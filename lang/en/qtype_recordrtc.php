<?php
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

/**
 * Strings for component 'qtype_recordrtc', language 'en', branch 'MOODLE_38_STABLE'
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['audio'] = 'Audio';
$string['audiobitrate'] = 'Audio bitrate';
$string['audiobitrate_desc'] = 'Quality of audio recording (larger number means higher quality)';
$string['err_timliemit'] = 'The time limit cannot be greater than {$a}.';
$string['err_timliemitzero'] = 'The time limit cannot be zero.';
$string['filex'] = 'File {$a}';
$string['gumabort'] = 'Something strange happened which prevented the webcam/microphone from being used';
$string['gumabort_title'] = 'Something happened';
$string['gumnotallowed'] = 'The user must allow the browser access to the webcam/microphone';
$string['gumnotallowed_title'] = 'Wrong permissions';
$string['gumnotfound'] = 'There is no input device connected or enabled';
$string['gumnotfound_title'] = 'Device missing';
$string['gumnotreadable'] = 'Something is preventing the browser from accessing the webcam/microphone';
$string['gumnotreadable_title'] = 'Hardware error';
$string['gumnotsupported'] = 'Your browser does not support recording over an insecure connection and must close the plugin';
$string['gumnotsupported_title'] = 'No support for insecure connection';
$string['gumoverconstrained'] = 'The current webcam/microphone can not produce a stream with the required constraints';
$string['gumoverconstrained_title'] = 'Problem with constraints';
$string['gumsecurity'] = 'Your browser does not support recording over an insecure connection and must close the plugin';
$string['gumsecurity_title'] = 'No support for insecure connection';
$string['gumtype'] = 'Tried to get stream from the webcam/microphone, but no constraints were specified';
$string['gumtype_title'] = 'No constraints specified';
$string['mediatype'] = 'Media type';
$string['mediatype_dec'] = 'Currently this works only with audio, but video will be added soon.';
$string['mediatype_help'] = 'Is the student being asked to record audio or video.';
$string['insecurewarning'] = 'Your browser will not allow this plugin to work unless it is used over HTTPS.';
$string['insecurewarningtitle'] = 'Insecure connection';
$string['nearingmaxsize'] = 'You have attained the maximum size limit for file uploads';
$string['nearingmaxsize_title'] = 'Recording stopped';
$string['norecording'] = 'No recording';
$string['nowebrtc'] = 'Your browser offers limited or no support for WebRTC technologies yet, and cannot be used with this type of question. Please switch or upgrade your browser.';
$string['nowebrtctitle'] = 'WebRTC not supported';
$string['pleaserecordsomething'] = 'Please record something.';
$string['pluginname'] = 'Record audio';
$string['pluginname_help'] = 'Students respond by recording some audio directly in their web browser, which can then be graded manually.';
$string['pluginname_link'] = 'question/type/recordrtc';
$string['pluginnameadding'] = 'Adding a record audio question';
$string['pluginnameediting'] = 'Editing a record audio question';
$string['pluginnamesummary'] = 'Students respond by recording some audio directly in their web browser, which can then be graded manually.';
$string['privacy:metadata'] = 'The Record audio question type plugin does not store any personal data.';
$string['recordagain'] = 'Record again';
$string['recordingfailed'] = 'Recording failed, try again';
$string['startrecording'] = 'Start recording';
$string['stoprecording'] = 'Stop recording';
$string['timelimit'] = 'Maximum recording duration';
$string['timelimit_desc'] = 'Maximum time that a question author can set for the recording length.';
$string['timelimit_help'] = 'This is the longest duration of a recording that the student is allowed to make. If they reach this time, the recording will automatically stop. There is an upper limit to the value that can be set here. If you need a longer time, ask an administrator.';
$string['uploadaborted'] = 'Saving aborted';
$string['uploadcomplete'] = 'Recording saved';
$string['uploadfailed'] = 'Saving the recording failed';
$string['uploadfailed404'] = 'Saving the recording failed: the file is probably too large';
$string['uploadpreparing'] = 'Preparing to save ...';
$string['uploadprogress'] = 'Saving recording ({$a}) ...';
$string['video'] = 'Video';
$string['videobitrate'] = 'Video bitrate';
$string['videobitrate_desc'] = 'Quality of video recording (larger number means higher quality)';
