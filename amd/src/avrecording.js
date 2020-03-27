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
        if (!(navigator.mediaDevices && window.MediaRecorder)) {
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
     * @param {HTMLMediaElement} mediaElement
     * @param {HTMLMediaElement} noMediaPlaceholder
     * @param {HTMLButtonElement} button
     * @param {HTMLElement} uploadProgressElement
     * @param {NodeList} otherControls other controls to disable while recording is in progress.
     * @param {string} filename the name of the audio (.ogg) or video file (.webm)
     * @param {Object} owner
     * @param {Object} settings
     * @constructor
     */
    function Recorder(type, mediaElement, noMediaPlaceholder,
                      button, uploadProgressElement,
                      otherControls, filename, owner, settings) {
        /**
         * @type {Recorder} reference to this recorder, for use in event handlers.
         */
        var recorder = this;

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
        this.uploadMediaToServer = uploadMediaToServer; // Make this method available.

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
                noMediaPlaceholder.classList.remove('hide');
                noMediaPlaceholder.textContent = '';
            } else {
                mediaElement.parentElement.classList.remove('hide');
                noMediaPlaceholder.classList.add('hide');
            }
            uploadProgressElement.classList.add('hide');

            // Change look of recording button.
            button.classList.remove('btn-outline-danger');
            button.classList.add('btn-danger');

            // Empty the array containing the previously recorded chunks.
            chunks = [];
            bytesRecordedSoFar = 0;
            Log.debug('Audio question: Starting recording with media constraints');
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
            Log.debug('Audio question: creating recorder with opptions');
            Log.debug(options);
            mediaRecorder = new MediaRecorder(stream, options);

            mediaRecorder.ondataavailable = handleDataAvailable;
            mediaRecorder.onstop = handleRecordingHasStopped;
            Log.debug('Audio question: starting recording.');
            mediaRecorder.start(1000); // Capture in one-second chunks. Firefox requires that.

            // Setup the UI for during recording.
            mediaElement.srcObject = stream;
            mediaElement.setAttribute('muted', '');
            button.dataset.state = 'recording';
            startCountdownTimer();

            setOtherControlsEnabled(false);

            // Make button clickable again, to allow stopping recording.
            button.disabled = false;
            button.focus();
        }

        /**
         * Callback that is called by the media system for each Chunk of data.
         *
         * @param {BlobEvent} event
         */
        function handleDataAvailable(event) {
            Log.debug('Audio question: chunk of ' + event.data.size + ' bytes received.');

            // Check there is space to store the next chunk, and if not stop.
            bytesRecordedSoFar += event.data.size;
            if (settings.maxUploadSize >= 0 && bytesRecordedSoFar >= settings.maxUploadSize) {

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

            // Notify form-change-checker that there is now unsaved data.
            // But, don't do this in question preview where it is just annoying.
            if (typeof M.core_formchangechecker !== 'undefined' &&
                    !window.location.pathname.endsWith('/question/preview.php')) {
                M.core_formchangechecker.set_form_changed();
            }
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

            setOtherControlsEnabled(true);

            // Ask the recording to stop.
            Log.debug('Audio question: stopping recording.');
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
            Log.debug('Audio question: recording stopped.');
            var blob = new Blob(chunks, {type: mediaRecorder.mimeType});
            mediaElement.srcObject = null;
            mediaElement.src = URL.createObjectURL(blob);

            // Show audio player with controls enabled, and unmute.
            mediaElement.muted = false;
            mediaElement.controls = true;
            mediaElement.parentElement.classList.remove('hide');
            noMediaPlaceholder.classList.add('hide');
            mediaElement.focus();

            button.innerText = M.util.get_string('recordagain', 'qtype_recordrtc');
            button.disabled = false;
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');
            button.dataset.state = 'recorded';

            if (chunks.length > 0) {
                owner.notifyRecordingComplete(recorder);
            }
        }

        /**
         * Function that handles errors from the recorder.
         *
         * @param {DOMException} error
         */
        function handleCaptureFailed(error) {
            Log.debug('Audio question: error received');
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

        /**
         * Upload the recorded media back to Moodle.
         */
        function uploadMediaToServer() {
            setUploadMessage('uploadpreparing');
            uploadProgressElement.classList.remove('hide');
            noMediaPlaceholder.classList.add('hide');
            setOtherControlsEnabled(false);

            var fetchRequest = new XMLHttpRequest();

            // Get media of audio/video tag.
            fetchRequest.open('GET', mediaElement.src);
            fetchRequest.responseType = 'blob';
            fetchRequest.addEventListener('load', handleRecordingFetched);
            fetchRequest.send();
        }

        /**
         * Callback called once we have the data from the media element.
         *
         * @param {ProgressEvent} e
         */
        function handleRecordingFetched(e) {
            var fetchRequest = e.target;
            if (fetchRequest.status !== 200) {
                // No data.
                return;
            }

            // Blob is now the media that the audio/video tag's src pointed to.
            var blob = fetchRequest.response;

            // Create FormData to send to PHP filepicker-upload script.
            var formData = new FormData();
            formData.append('repo_upload_file', blob, filename);
            formData.append('sesskey', M.cfg.sesskey);
            formData.append('repo_id', settings.uploadRepositoryId);
            formData.append('itemid', settings.draftItemId);
            formData.append('savepath', '/');
            formData.append('ctx_id', settings.contextId);
            formData.append('overwrite', 1);

            var uploadRequest = new XMLHttpRequest();
            uploadRequest.addEventListener('readystatechange', handleUploadReadyStateChanged);
            uploadRequest.upload.addEventListener('progress', handleUploadProgress);
            uploadRequest.addEventListener('error', handleUploadError);
            uploadRequest.addEventListener('abort', handleUploadAbort);
            uploadRequest.open('POST', M.cfg.wwwroot + '/repository/repository_ajax.php?action=upload');
            uploadRequest.send(formData);
        }

        /**
         * Callback for when the upload completes.
         * @param {ProgressEvent} e
         */
        function handleUploadReadyStateChanged(e) {
            var uploadRequest = e.target;
            if (uploadRequest.readyState === 4 && uploadRequest.status === 200) {
                // When request finished and successful.
                setUploadMessage('uploadcomplete');
            } else if (uploadRequest.status === 404) {
                setUploadMessage('uploadfailed404');
            }
            setOtherControlsEnabled(true);
        }

        /**
         * Callback for updating the upload progress.
         * @param {ProgressEvent} e
         */
        function handleUploadProgress(e) {
            setUploadMessage('uploadprogress', Math.round(e.loaded / e.total * 100) + '%');
        }

        /**
         * Callback for when the upload fails with an error.
         */
        function handleUploadError() {
            setUploadMessage('uploadfailed');
        }

        /**
         * Callback for when the upload fails with an error.
         */
        function handleUploadAbort() {
            setUploadMessage('uploadaborted');
        }

        /**
         * Display a progress message in the upload progress area.
         *
         * @param {string} langString
         * @param {Object|String} a optional variable to populate placeholder with
         */
        function setUploadMessage(langString, a) {
            uploadProgressElement.querySelector('small').innerText =
                    M.util.get_string(langString, 'qtype_recordrtc', a);
        }

        /**
         * Set the state of the otherControls to enabled or disabled.
         *
         * @param {boolean} enabled true to enable. False to disable.
         */
        function setOtherControlsEnabled(enabled) {
            otherControls.forEach(function(node) {
                node.disabled = !enabled;
            });
        }

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
        var questionDiv = document.getElementById(questionId);

        // Check if the RTC API can work here.
        var result = checkCanWork();
        if (result === 'nothttps') {
            questionDiv.querySelector('.https-warning').classList.remove('hide');
            return;
        } else if (result === 'nowebrtc') {
            questionDiv.querySelector('.no-webrtc-warning').classList.remove('hide');
            return;
        }

        // Get the appropriate options.
        var typeInfo;
        if (type === 'audio') {
            typeInfo = AudioSettings;
        } else {
            typeInfo = VideoSettings;
        }

        // We may have more than one widget in a question.
        var recorderElements = questionDiv.querySelectorAll('.record-widget');
        recorderElements.forEach (function(rElement) {
            // Get the key UI elements.
            var button = rElement.querySelector('.record-button button');
            var mediaElement = rElement.querySelector('.media-player ' + type);
            var noMediaPlaceholder = rElement.querySelector('.no-recording-placeholder');
            var uploadProgressElement = rElement.querySelector('.saving-message');
            var otherControls = rElement.querySelectorAll('input.submit[type=submit]');
            var filename = rElement.dataset.recordingFilename;

            // Make the callback functions available.
            this.showAlert = showAlert;
            this.notifyRecordingComplete = notifyRecordingComplete;

            // Create the recorder.
            new Recorder(typeInfo, mediaElement, noMediaPlaceholder, button,
                    uploadProgressElement, otherControls, filename, this, settings);
        });

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
            recorder.uploadMediaToServer();
        }
    }

    return {
        /**
         * Initialise a record audio (or video) question.
         *
         * @param {string} questionId id of the outer question div.
         * @param {Object} settings like audio bit rate.
         * @param {string} type 'audio' or 'video'.
         */
        init: function(questionId, settings, type) {
            M.util.js_pending('init-' + questionId);
            new RecordRtcQuestion(questionId, settings, type);
            M.util.js_complete('init-' + questionId);
        }
    };
});
