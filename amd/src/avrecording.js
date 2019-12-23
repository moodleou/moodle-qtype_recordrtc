// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//

/**
 * JavaScript to the recording work.
 *
 * We would like to thank the creators of atto_recordrtc, whose
 * work inspired this.
 *
 * @package    qtype_recordrtc
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/log', 'core/modal_factory'], function(Log, ModalFactory) {

    /**
     * Verify that the question type can work. If not, show a warning.
     *
     * @return {string} 'ok' if it looks OK, else 'nowebrtc' or 'nothttps' if there is a problem.
     */
    function checkCanWork() {
        if (!(navigator.mediaDevices && MediaRecorder)) {
            return 'nowebrtc';
        }

        if (!(location.protocol === 'https:' || location.host.indexOf('localhost') !== -1)) {
            return 'nothttps';
        }

        return 'ok';
    }

    /**
     * Object for actually doing the recording.
     *
     * The recorder can be in one of 4 states, which is stored in a data-state
     * attribute on the button. The states are:
     *  - new:       there is no recording yet. Button shows 'Start recording'.
     *  - recording: buttons shows a countdown of remaining time. Media is being recorded.
     *  - saving:    buttons shows a progress indicator.
     *  - recorded:  button shows 'Record again'.
     *
     * @param {(AudioSettings|VideoSettings)} type
     * @param {HTMLMediaElement } mediaElement
     * @param {HTMLButtonElement} button
     * @param {Object} owner
     * @param {Object} settings
     * @constructor
     */
    function Recorder(type, mediaElement,
                      button, owner, settings) {

        /**
         * @type {MediaStream} during recording, the stream of incoming media.
         */
        var mediaStream = null;

        /**
         * @type {MediaRecorder} the recorder that is capturing stream.
         */
        var mediaRecorder = null;

        /**
         * @type {Blob[]} the chunks of data that have been captured so far duing the current recording.
         */
        var chunks = [];

        /**
         * @type {number} number of bytes recorded so far, so we can auto-stop
         * before hitting Moodle's file-size limit.
         */
        var bytesRecordedSoFar = 0;

        /**
         * @type {number} time left in seconds, so we can auto-stop at the time limit.
         */
        var secondsRemaining = 0;

        /**
         * @type {number} intervalID returned by setInterval() while the timer is running.
         */
        var countdownTicker = 0;

        button.addEventListener('click', handleButtonClick);

        /**
         * Handles clicks on the start/stop button.
         *
         * @param {Event} e
         */
        function handleButtonClick(e) {
            Log.debug('Start/stop button clicked.');
            e.preventDefault();
            switch (button.dataset.state) {
                case 'new':
                case 'recorded':
                    startRecording();
                    break;
                case 'recording':
                    stopRecording();
                    break;
            }
        }

        /**
         * Start recording (because the button was clicked).
         */
        function startRecording() {
            button.disabled = true;

            if (type.hidePlayerDuringRecording) {
                mediaElement.parentElement.classList.add('hide');
            } else {
                mediaElement.parentElement.classList.remove('hide');
            }

            // Change look of recording button.
            button.classList.remove('btn-outline-danger');
            button.classList.add('btn-danger');

            // Empty the array containing the previously recorded chunks.
            chunks = [];
            bytesRecordedSoFar = 0;
            Log.debug('Audio question type: Starting recording with media constraints');
            Log.debug(type.mediaConstraints);
            navigator.mediaDevices.getUserMedia(type.mediaConstraints)
                .then(handleCaptureStarting)
                .catch(handleCaptureFailed);
        }

        /**
         * Callback once getUserMedia has permission from the user to access the recording devices.
         *
         * @param {MediaStream} stream the stream to record.
         */
        function handleCaptureStarting(stream) {
            mediaStream = stream;

            // Initialize MediaRecorder events and start recording.
            var options = getRecordingOptions();
            Log.debug('Audio question type: creating recorder with opptions');
            Log.debug(options);
            mediaRecorder = new MediaRecorder(stream, options);

            mediaRecorder.ondataavailable = handleDataAvailable;
            mediaRecorder.onstop = handleRecordingHasStopped;
            Log.debug('Audio question type: starting recording.');
            mediaRecorder.start(1000); // Capture in one-second chunks. Firefox requires that.

            // Setup the UI for during recording.
            mediaElement.srcObject = stream;
            mediaElement.setAttribute('muted', '');
            button.dataset.state = 'recording';
            startCountdownTimer();

            // Make button clickable again, to allow stopping recording.
            button.disabled = false;
        }

        /**
         * Callback that is called by the media system for each Chunk of data.
         *
         * @param {BlobEvent} event
         */
        function handleDataAvailable(event) {
            Log.debug('Audio question type: chunk of ' + event.data.size + ' bytes received.');

            // Check there is space to store the next chunk, and if not stop.
            bytesRecordedSoFar += event.data.size;
            if (bytesRecordedSoFar >= settings.maxUploadSize) {

                // Extra check to avoid alerting twice.
                if (!localStorage.getItem('alerted')) {
                    localStorage.setItem('alerted', 'true');
                    stopRecording();
                    owner.showAlert('nearingmaxsize');

                } else {
                    localStorage.removeItem('alerted');
                }
            }

            // Store the next chunk of data.
            chunks.push(event.data);
        }

        /**
         * Start recording (because the button was clicked or because we have reached a limit).
         */
        function stopRecording() {
            // Disable the button while things change. Gets re-enabled once recording is underway.
            button.disabled = true;
            setTimeout(function() {
                button.disabled = false;
            }, 1000);

            // Stop the count-down timer.
            stopCountdownTimer();

            // Update the button.
            button.innerText = M.util.get_string('recordagain', 'qtype_recordrtc');
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');

            // Ask the recording to stop.
            Log.debug('Audio question type: stopping recording.');
            mediaRecorder.stop();

            // Also stop each individual MediaTrack.
            var tracks = mediaStream.getTracks();
            for (var i = 0; i < tracks.length; i++) {
                tracks[i].stop();
            }
        }

        /**
         * Callback that is called by the media system once recording has finished.
         */
        function handleRecordingHasStopped() {
            // Set source of audio player.
            Log.debug('Audio question type: recording stopped.');
            var blob = new Blob(chunks, {type: mediaRecorder.mimeType});
            mediaElement.src = URL.createObjectURL(blob);

            // Show audio player with controls enabled, and unmute.
            mediaElement.muted = false;
            mediaElement.controls = true;
            mediaElement.parentElement.classList.remove('hide');

            button.innerText = M.util.get_string('recordagain', 'qtype_recordrtc');
            button.disabled = false;
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');
            button.dataset.state = 'recorded';

            if (chunks.length > 0) {
                owner.notifyRecordingComplete(this);
            }
        }

        /**
         * Function that handles errors from the recorder.
         *
         * @param {DOMException} error
         */
        function handleCaptureFailed(error) {
            Log.debug('Audio question type: error received');
            Log.debug(error);

            button.innerText = M.util.get_string('recordingfailed', 'qtype_recordrtc');
            button.disabled = false;
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');
            button.dataset.state = 'new';

            // Changes 'CertainError' -> 'gumcertain' to match language string names.
            var stringName = 'gum' + error.name.replace('Error', '').toLowerCase();

            owner.showAlert(stringName);
        }

        /**
         * Start the countdown timer from settings.timeLimit.
         */
        function startCountdownTimer() {
            secondsRemaining = settings.timeLimit + 1;

            button.innerHTML = M.util.get_string('stoprecording', 'qtype_recordrtc') + ' (<span></span>)';
            updateTimerDisplay();
            countdownTicker = setInterval(updateTimerDisplay, 1000);
        }

        /**
         * Stop the countdown timer.
         */
        function stopCountdownTimer() {
            if (countdownTicker !== 0) {
                clearInterval(countdownTicker);
                countdownTicker = 0;
            }
        }

        /**
         * Update the countdown timer, and stop recording if we have reached 0.
         */
        function updateTimerDisplay() {
            secondsRemaining -= 1;

            var secs = secondsRemaining % 60;
            var mins = Math.round((secondsRemaining - secs) / 60);
            button.querySelector('span').innerText = pad(mins) + ':' + pad(secs);

            if (secondsRemaining === 0) {
                stopRecording();
            }
        }

        /**
         * Zero-pad a string to be at least two characters long.
         *
         * Used fro
         * @param {number} val, e.g. 1 or 10
         * @return {string} e.g. '01' or '10'.
         */
        function pad(val) {
            var valString = val + '';

            if (valString.length < 2) {
                return '0' + valString;
            } else {
                return valString;
            }
        }

        // /**
        //  *
        //  * @param callback
        //  */
        // function uploadMediaToServer(callback) {
        //     var xhr = new window.XMLHttpRequest();
        //
        //     // Get src media of audio/video tag.
        //     var player = questionDiv.find('.media-player ' + type);
        //     xhr.open('GET', player.attr('src'), true);
        //     xhr.responseType = 'blob';
        //
        //     xhr.onload = function() {
        //         if (xhr.status === 200) { // If src media was successfully retrieved.
        //             // blob is now the media that the audio/video tag's src pointed to.
        //             var blob = this.response;
        //
        //             // Generate filename with random ID and file extension.
        //             var fileName = (Math.random() * 1000).toString().replace('.', '');
        //             fileName += (type === 'audio') ? '-audio.ogg'
        //                 : '-video.webm';
        //
        //             // Create FormData to send to PHP filepicker-upload script.
        //             var formData = new window.FormData(),
        //                 filepickerOptions = t.commonmodule.saveFileUrl,
        //                 repositoryKeys = window.Object.keys(filepickerOptions.repositories);
        //
        //             formData.append('repo_upload_file', blob, fileName);
        //             formData.append('itemid', filepickerOptions.itemid);
        //
        //             for (var i = 0; i < repositoryKeys.length; i++) {
        //                 if (filepickerOptions.repositories[repositoryKeys[i]].type === 'upload') {
        //                     formData.append('repo_id', filepickerOptions.repositories[repositoryKeys[i]].id);
        //                     break;
        //                 }
        //             }
        //
        //             formData.append('env', filepickerOptions.env);
        //             formData.append('sesskey', M.cfg.sesskey);
        //             formData.append('client_id', filepickerOptions.client_id);
        //             formData.append('savepath', '/');
        //             formData.append('ctx_id', filepickerOptions.context.id);
        //
        //             // Pass FormData to PHP script using XHR.
        //             var uploadEndpoint = M.cfg.wwwroot + '/repository/repository_ajax.php?action=upload';
        //             t.commonmodule.makeXmlHttpRequest(uploadEndpoint, formData,
        //                 function(progress, responseText) {
        //                     if (progress === 'upload-ended') {
        //                         callback('ended', window.JSON.parse(responseText).url);
        //                     } else {
        //                         callback(progress);
        //                     }
        //                 }
        //             );
        //         }
        //     };
        //
        //     xhr.send();
        // }
        //
        // function makeXmlHttpRequest(url, data, callback) {
        //     var xhr = new window.XMLHttpRequest();
        //
        //     xhr.onreadystatechange = function() {
        //         if ((xhr.readyState === 4) && (xhr.status === 200)) { // When request is finished and successful.
        //             callback('upload-ended', xhr.responseText);
        //         } else if (xhr.status === 404) { // When request returns 404 Not Found.
        //             callback('upload-failed-404');
        //         }
        //     };
        //
        //     xhr.upload.onprogress = function(event) {
        //         callback(Math.round(event.loaded / event.total * 100) + "% " +
        //             M.util.get_string('uploadprogress', 'qtype_recordrtc'));
        //     };
        //
        //     xhr.upload.onerror = function(error) {
        //         callback('upload-failed', error);
        //     };
        //
        //     xhr.upload.onabort = function(error) {
        //         callback('upload-aborted', error);
        //     };
        //
        //     // POST FormData to PHP script that handles uploading/saving.
        //     xhr.open('POST', url);
        //     xhr.send(data);
        // }

        /**
         * Select best options for the recording codec.
         *
         * @returns {Object}
         */
        function getRecordingOptions() {
            var options = {};

            // Get the relevant bit rates from settings.
            options.audioBitsPerSecond = parseInt(settings.audioBitRate, 10);
            if (type.name === 'video') {
                options.videoBitsPerSecond = parseInt(settings.videoBitRate, 10);
            }

            // Go through our list of mimeTypes, and take the first one that will work.
            for (var i = 0; i < type.mimeTypes.length; i++) {
                if (MediaRecorder.isTypeSupported(type.mimeTypes[i])) {
                    options.mimeType = type.mimeTypes[i];
                    break;
                }
            }

            return options;
        }
    }

    /**
     * Fixed object which has the info specific to recording audio.
     * @type {Object}
     */
    var AudioSettings = {
        name: 'audio',
        hidePlayerDuringRecording: true,
        mediaConstraints: {
            audio: true
        },
        mimeTypes: [
            'audio/webm;codecs=opus',
            'audio/ogg;codecs=opus'
        ]
    };

    /**
     * Fixed object which has the info specific to recording video.
     * @type {Object}
     */
    var VideoSettings = {
        name: 'video',
        hidePlayerDuringRecording: false,
        mediaConstraints: {
            audio: true,
            video: {
                width: {ideal: 640},
                height: {ideal: 480}
            }
        },
        mimeTypes: [
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=h264,opus',
            'video/webm;codecs=vp8,opus'
        ]
    };

    /**
     * Represents one record audio (or video) question.
     *
     * @param {string} questionId id of the outer question div.
     * @param {Object} settings like audio bit rate.
     * @param {string} type 'audio' or 'video'.
     * @constructor
     */
    function RecordRtcQuestion(questionId, settings, type) {
        M.util.js_pending('init-' + questionId);
        var questionDiv = document.getElementById(questionId);

        // Check if the RTC API can work here.
        var result = checkCanWork();
        if (result === 'nowebrtc') {
            questionDiv.querySelector('.no-webrtc-warning').classList.remove('hide');
            return;
        } else if (result === 'nothttps') {
            questionDiv.querySelector('.https-warning').classList.remove('hide');
            return;
        }

        // Get the appropriate options.
        var typeInfo;
        if (type === 'audio') {
            typeInfo = AudioSettings;
        } else {
            typeInfo = VideoSettings;
        }

        // Get the key UI elements.
        var button = questionDiv.querySelector('.record-button button');
        var mediaElement = questionDiv.querySelector('.media-player ' + type);

        // Make the callback functions available.
        this.showAlert = showAlert;
        this.notifyRecordingComplete = notifyRecordingComplete;

        // Create the recorder.
        new Recorder(typeInfo, mediaElement, button, this, settings);

        M.util.js_complete('init-' + questionId);

        /**
         * Show a modal alert.
         *
         * @param {string} subject Subject is the content of the alert (which error the alert is for).
         * @return {Promise}
         */
        function showAlert(subject) {
            return ModalFactory.create({
                type: ModalFactory.types.ALERT,
                title: M.util.get_string(subject + '_title', 'qtype_recordrtc'),
                body: M.util.get_string(subject, 'qtype_recordrtc'),
            }).then(function(modal) {
                modal.show();
                return modal;
            });
        }

        /**
         * Callback called when the recofding is
         *
         * @param {Recorder} recorder the recorder.
         */
        function notifyRecordingComplete(recorder) {
            Log.debug(recorder);
//            recorder.uploadMediaToServer();
        }

        // function uploadStatus() {
        //     // Upload recording to server.
        //     t.commonmodule.uploadToServer(function(progress, fileURLOrError) {
        //         if (progress === 'ended') { // Insert annotation in text.
        //         } else if (progress === 'upload-failed') { // Show error message in upload button.
        //             M.util.get_string('uploadfailed', 'qtype_recordrtc') + ' ' + fileURLOrError;
        //         } else if (progress === 'upload-failed-404') { // 404 error = File too large in Moodle.
        //             M.util.get_string('uploadfailed404', 'qtype_recordrtc');
        //         } else if (progress === 'upload-aborted') {
        //             M.util.get_string('uploadaborted', 'qtype_recordrtc') + ' ' + fileURLOrError;
        //         } else {
        //             progress;
        //         }
        //     });
        //
        // }
    }

    return {
        /**
         * Initialise a record audio (or video) question.
         *
         * @param {string} questionId id of the outer question div.
         * @param {Object} settings like audio bit rate.
         */
        init: function(questionId, settings) {
            new RecordRtcQuestion(questionId, settings, 'audio');
        }
    };
});
