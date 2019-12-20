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

define(['jquery'], function($) {

    var type = null, // Will be 'audio' or 'video'.
        questionDiv = null,
        startStopButton = null,
        settings = {
            audioBitRate: null,
            timeLimit: null,
            maxUploadSize: null,
        };

    /**
     * A helper for making a Moodle alert appear.
     *
     * @param {String} subject Subject is the content of the alert (which error the alert is for).
     * @param {Function} onCloseEvent if present, this function is called when the alert is closed.
     */
    function showAlert(subject, onCloseEvent) {
        Y.use('moodle-core-notification-alert', function() {
            var dialogue = new M.core.alert({
                title: M.util.get_string(subject + '_title', 'qtype_recordrtc'),
                message: M.util.get_string(subject, 'qtype_recordrtc')
            });

            if (onCloseEvent) {
                dialogue.after('complete', onCloseEvent);
            }
        });
    }

    /**
     * Handle getUserMedia errors.
     *
     * @param {DOMException} error the error from the media system.
     * @param {Object} commonConfig
     */
    function handleGumErrors(error, commonConfig) {
        var btnLabel = M.util.get_string('recordingfailed', 'qtype_recordrtc'),
            treatAsStopped = function() {
                commonConfig.onMediaStopped(btnLabel);
            };

        // Changes 'CertainError' -> 'gumcertain' to match language string names.
        var stringName = 'gum' + error.name.replace('Error', '').toLowerCase();

        // After alert, proceed to treat as stopped recording, or close dialogue.
        showAlert(stringName, treatAsStopped);
    }

    /**
     * Select best options for the recording codec.
     *
     * @returns {Object}
     */
    function selectRecordingOptions() {
        var types, options;

        if (type === 'audio') {
            types = [
                'audio/webm;codecs=opus',
                'audio/ogg;codecs=opus'
            ];
            options = {
                audioBitsPerSecond: settings.audioBitRate
            };
        } else {
            types = [
                'video/webm;codecs=vp9,opus',
                'video/webm;codecs=h264,opus',
                'video/webm;codecs=vp8,opus'
            ];
            options = {
                audioBitsPerSecond: settings.audioBitRate,
                videoBitsPerSecond: 0 // TODO
            };
        }

        var compatTypes = types.filter(function(type) {
            return window.MediaRecorder.isTypeSupported(type);
        });

        if (compatTypes.length !== 0) {
            options.mimeType = compatTypes[0];
        }
        return options;
    }

    /**
     * Verify that the question type can work. If not, show a warning.
     *
     * @return {Boolean} true if can work, else false.
     */
    function checkCanWork() {
        if (!(navigator.mediaDevices && window.MediaRecorder)) {
            questionDiv.find('.no-webrtc-warning').removeClass('hide');
            return false;
        }

        if (!(window.location.protocol === 'https:' || window.location.host.indexOf('localhost') !== -1)) {
            questionDiv.find('.https-warning').removeClass('hide');
            return false;
        }

        return true;
    }

    var t = {
        commonmodule: {
            // Uninitialized variables to be used by the other modules.
            saveFileUrl: null,
            countdownSeconds: null,
            countdownTicker: null,
            stream: null,
            mediaRecorder: null,
            chunks: null,
            blobSize: null,

            // Capture webcam/microphone stream.
            captureUserMedia: function(mediaConstraints, successCallback, errorCallback) {
                window.navigator.mediaDevices.getUserMedia(mediaConstraints).then(successCallback).catch(errorCallback);
            },

            // Add chunks of audio/video to array when made available.
            handleDataAvailable: function(event) {

                // Push recording slice to array.
                t.commonmodule.chunks.push(event.data);
                // Size of all recorded data so far.
                t.commonmodule.blobSize += event.data.size;

                // If total size of recording so far exceeds max upload limit, stop recording.
                // An extra condition exists to avoid displaying alert twice.
                if (t.commonmodule.blobSize >= settings.maxUploadSize) {
                    if (!window.localStorage.getItem('alerted')) {
                        window.localStorage.setItem('alerted', 'true');

                        startStopButton.simulate('click');
                        showAlert('nearingmaxsize');
                    } else {
                        window.localStorage.removeItem('alerted');
                    }

                    t.commonmodule.chunks.pop();
                }
            },

            // Handle recording end.
            handleStop: function() {
                // Set source of audio player.
                var blob = new window.Blob(t.commonmodule.chunks, {type: t.commonmodule.mediaRecorder.mimeType});
                var player = questionDiv.find('.media-player ' + type);

                player.attr('src', window.URL.createObjectURL(blob));

                // Show audio player with controls enabled, and unmute.
                player.attr('muted', false);
                player.attr('controls', true);
                player.closest('div').removeClass('hide');

                // TODO start upload to server.
                if (false) {
                    // Trigger error if no recording has been made.
                    if (t.commonmodule.chunks.length === 0) {
                        showAlert('norecordingfound');
                    } else {
                        // Upload recording to server.
                        t.commonmodule.uploadToServer(function(progress, fileURLOrError) {
                            if (progress === 'ended') { // Insert annotation in text.
                            } else if (progress === 'upload-failed') { // Show error message in upload button.
                                M.util.get_string('uploadfailed', 'qtype_recordrtc') + ' ' + fileURLOrError;
                            } else if (progress === 'upload-failed-404') { // 404 error = File too large in Moodle.
                                M.util.get_string('uploadfailed404', 'qtype_recordrtc');
                            } else if (progress === 'upload-aborted') {
                                M.util.get_string('uploadaborted', 'qtype_recordrtc') + ' ' + fileURLOrError;
                            } else {
                                progress;
                            }
                        });
                    }
                }
            },

            // Get everything set up to start recording.
            startRecording: function(stream) {
                // The options for the recording codecs and bit rates.
                var options = selectRecordingOptions();
                t.commonmodule.mediaRecorder = new window.MediaRecorder(stream, options);

                // Initialize MediaRecorder events and start recording.
                t.commonmodule.mediaRecorder.ondataavailable = t.commonmodule.handleDataAvailable;
                t.commonmodule.mediaRecorder.onstop = t.commonmodule.handleStop;
                t.commonmodule.mediaRecorder.start(1000); // Capture in 1s chunks. Must be set to work with Firefox.

                // Mute audio, distracting while recording.
                var player = questionDiv.find('.media-player ' + type);
                player.attr('muted', true);

                // Set recording timer to the time specified in the settings.
                t.commonmodule.countdownSeconds = settings.timeLimit;
                t.commonmodule.countdownSeconds++;
                var timerText = M.util.get_string('stoprecording', 'qtype_recordrtc');
                timerText += ' (<span id="minutes"></span>:<span id="seconds"></span>)';
                startStopButton.innerHtml = timerText;
                t.commonmodule.setTime();
                t.commonmodule.countdownTicker = window.setInterval(t.commonmodule.setTime, 1000);

                // Make button clickable again, to allow stopping recording.
                startStopButton.attr('disabled', false);
            },

            // Get everything set up to stop recording.
            stopRecording: function(stream) {
                // Stop recording stream.
                t.commonmodule.mediaRecorder.stop();

                // Stop each individual MediaTrack.
                var tracks = stream.getTracks();
                for (var i = 0; i < tracks.length; i++) {
                    tracks[i].stop();
                }
            },

            // Upload recorded audio/video to server.
            uploadToServer: function(callback) {
                var xhr = new window.XMLHttpRequest();

                // Get src media of audio/video tag.
                var player = questionDiv.find('.media-player ' + type);
                xhr.open('GET', player.attr('src'), true);
                xhr.responseType = 'blob';

                xhr.onload = function() {
                    if (xhr.status === 200) { // If src media was successfully retrieved.
                        // blob is now the media that the audio/video tag's src pointed to.
                        var blob = this.response;

                        // Generate filename with random ID and file extension.
                        var fileName = (Math.random() * 1000).toString().replace('.', '');
                        fileName += (type === 'audio') ? '-audio.ogg'
                            : '-video.webm';

                        // Create FormData to send to PHP filepicker-upload script.
                        var formData = new window.FormData(),
                            filepickerOptions = t.commonmodule.saveFileUrl,
                            repositoryKeys = window.Object.keys(filepickerOptions.repositories);

                        formData.append('repo_upload_file', blob, fileName);
                        formData.append('itemid', filepickerOptions.itemid);

                        for (var i = 0; i < repositoryKeys.length; i++) {
                            if (filepickerOptions.repositories[repositoryKeys[i]].type === 'upload') {
                                formData.append('repo_id', filepickerOptions.repositories[repositoryKeys[i]].id);
                                break;
                            }
                        }

                        formData.append('env', filepickerOptions.env);
                        formData.append('sesskey', M.cfg.sesskey);
                        formData.append('client_id', filepickerOptions.client_id);
                        formData.append('savepath', '/');
                        formData.append('ctx_id', filepickerOptions.context.id);

                        // Pass FormData to PHP script using XHR.
                        var uploadEndpoint = M.cfg.wwwroot + '/repository/repository_ajax.php?action=upload';
                        t.commonmodule.makeXmlHttpRequest(uploadEndpoint, formData,
                            function(progress, responseText) {
                                if (progress === 'upload-ended') {
                                    callback('ended', window.JSON.parse(responseText).url);
                                } else {
                                    callback(progress);
                                }
                            }
                        );
                    }
                };

                xhr.send();
            },

            // Handle XHR sending/receiving/status.
            makeXmlHttpRequest: function(url, data, callback) {
                var xhr = new window.XMLHttpRequest();

                xhr.onreadystatechange = function() {
                    if ((xhr.readyState === 4) && (xhr.status === 200)) { // When request is finished and successful.
                        callback('upload-ended', xhr.responseText);
                    } else if (xhr.status === 404) { // When request returns 404 Not Found.
                        callback('upload-failed-404');
                    }
                };

                xhr.upload.onprogress = function(event) {
                    callback(Math.round(event.loaded / event.total * 100) + "% " +
                        M.util.get_string('uploadprogress', 'qtype_recordrtc'));
                };

                xhr.upload.onerror = function(error) {
                    callback('upload-failed', error);
                };

                xhr.upload.onabort = function(error) {
                    callback('upload-aborted', error);
                };

                // POST FormData to PHP script that handles uploading/saving.
                xhr.open('POST', url);
                xhr.send(data);
            },

            // Makes 1min and 2s display as 1:02 on timer instead of 1:2, for example.
            pad: function(val) {
                var valString = val + "";

                if (valString.length < 2) {
                    return "0" + valString;
                } else {
                    return valString;
                }
            },

            // Functionality to make recording timer count down.
            // Also makes recording stop when time limit is hit.
            setTime: function() {
                t.commonmodule.countdownSeconds--;

                startStopButton.one('span#seconds').innerText =
                    t.commonmodule.pad(t.commonmodule.countdownSeconds % 60);
                startStopButton.one('span#minutes').innerText =
                    t.commonmodule.pad(window.parseInt(t.commonmodule.countdownSeconds / 60, 10));

                if (t.commonmodule.countdownSeconds === 0) {
                    startStopButton.simulate('click');
                }
            }
        },

        audiomodule: {
            init: function() {
                // Run when user clicks on "record" button.
                startStopButton.on('click', function(e) {
                    startStopButton.attr('disabled', true);

                    // If button is displaying "Start Recording" or "Record Again".
                    if ((startStopButton[0].innerText === M.util.get_string('startrecording', 'qtype_recordrtc')) ||
                        (startStopButton[0].innerText === M.util.get_string('recordagain', 'qtype_recordrtc')) ||
                        (startStopButton[0].innerText === M.util.get_string('recordingfailed', 'qtype_recordrtc'))) {

                        // Make sure the audio player and upload button are not shown.
                        var player = questionDiv.find('.media-player ' + type);
                        player.closest('div').addClass('hide');

                        // Change look of recording button.
                        startStopButton.removeClass('btn-outline-danger');
                        startStopButton.addClass('btn-danger');

                        // Empty the array containing the previously recorded chunks.
                        t.commonmodule.chunks = [];
                        t.commonmodule.blobSize = 0;

                        // Initialize common configurations.
                        var commonConfig = {
                            // When the stream is captured from the microphone/webcam.
                            onMediaCaptured: function(stream) {
                                // Make audio stream available at a higher level by making it a property of the common module.
                                t.commonmodule.stream = stream;
                                t.commonmodule.startRecording(t.commonmodule.stream);
                            },

                            // Revert button to "Record Again" when recording is stopped.
                            onMediaStopped: function(btnLabel) {
                                startStopButton.innerText = btnLabel;
                                startStopButton.attr('disabled', false);
                                startStopButton.removeClass('btn-danger');
                                startStopButton.addClass('btn-outline-danger');
                            },

                            // Handle recording errors.
                            onMediaCapturingFailed: function(error) {
                                handleGumErrors(error, commonConfig);
                            }
                        };

                        // Capture audio stream from microphone.
                        t.audiomodule.captureAudio(commonConfig);
                    } else { // If button is displaying "Stop Recording".
                        // First of all clears the countdownTicker.
                        window.clearInterval(t.commonmodule.countdownTicker);

                        // Disable "Record Again" button for 1s to allow background processing (closing streams).
                        window.setTimeout(function() {
                            startStopButton.attr('disabled', false);
                        }, 1000);

                        // Stop recording.
                        t.commonmodule.stopRecording(t.commonmodule.stream);

                        // Change button to offer to record again.
                        startStopButton.innerText = M.util.get_string('recordagain', 'qtype_recordrtc');
                        startStopButton.removeClass('btn-danger');
                        startStopButton.addClass('btn-outline-danger');
                    }
                });
            },

            // Setup to get audio stream from microphone.
            captureAudio: function(config) {
                t.commonmodule.captureUserMedia(
                    // Media constraints.
                    {
                        audio: true
                    },

                    // Success callback.
                    function(audioStream) {
                        var player = questionDiv.find('.media-player ' + type);
                        player[0].srcObject = audioStream;
                        config.onMediaCaptured(audioStream);
                    },

                    // Error callback.
                    function(error) {
                        config.onMediaCapturingFailed(error);
                    }
                );
            }
        },

        videomodule: {
            init: function() {
                // Run when user clicks on "record" button.
                startStopButton.on('click', function() {
                    startStopButton.attr('disabled', true);

                    // If button is displaying "Start Recording" or "Record Again".
                    if ((startStopButton.innerText === M.util.get_string('startrecording', 'qtype_recordrtc')) ||
                        (startStopButton.innerText === M.util.get_string('recordagain', 'qtype_recordrtc')) ||
                        (startStopButton.innerText === M.util.get_string('recordingfailed', 'qtype_recordrtc'))) {

                        // Change look of recording button.
                        startStopButton.removeClass('btn-outline-danger');
                        startStopButton.addClass('btn-danger');

                        // Empty the array containing the previously recorded chunks.
                        t.commonmodule.chunks = [];
                        t.commonmodule.blobSize = 0;

                        // Initialize common configurations.
                        var commonConfig = {
                            onMediaCaptured: function(stream) {
                                // Make video stream available at a higher level by making it a property of the common module.
                                t.commonmodule.stream = stream;
                                t.commonmodule.stream = stream;

                                t.commonmodule.startRecording(t.commonmodule.stream);
                            },

                            // Revert button to "Record Again" when recording is stopped.
                            onMediaStopped: function(btnLabel) {
                                startStopButton.innerText = btnLabel;
                                startStopButton.attr('disabled', false);
                                startStopButton.removeClass('btn-danger');
                                startStopButton.addClass('btn-outline-danger');
                            },

                            // Handle recording errors.
                            onMediaCapturingFailed: function(error) {
                                handleGumErrors(error, commonConfig);
                            }
                        };

                        // Show video tag without controls to view webcam stream.
                        var player = questionDiv.find('.media-player ' + type);
                        player.parent().parent().removeClass('hide');
                        player.attr('controls', false);

                        // Capture audio+video stream from webcam/microphone.
                        t.videomodule.captureAudioVideo(commonConfig);
                    } else { // If button is displaying "Stop Recording".
                        // First of all clears the countdownTicker.
                        window.clearInterval(t.commonmodule.countdownTicker);

                        // Disable "Record Again" button for 1s to allow background processing (closing streams).
                        window.setTimeout(function() {
                            startStopButton.attr('disabled', false);
                        }, 1000);

                        // Stop recording.
                        t.commonmodule.stopRecording(t.commonmodule.stream);

                        // Change button to offer to record again.
                        startStopButton.innerText = M.util.get_string('recordagain', 'qtype_recordrtc');
                        startStopButton.removeClass('btn-danger');
                        startStopButton.addClass('btn-outline-danger');
                    }
                });
            },

            // Setup to get audio+video stream from microphone/webcam.
            captureAudioVideo: function(config) {
                t.commonmodule.captureUserMedia(
                    // Media constraints.
                    {
                        audio: true,
                        video: {
                            width: {ideal: 640},
                            height: {ideal: 480}
                        }
                    },

                    // Success callback.
                    function(audioVideoStream) {
                        var player = questionDiv.find('.media-player ' + type);
                        // Set video player source to microphone+webcam stream, and play it back as it's recording.
                        player[0].srcObject = audioVideoStream;
                        player[0].play();

                        config.onMediaCaptured(audioVideoStream);
                    },

                    // Error callback.
                    function(error) {
                        config.onMediaCapturingFailed(error);
                    }
                );
            }
        },

        init: function(questionId) {
            type = 'audio';
            questionDiv = $(document.getElementById(questionId));

            if (!checkCanWork()) {
                return;
            }

            var settingsDiv = questionDiv.find('.record-button');
            settings.audioBitRate = settingsDiv.data('audioBitRate');
            settings.timeLimit = settingsDiv.data('timeLimit');
            settings.maxUploadSize = settingsDiv.data('maxUploadSize');

            startStopButton = questionDiv.find('.record-button button');

            M.util.js_pending('init-' + questionId);
            t.audiomodule.init();
            M.util.js_complete('init-' + questionId);
        }
    };

    return t;
});
