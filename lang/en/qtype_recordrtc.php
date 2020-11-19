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

$string['audio'] = 'Audio only';
$string['audiobitrate'] = 'Audio bitrate';
$string['audiobitrate_desc'] = 'Quality of audio recording (larger number means higher quality)';
$string['avplaceholder'] = 'Audio/video placeholders';
$string['avplaceholder_help'] = 'Place one or more of either recording widgets anywhere throughout your question text -
exemplars are listed, and are essentially a unique name followed by the widget type, separated by a colon.
It is important that each name is distinct, such as [[name:audio]], [[welcome:video]], [[age:audio]] etc.

You may be able to apply formatting to the widget, such as changing its alignment.';
$string['customav'] = 'Customised A/V';
$string['downloadrecording'] = 'Download {$a}';
$string['err_closesquarebrackets'] = 'Missing close square bracket(s). {$a->format}';
$string['err_multiplerecorders'] = 'Only one {$a} recorder allowed here, for  multiple recorders set the Recording widget to Customised A/V';
$string['err_opensquarebrackets'] = 'Missing open square bracket(s). {$a->format}';
$string['err_placeholderformat'] = 'The placeholder format is [[title:audio]] or [[title:video]], where title is a string and can contain lower-case letters, numbers, hyphens and underscores.';
$string['err_placeholderincorrectformat'] = 'A media placeholder is not in correct format. {$a->format}';
$string['err_placeholdermediatype'] = '"{$a->text}" is not valid. For \'Recording widget\' use the string "audio" or "video". {$a->format}';
$string['err_placeholderneeded'] = 'Recording widget "{$a}" requires customised media placeholders within the question text.';
$string['err_placeholdernotallowed'] = 'Recording widget "{$a}" does not allow customised media placeholders within the question text.';
$string['err_placeholdertitle'] = '"{$a->text}" is not a valid title. For title use a suitable string. {$a->format}';
$string['err_placeholdertitlecase'] = '"{$a->text}" is not a valid title. Titles may only contain lower-case letters. {$a->format}';
$string['err_placeholdertitleduplicate'] = '"{$a->text}" has been used more than once. Each title must be different.';
$string['err_placeholdertitlelength'] = '"{$a->text}" exceeds the max length of {$a->maxlength} for a title. {$a->format}';
$string['err_timelimit'] = 'Maximum recording duration cannot be greater than {$a}.';
$string['err_timelimitpositive'] = 'Maximum recording duration must be greater than 0.';
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
$string['mediatype'] = 'Recording widget';
$string['mediatype_help'] = 'Is the student being asked to record only one audio, only one video, or number of audios and/or videos.

<b>Audio only:</b> An audio recorder/player will be displayed at the bottom of the question text.

<b>Video only:</b> A video recorder/player will be displayed at the bottom of the question text.

<b>Customised A/V:</b> Question authors/editors provide required audio/video-placeholders within the question text, for instance, [[title_a1:audio]], [[title_v1:video]], where \'title_a1\' and \'title_v1\' are the titles and ultimately the recorded audio/video file names respectively.';
$string['insecurewarning'] = 'Your browser will not allow this plugin to work unless it is used over HTTPS.';
$string['insecurewarningtitle'] = 'Insecure connection';
$string['nearingmaxsize'] = 'You have attained the maximum size limit for file uploads';
$string['nearingmaxsize_title'] = 'Recording stopped';
$string['norecording'] = 'No recording';
$string['nowebrtc'] = 'Your browser offers limited or no support for WebRTC technologies yet, and cannot be used with this type of question. Please switch or upgrade your browser.';
$string['nowebrtctitle'] = 'WebRTC not supported';
$string['optionsforaudio'] = 'Audio options';
$string['optionsforaudioandvideo'] = 'Audio and video options';
$string['optionsforvideo'] = 'Video options';
$string['pleaserecordsomethingineachpart'] = 'Please complete your answer.';
$string['pluginname'] = 'Record audio/video';
$string['pluginname_help'] = 'Students respond to the question text by recording audio or video (or a mix of both if you select the \'Customised A/V\' option) directly into their browser. This can then be graded manually, or by self-assessment if you have installed The Open University\'s free optional behaviour plugin.';
$string['pluginname_link'] = 'question/type/recordrtc';
$string['pluginnameadding'] = 'Adding a record audio/video question';
$string['pluginnameediting'] = 'Editing a record audio/video question';
$string['pluginnamesummary'] = 'Students respond to the question text by recording audio or video (or a mix of both if you select the \'Customised A/V\' option) directly into their browser. This can then be graded manually, or by self-assessment if you have installed The Open University\'s free optional behaviour plugin.';
$string['privacy:metadata'] = 'The Record audio/video question type plugin does not store any personal data.';
$string['recordagain'] = 'Re-record';
$string['recordingfailed'] = 'Recording failed';
$string['recordinginprogress'] = 'Stop recording ({$a})';
$string['startrecording'] = 'Start recording';
$string['timelimit'] = 'Maximum recording duration';
$string['timelimit_desc'] = 'Maximum time that a question author can set for the recording length.';
$string['timelimit_help'] = 'This is the longest duration of a recording that the student is allowed to make. If they reach this time, the recording will automatically stop. There is an upper limit to the value that can be set here. If you need a longer time, ask an administrator.';
$string['uploadaborted'] = 'Saving aborted';
$string['uploadcomplete'] = 'Recording uploaded';
$string['uploadfailed'] = 'Upload failed';
$string['uploadfailed404'] = 'Upload failed (file too big?)';
$string['uploadpreparing'] = 'Preparing upload ...';
$string['uploadprogress'] = 'Uploading ({$a})';
$string['video'] = 'Video only';
$string['videobitrate'] = 'Video bitrate';
$string['videobitrate_desc'] = 'Quality of video recording (larger number means higher quality)';
$string['videosize'] = 'Video size';
$string['videosize_desc'] = 'The size of the video.';
