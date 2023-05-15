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
 * work originally inspired this.
 *
 * This script uses some third-party JavaScript and loading that within Moodle/ES6
 * requires some contortions. The main classes here are:
 *
 * * Recorder - represents one recording widget. This works in a way that is
 *   not particularly specific to this question type.
 * * RecordRtcQuestion - represents one question, which may contain several recorders.
 *   It deals with the interaction between the recorders and the question.
 *
 * @module    qtype_recordrtc/avrecording
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Log from 'core/log';
import ModalFactory from 'core/modal_factory';

/**
 * Verify that the question type can work. If not, show a warning.
 *
 * @return {string} 'ok' if it looks OK, else 'nowebrtc' or 'nothttps' if there is a problem.
 */
function checkCanWork() {
    // Check APIs are known.
    if (!(navigator.mediaDevices && window.MediaRecorder)) {
        return 'nowebrtc';
    }

    // Check protocol (localhost).
    if (location.protocol === 'https:' ||
            location.host === 'localhost' || location.host === '127.0.0.1') {
        return 'ok';
    } else {
        return 'nothttps';
    }
}

/**
 * Object for actually doing the recording.
 *
 * The recorder can be in one of several states, which is stored in a data-state
 * attribute on the outer span (widget). The states are:
 *
 *  - new:       there is no recording yet. Button shows 'Start recording' (audio) or 'Start camera' (video).
 *  - starting:  (video only) camera has started, but we are not recording yet. Button show 'Start recording'.
 *  - recording: Media is being recorded. Pause button visible if allowed. Main button shows 'Stop'. Countdown displayed.
 *  - paused:    If pause was pressed. Media recording paused, but resumable. Pause button changed to say 'resume'.
 *  - saving:    Media being uploaded. Progress indication shown. Pause button hidden if was visible.
 *  - recorded:  Recording and upload complete. The button then shows 'Record again'.
 *
 * @param {HTMLElement} widget the DOM node that is the top level of the whole recorder.
 * @param {(AudioSettings|VideoSettings)} mediaSettings information about the media type.
 * @param {Object} owner the object we are doing the recording for. Must provide three callback functions
 *                       showAlert notifyRecordingComplete notifyButtonStatesChanged.
 * @param {Object} uploadInfo object with fields uploadRepositoryId, draftItemId, contextId and maxUploadSize.
 * @constructor
 */
function Recorder(widget, mediaSettings, owner, uploadInfo) {
    /**
     * @type {Recorder} reference to this recorder, for use in event handlers.
     */
    const recorder = this;

    /**
     * @type {MediaStream} during recording, the stream of incoming media.
     */
    let mediaStream = null;

    /**
     * @type {MediaRecorder} the recorder that is capturing stream.
     */
    let mediaRecorder = null;

    /**
     * @type {Blob[]} the chunks of data that have been captured so far during the current recording.
     */
    let chunks = [];

    /**
     * @type {number} number of bytes recorded so far, so we can auto-stop
     * before hitting Moodle's file-size limit.
     */
    let bytesRecordedSoFar = 0;

    /**
     * @type {number} when paused, the time left in milliseconds, so we can auto-stop at the time limit.
     */
    let timeRemaining = 0;

    /**
     * @type {number} while recording, the time we reach the time-limit, so we can auto-stop then.
     * This is milliseconds since Unix epoch, so comparable with Date.now().
     */
    let stopTime = 0;

    /**
     * @type {number} intervalID returned by setInterval() while the timer is running.
     */
    let countdownTicker = 0;

    const button = widget.querySelector('button.qtype_recordrtc-main-button');
    const pauseButton = widget.querySelector('.qtype_recordrtc-pause-button button');
    const controlRow = widget.querySelector('.qtype_recordrtc-control-row');
    const mediaElement = widget.querySelector('.qtype_recordrtc-media-player ' +
        (mediaSettings.name === 'screen' ? 'video' : mediaSettings.name));
    const noMediaPlaceholder = widget.querySelector('.qtype_recordrtc-no-recording-placeholder');
    const timeDisplay = widget.querySelector('.qtype_recordrtc-time-left');
    const progressBar = widget.querySelector('.qtype_recordrtc-time-left .qtype_recordrtc-timer-front');
    const backTimeEnd = widget.querySelector('.qtype_recordrtc-time-left .qtype_recordrtc-timer-back span.timer-end');
    const backtimeStart = widget.querySelector('.qtype_recordrtc-time-left .qtype_recordrtc-timer-back span.timer-start');
    const frontTimeEnd = widget.querySelector('.qtype_recordrtc-time-left .qtype_recordrtc-timer-front span.timer-end');
    const fronttimeStart = widget.querySelector('.qtype_recordrtc-time-left .qtype_recordrtc-timer-front span.timer-start');

    widget.addEventListener('click', handleButtonClick);
    this.uploadMediaToServer = uploadMediaToServer; // Make this method available.

    /**
     * Handles clicks on the start/stop and pause buttons.
     *
     * @param {Event} e
     */
    function handleButtonClick(e) {
        const clickedButton = e.target.closest('button');
        if (!clickedButton) {
            return; // Not actually a button click.
        }
        e.preventDefault();
        switch (widget.dataset.state) {
            case 'new':
            case 'recorded':
                startRecording();
                break;
            case 'starting':
                if (mediaSettings.name === 'screen') {
                    startScreenSaving();
                } else {
                    startSaving();
                }
                break;
            case 'recording':
                if (clickedButton === pauseButton) {
                    pause();
                } else {
                    stopRecording();
                }
                break;
            case 'paused':
                if (clickedButton === pauseButton) {
                    resume();
                } else {
                    stopRecording();
                }
                break;
        }
    }

    /**
     * To handle every time the audio mic has a problem.
     * For now, we will allow video to be saved without sound when there is an error with the microphone.
     *
     * @param {Object} error A error object.
     */
    function handleScreenSharingError(error) {
        Log.debug(error);
        startSaving();
    }

    /**
     * When recorder type is screen, we need add audio mic stream into mediaStream
     * before saving.
     */
    function startScreenSaving() {
        // We need to combine 2 audio and screen-sharing streams to create a recording with audio from the mic.
        navigator.mediaDevices.enumerateDevices().then(() => {
            // Get audio stream from microphone.
            return navigator.mediaDevices.getUserMedia({audio: true});
        }).then(micStream => {
            let composedStream = new MediaStream();
            // When the user shares their screen, we need to merge the video track from the media stream with
            // the audio track from the microphone stream and stop any unnecessary tracks to ensure
            // that the recorded video has microphone sound.
            mediaStream.getTracks().forEach(function(track) {
                if (track.kind === 'video') {
                    // Add video track into stream.
                    composedStream.addTrack(track);
                } else {
                    // Stop any audio track.
                    track.stop();
                }
            });

            // Add mic audio track from mic stream into composedStream to track audio.
            // This will make sure the recorded video will have mic sound.
            micStream.getAudioTracks().forEach(function(micTrack) {
                composedStream.addTrack(micTrack);
            });
            mediaStream = composedStream;
            startSaving();
            return true;
        }).catch(handleScreenSharingError);
    }

    /**
     * Start recording (because the button was clicked).
     */
    function startRecording() {

        // Reset timer label.
        setLabelForTimer(0, parseInt(widget.dataset.maxRecordingDuration));

        if (mediaSettings.name === 'audio') {
            mediaElement.parentElement.classList.add('hide');
            noMediaPlaceholder.classList.add('hide');
            timeDisplay.classList.remove('hide');

        } else {
            mediaElement.parentElement.classList.remove('hide');
            noMediaPlaceholder.classList.add('hide');
        }
        pauseButton?.parentElement.classList.remove('hide');

        // Change look of recording button.
        button.classList.remove('btn-outline-danger');
        button.classList.add('btn-danger');

        // Disable other question buttons when current widget stared recording.
        disableAllButtons();

        // Empty the array containing the previously recorded chunks.
        chunks = [];
        bytesRecordedSoFar = 0;
        if (mediaSettings.name === 'screen') {
            navigator.mediaDevices.getDisplayMedia(mediaSettings.mediaConstraints)
                .then(handleCaptureStarting)
                .catch(handleCaptureFailed);
        } else {
            navigator.mediaDevices.getUserMedia(mediaSettings.mediaConstraints)
                .then(handleCaptureStarting)
                .catch(handleCaptureFailed);
        }
    }

    /**
     * Callback once getUserMedia has permission from the user to access the recording devices.
     *
     * @param {MediaStream} stream the stream to record.
     */
    function handleCaptureStarting(stream) {
        mediaStream = stream;

        // Setup the UI for during recording.
        mediaElement.srcObject = stream;
        mediaElement.muted = true;
        if (mediaSettings.name === 'audio') {
            startSaving();
        } else {
            // Cover when user clicks Browser's "Stop Sharing Screen" button.
            if (mediaSettings.name === 'screen') {
                mediaStream.getVideoTracks()[0].addEventListener('ended', handleStopSharing);
            }
            mediaElement.play();
            mediaElement.controls = false;

            widget.dataset.state = 'starting';
            setButtonLabel('startrecording');
            widget.querySelector('.qtype_recordrtc-stop-button').disabled = false;
        }

        // Make button clickable again, to allow starting/stopping recording.
        if (pauseButton) {
            pauseButton.disabled = false;
        }
        button.disabled = false;
        button.focus();
    }

    /**
     * For recording types which show the media during recording,
     * this starts the loop-back display, but does not start recording it yet.
     */
    function startSaving() {
        // Initialize MediaRecorder events and start recording.
        mediaRecorder = new MediaRecorder(mediaStream, getRecordingOptions());

        mediaRecorder.ondataavailable = handleDataAvailable;
        mediaRecorder.onpause = handleDataAvailable;
        mediaRecorder.onstop = handleRecordingHasStopped;
        mediaRecorder.start(1000); // Capture in one-second chunks. Firefox requires that.

        widget.dataset.state = 'recording';
        // Set duration for progressbar and start animate.
        progressBar.style.animationDuration = widget.dataset.maxRecordingDuration + 's';
        progressBar.classList.add('animate');
        setButtonLabel('stoprecording');
        startCountdownTimer();
        if (mediaSettings.name === 'video' || mediaSettings.name === 'screen') {
            button.parentElement.classList.add('hide');
            controlRow.classList.remove('hide');
            controlRow.classList.add('d-flex');
            timeDisplay.classList.remove('hide');
        }
    }

    /**
     * Callback that is called by the user clicking Stop screen sharing on the browser.
     */
    function handleStopSharing() {
        if (widget.dataset.state === 'starting') {
            widget.dataset.state = 'new';
            mediaElement.parentElement.classList.add('hide');
            noMediaPlaceholder.classList.remove('hide');
            setButtonLabel('startsharescreen');
            button.blur();
        } else {
            const controlEl = widget.querySelector('.qtype_recordrtc-control-row');
            if (!controlEl.classList.contains('hide')) {
                controlEl.querySelector('.qtype_recordrtc-stop-button').click();
            }
        }
        enableAllButtons();
    }

    /**
     * Callback that is called by the media system for each Chunk of data.
     *
     * @param {BlobEvent} event
     */
    function handleDataAvailable(event) {
        if (!event.data) {
            return; // It seems this can happen around pausing.
        }

        // Check there is space to store the next chunk, and if not stop.
        bytesRecordedSoFar += event.data.size;
        if (uploadInfo.maxUploadSize >= 0 && bytesRecordedSoFar >= uploadInfo.maxUploadSize) {

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
     * Pause recording.
     */
    function pause() {
        // Stop the count-down timer.
        stopCountdownTimer();
        setPauseButtonLabel('resume');
        mediaRecorder.pause();
        widget.dataset.state = 'paused';
        // Pause animate.
        toggleProgressbarState();
    }

    /**
     * Continue recording.
     */
    function resume() {
        // Stop the count-down timer.
        resumeCountdownTimer();
        widget.dataset.state = 'recording';
        setPauseButtonLabel('pause');
        mediaRecorder.resume();
        // Resume animate.
        toggleProgressbarState();
    }

    /**
     * Start recording (because the button was clicked or because we have reached a limit).
     */
    function stopRecording() {
        // Disable the button while things change.
        button.disabled = true;

        // Stop the count-down timer.
        stopCountdownTimer();

        // Update the button.
        button.classList.remove('btn-danger');
        button.classList.add('btn-outline-danger');
        if (pauseButton) {
            setPauseButtonLabel('pause');
            pauseButton.parentElement.classList.add('hide');
        }

        // Reset animation state.
        progressBar.style.animationPlayState = 'running';
        // Stop animate.
        progressBar.classList.remove('animate');

        // Ask the recording to stop.
        mediaRecorder.stop();

        // Also stop each individual MediaTrack.
        const tracks = mediaStream.getTracks();
        for (let i = 0; i < tracks.length; i++) {
            tracks[i].stop();
        }
    }

    /**
     * Callback that is called by the media system once recording has finished.
     */
    function handleRecordingHasStopped() {
        if (widget.dataset.state === 'new') {
            // This can happens if an error occurs when recording is starting. Do nothing.
            return;
        }

        // Set source of the media player.
        const blob = new Blob(chunks, {type: mediaRecorder.mimeType});
        mediaElement.srcObject = null;
        mediaElement.src = URL.createObjectURL(blob);

        // Show audio player with controls enabled, and unmute.
        mediaElement.muted = false;
        mediaElement.controls = true;
        mediaElement.parentElement.classList.remove('hide');
        noMediaPlaceholder.classList.add('hide');
        mediaElement.focus();

        if (mediaSettings.name === 'audio') {
            timeDisplay.classList.add('hide');

        } else {
            button.parentElement.classList.remove('hide');
            controlRow.classList.add('hide');
            controlRow.classList.remove('d-flex');
        }

        // Ensure the button while things change.
        button.disabled = true;
        button.classList.remove('btn-danger');
        button.classList.add('btn-outline-danger');
        widget.dataset.state = 'recorded';

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
        Log.debug('Audio/video/screen question: error received');
        Log.debug(error);

        setPlaceholderMessage('recordingfailed');
        setButtonLabel('recordagainx');
        button.classList.remove('btn-danger');
        button.classList.add('btn-outline-danger');
        widget.dataset.state = 'new';
        // Hide time display.
        timeDisplay.classList.add('hide');

        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }

        // Changes 'CertainError' -> 'gumcertain' to match language string names.
        const stringName = 'gum' + error.name.replace('Error', '').toLowerCase();

        owner.showAlert(stringName);
        enableAllButtons();
    }

    /**
     * Start the countdown timer.
     */
    function startCountdownTimer() {
        timeRemaining = widget.dataset.maxRecordingDuration * 1000;
        resumeCountdownTimer();
        updateTimerDisplay();
    }

    /**
     * Stop the countdown timer.
     */
    function stopCountdownTimer() {
        timeRemaining = stopTime - Date.now();
        if (countdownTicker !== 0) {
            clearInterval(countdownTicker);
            countdownTicker = 0;
        }
    }

    /**
     * Start or resume the countdown timer.
     */
    function resumeCountdownTimer() {
        stopTime = Date.now() + timeRemaining;
        if (countdownTicker === 0) {
            countdownTicker = setInterval(updateTimerDisplay, 100);
        }
    }

    /**
     * Update the countdown timer, and stop recording if we have reached 0.
     */
    function updateTimerDisplay() {
        const millisecondsRemaining = stopTime - Date.now();
        const secondsRemaining = Math.round(millisecondsRemaining / 1000);
        const secondsStart = widget.dataset.maxRecordingDuration - secondsRemaining;
        // Set time label for elements.
        setLabelForTimer(secondsStart, secondsRemaining);
        if (millisecondsRemaining <= 0) {
            stopRecording();
        }
    }

    /**
     * Get time label for timer.
     *
     * @param {number} seconds The time in seconds.
     * @return {string} The label for timer. e.g. '00:00' or '10:00'.
     */
    function getTimeLabelForTimer(seconds) {
        const secs = seconds % 60;
        const mins = Math.round((seconds - secs) / 60);

        return M.util.get_string('timedisplay', 'qtype_recordrtc',
            {mins: pad(mins), secs: pad(secs)});
    }

    /**
     * Set time label for timer.
     * We need to update the labels for both the timer back(whose background color is white) and
     * timer front (with blue background) to create a text effect that contrasts with the background color.
     *
     * @param {Number} secondsStart The second start. e.g: With duration 1 minute
     * secondsStart will start from 0 and increase up to 60.
     * @param {Number} secondsRemaining The second remaining. e.g: With duration 1 minute
     * secondsRemaining will decrease from 60 to 0.
     */
    function setLabelForTimer(secondsStart, secondsRemaining) {
        // Set time label for timer back.
        backTimeEnd.innerText = getTimeLabelForTimer(secondsRemaining);
        backtimeStart.innerText = getTimeLabelForTimer(secondsStart);
        // Set time label for timer front.
        frontTimeEnd.innerText = getTimeLabelForTimer(secondsRemaining);
        fronttimeStart.innerText = getTimeLabelForTimer(secondsStart);
    }

    /**
     * Zero-pad a string to be at least two characters long.
     *
     * @param {number} val e.g. 1 or 10
     * @return {string} e.g. '01' or '10'.
     */
    function pad(val) {
        const valString = val + '';

        if (valString.length < 2) {
            return '0' + valString;
        } else {
            return '' + valString;
        }
    }

    /**
     * Trigger the upload of the recorded media back to Moodle.
     */
    async function uploadMediaToServer() {
        setButtonLabel('uploadpreparing');

        if (widget.dataset.convertToMp3) {
            const mp3DataBlob = await convertOggToMp3(mediaElement.src);
            mediaElement.src = URL.createObjectURL(mp3DataBlob);
            uploadBlobToRepository(mp3DataBlob, widget.dataset.recordingFilename.replace(/\.ogg$/, '.mp3'));
        } else {
            // First we need to get the media data from the media element.
            const oggDataBlob = await fetchOggData(mediaElement.src, 'blob');
            uploadBlobToRepository(oggDataBlob, widget.dataset.recordingFilename);
        }
    }

    /**
     * Convert audio data to MP3.
     *
     * @param {string} sourceUrl URL from which to fetch the Ogg audio file to convert.
     * @returns {Promise<Blob>}
     */
    async function convertOggToMp3(sourceUrl) {
        const lamejs = await getLameJs();
        const oggData = await fetchOggData(sourceUrl, 'arraybuffer');
        const audioBuffer = await (new AudioContext()).decodeAudioData(oggData);
        const [left, right] = getRawAudioDataFromBuffer(audioBuffer);
        return await createMp3(lamejs, audioBuffer.numberOfChannels, audioBuffer.sampleRate, left, right);
    }

    /**
     * Helper to wrap loading the lamejs library.
     *
     * @returns {Promise<*>} access to the lamejs library.
     */
    async function getLameJs() {
        return await import(M.cfg.wwwroot + '/question/type/recordrtc/js/lamejs@1.2.1a-7-g582bbba/lame.min.js');
    }

    /**
     * Load Ogg data from a URL and return as an ArrayBuffer or a Blob.
     *
     * @param {string} sourceUrl URL from which to fetch the Ogg audio data.
     * @param {XMLHttpRequestResponseType} responseType 'arraybuffer' or 'blob'.
     * @returns {Promise<ArrayBuffer|Blob>} the audio data in the requested structure.
     */
    function fetchOggData(sourceUrl, responseType) {
        return new Promise((resolve) => {
            const fetchRequest = new XMLHttpRequest();
            fetchRequest.open('GET', sourceUrl);
            fetchRequest.responseType = responseType;
            fetchRequest.addEventListener('load', () => {
                resolve(fetchRequest.response);
            });
            fetchRequest.send();
        });
    }

    /**
     * Extract the raw sample data from an AudioBuffer.
     *
     * @param {AudioBuffer} audioIn an audio buffer, e.g. from a call to decodeAudioData.
     * @returns {Int16Array[]} for each audio channel, a Int16Array of the samples.
     */
    function getRawAudioDataFromBuffer(audioIn) {
        const channelData = [];

        for (let channel = 0; channel < audioIn.numberOfChannels; channel++) {
            const rawChannelData = audioIn.getChannelData(channel);
            channelData[channel] = new Int16Array(audioIn.length);
            for (let i = 0; i < audioIn.length; i++) {
                // This is not the normal code given for this conversion (which can be
                // found in git history) but this is 10x faster, and surely good enough.
                channelData[channel][i] = rawChannelData[i] * 0x7FFF;
            }
        }

        return channelData;
    }

    /**
     * Convert some audio data to MP3.
     *
     * @param {*} lamejs lamejs library from getLameJs().
     * @param {int} channels number of audio channels (1 or 2 supported).
     * @param {int} sampleRate sample rate of the audio to encode.
     * @param {Int16Array} left audio data for the left or only channel.
     * @param {Int16Array|null} right audio data for the right channel, if any.
     * @returns {Blob} representing an MP3 file.
     */
    async function createMp3(lamejs, channels, sampleRate, left, right = null) {
        const buffer = [];
        const mp3enc = new lamejs.Mp3Encoder(channels, sampleRate, mediaSettings.bitRate / 1000);
        let remaining = left.length;
        const samplesPerFrame = 1152;
        let mp3buf;

        await setPreparingPercent(0, left.length);
        for (let i = 0; remaining >= samplesPerFrame; i += samplesPerFrame) {
            if (channels === 1) {
                const mono = left.subarray(i, i + samplesPerFrame);
                mp3buf = mp3enc.encodeBuffer(mono);
            } else {
                const leftChunk = left.subarray(i, i + samplesPerFrame);
                const rightChunk = right.subarray(i, i + samplesPerFrame);
                mp3buf = mp3enc.encodeBuffer(leftChunk, rightChunk);
            }
            if (mp3buf.length > 0) {
                buffer.push(mp3buf);
            }
            remaining -= samplesPerFrame;
            if (i % (10 * samplesPerFrame) === 0) {
                await setPreparingPercent(i, left.length);
            }
        }
        const d = mp3enc.flush();
        if (d.length > 0) {
            buffer.push(new Int8Array(d));
        }
        await setPreparingPercent(left.length, left.length);

        return new Blob(buffer, {type: "audio/mp3"});
    }

    /**
     * Set the label on the upload button to a progress message including a percentage.
     *
     * @param {number} current number done so far.
     * @param {number} total number to do in total.
     */
    async function setPreparingPercent(current, total) {
        setButtonLabel('uploadpreparingpercent', Math.round(100 * current / total));
        // Next like is a hack to ensure the screen acutally updates.
        await new Promise(resolve => requestAnimationFrame(resolve));
    }

    /**
     * Upload the audio file to the Moodle draft file repository.
     *
     * @param {Blob} blob data to upload.
     * @param {string} recordingFilename the filename to use for the uplaod.
     */
    function uploadBlobToRepository(blob, recordingFilename) {

        // Create FormData to send to PHP filepicker-upload script.
        const formData = new FormData();
        formData.append('repo_upload_file', blob, recordingFilename);
        formData.append('sesskey', M.cfg.sesskey);
        formData.append('repo_id', uploadInfo.uploadRepositoryId);
        formData.append('itemid', uploadInfo.draftItemId);
        formData.append('savepath', '/');
        formData.append('ctx_id', uploadInfo.contextId);
        formData.append('overwrite', '1');

        const uploadRequest = new XMLHttpRequest();
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
        const uploadRequest = e.target;
        if (uploadRequest.readyState !== 4) {
            return; // Not finished yet. We will get more of these events when it is.
        }

        const response = JSON.parse(uploadRequest.responseText);
        if (response.errorcode) {
            handleUploadError(); // Moodle sends back errors with a 200 status code for some reason!
        }

        if (uploadRequest.status === 200) {
            // When request finished and successful.
            setButtonLabel('recordagainx');
            button.classList.remove('btn-outline-danger');
            enableAllButtons();
        } else if (uploadRequest.status === 404) {
            setPlaceholderMessage('uploadfailed404');
            enableAllButtons();
        }
    }

    /**
     * Callback for updating the upload progress.
     * @param {ProgressEvent} e
     */
    function handleUploadProgress(e) {
        setButtonLabel('uploadprogress', Math.round(e.loaded / e.total * 100) + '%');
    }

    /**
     * Callback for when the upload fails with an error.
     */
    function handleUploadError() {
        setPlaceholderMessage('uploadfailed');
        enableAllButtons();
    }

    /**
     * Callback for when the upload fails with an error.
     */
    function handleUploadAbort() {
        setPlaceholderMessage('uploadaborted');
        enableAllButtons();
    }

    /**
     * Change the label on the start/stop button.
     *
     * @param {string} langString
     * @param {string|null} [a] optional variable to populate placeholder with
     */
    function setButtonLabel(langString, a) {
        if (a === undefined) {
            // Seemingly unnecessary space inside the span is needed for screen-readers, and it must be a non-breaking space.
            a = '<span class="sr-only">&nbsp;' + widget.dataset.widgetName + '</span>';
        }
        button.innerHTML = M.util.get_string(langString, 'qtype_recordrtc', a);
    }

    /**
     * Change the label on the pause button.
     *
     * @param {string} langString
     */
    function setPauseButtonLabel(langString) {
        pauseButton.innerText = M.util.get_string(langString, 'qtype_recordrtc');
    }

    /**
     * Display a message in the upload progress area.
     *
     * @param {string} langString
     */
    function setPlaceholderMessage(langString) {
        noMediaPlaceholder.textContent = M.util.get_string(langString, 'qtype_recordrtc');
        mediaElement.parentElement.classList.add('hide');
        noMediaPlaceholder.classList.remove('hide');
    }

    /**
     * Select best options for the recording codec.
     *
     * @returns {Object}
     */
    function getRecordingOptions() {
        const options = {};

        // Get the relevant bit rates from settings.
        if (mediaSettings.name === 'audio') {
            options.audioBitsPerSecond = mediaSettings.bitRate;
        } else if (mediaSettings.name === 'video' || mediaSettings.name === 'screen') {
            options.videoBitsPerSecond = mediaSettings.bitRate;
            options.videoWidth = mediaSettings.width;
            options.videoHeight = mediaSettings.height;

            // Go through our list of mimeTypes, and take the first one that will work.
            for (let i = 0; i < mediaSettings.mimeTypes.length; i++) {
                if (MediaRecorder.isTypeSupported(mediaSettings.mimeTypes[i])) {
                    options.mimeType = mediaSettings.mimeTypes[i];
                    break;
                }
            }
        }

        return options;
    }

    /**
     * Enable all buttons in the question.
     */
    function enableAllButtons() {
        disableOrEnableButtons(true);
        owner.notifyButtonStatesChanged();
    }

    /**
     * Disable all buttons in the question.
     */
    function disableAllButtons() {
        disableOrEnableButtons(false);
    }

    /**
     * Disables/enables other question buttons when current widget started recording/finished recording.
     *
     * @param {boolean} enabled true if the button should be enabled.
     */
    function disableOrEnableButtons(enabled = false) {
        document.querySelectorAll('.que.recordrtc').forEach(record => {
            record.querySelectorAll('button, input[type=submit], input[type=button]').forEach(button => {
                button.disabled = !enabled;
            });
        });
    }

    /**
     * Pause/resume the progressbar state.
     */
    function toggleProgressbarState() {
        const running = progressBar.style.animationPlayState || 'running';
        progressBar.style.animationPlayState = running === 'running' ? 'paused' : 'running';
    }
}

/**
 * Object that controls the settings for recording audio.
 *
 * @param {string} bitRate desired audio bitrate.
 * @constructor
 */
function AudioSettings(bitRate) {
    this.name = 'audio';
    this.bitRate = parseInt(bitRate, 10);
    this.mediaConstraints = {
        audio: true
    };
    this.mimeTypes = [
        'audio/webm;codecs=opus',
        'audio/ogg;codecs=opus'
    ];
}

/**
 * Object that controls the settings for recording video.
 *
 * @param {string} bitRate desired video bitrate.
 * @param {string} width desired width.
 * @param {string} height desired height.
 * @constructor
 */
function VideoSettings(bitRate, width, height) {
    this.name = 'video';
    this.bitRate = parseInt(bitRate, 10);
    this.width = parseInt(width, 10);
    this.height = parseInt(height, 10);
    this.mediaConstraints = {
        audio: true,
        video: {
            width: {ideal: this.width},
            height: {ideal: this.height}
        }
    };
    this.mimeTypes = [
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=h264,opus',
        'video/webm;codecs=vp8,opus'
    ];
}

/**
 * Object that controls the settings for recording screen.
 *
 * @param {string} bitRate desired screen bitrate.
 * @param {string} width desired width.
 * @param {string} height desired height.
 * @constructor
 */
function ScreenSettings(bitRate, width, height) {
    this.name = 'screen';
    this.bitRate = parseInt(bitRate, 10);
    this.width = parseInt(width, 10);
    this.height = parseInt(height, 10);
    this.mediaConstraints = {
        audio: true,
        systemAudio: 'exclude',
        video: {
            displaySurface: 'monitor',
            frameRate: {ideal: 24},
            // Currently, Safari does not support ideal constraints for width and height with screen sharing feature.
            // It may be supported in version 16.4.
            width: {max: this.width},
            height: {max: this.height},
        }
    };

    // We use vp8 as the default codec. If it is not supported, we will switch to another codec.
    this.mimeTypes = [
        'video/webm;codecs=vp8,opus',
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=h264,opus',
    ];
}

/**
 * Represents one record audio or video question.
 *
 * @param {string} questionId id of the outer question div.
 * @param {Object} settings like audio bit rate.
 * @constructor
 */
function RecordRtcQuestion(questionId, settings) {
    const questionDiv = document.getElementById(questionId);

    // Check if the RTC API can work here.
    const result = checkCanWork();
    if (result === 'nothttps') {
        questionDiv.querySelector('.https-warning').classList.remove('hide');
        return;
    } else if (result === 'nowebrtc') {
        questionDiv.querySelector('.no-webrtc-warning').classList.remove('hide');
        return;
    }

    // Make the callback functions available.
    this.showAlert = showAlert;
    this.notifyRecordingComplete = notifyRecordingComplete;
    this.notifyButtonStatesChanged = setSubmitButtonState;
    const thisQuestion = this;

    // We may have more than one widget in a question.
    questionDiv.querySelectorAll('.qtype_recordrtc-audio-widget, .qtype_recordrtc-video-widget, .qtype_recordrtc-screen-widget')
        .forEach(function(widget) {
            // Get the appropriate options.
            let typeInfo;
            switch (widget.dataset.mediaType) {
                case 'audio':
                    typeInfo = new AudioSettings(settings.audioBitRate);
                    break;
                case 'screen':
                    typeInfo = new ScreenSettings(settings.screenBitRate, settings.screenWidth, settings.screenHeight);
                    break;
                default:
                    typeInfo = new VideoSettings(settings.videoBitRate, settings.videoWidth, settings.videoHeight);
                    break;
            }

            // Create the recorder.
            new Recorder(widget, typeInfo, thisQuestion, settings);
            return 'Not used';
        });
    setSubmitButtonState();

    /**
     * Set the state of the question's submit button.
     *
     * If any recorder does not yet have a recording, then disable the button.
     * Otherwise, enable it.
     */
    function setSubmitButtonState() {
        let anyRecorded = false;
        questionDiv.querySelectorAll('.qtype_recordrtc-audio-widget, .qtype_recordrtc-video-widget, .qtype_recordrtc-screen-widget')
            .forEach(function(widget) {
                if (widget.dataset.state === 'recorded') {
                    anyRecorded = true;
                }
            });
        const submitButton = questionDiv.querySelector('input.submit[type=submit]');
        if (submitButton) {
            submitButton.disabled = !anyRecorded;
        }
    }

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
     * Callback called when the recording is completed.
     *
     * @param {Recorder} recorder the recorder.
     */
    function notifyRecordingComplete(recorder) {
        recorder.uploadMediaToServer();
    }
}

/**
 * Initialise a record audio or video question.
 *
 * @param {string} questionId id of the outer question div.
 * @param {Object} settings like audio bit rate.
 */
function init(questionId, settings) {
    M.util.js_pending('init-' + questionId);
    new RecordRtcQuestion(questionId, settings);
    M.util.js_complete('init-' + questionId);
}

export {
    init
};
