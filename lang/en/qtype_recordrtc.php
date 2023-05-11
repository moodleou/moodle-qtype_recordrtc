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

$string['allowpausing'] = 'Allow pausing';
$string['allowpausing_help'] = 'If enabled, an extra \'pause\' button will be added to the recording widgets.';
$string['audio'] = 'Single audio';
$string['audiobitrate'] = 'Audio bitrate';
$string['audiobitrate_desc'] = 'Quality of audio recording (larger number means higher quality).';
$string['audiotimelimit'] = 'Max audio recording duration';
$string['audiotimelimit_desc'] = 'Maximum time that a question author can set for the audio recording length.';
$string['avplaceholder'] = 'Widget placeholders';
$string['avplaceholder_help'] = 'Place one or more recording widgets anywhere in the question text. You can copy the examples here.

Each placeholder requires, in double square brackets, a unique name (e.g. \'recorder1\'), a type (\'audio\' or \'video\' or \'screen\') and an optional duration, separated by colons. The duration should be expressed like \'30s\' or \'05m45s\'. When no duration is set, the widget will default to the maximum recording duration.

You may be able to apply formatting to the widget, such as changing its alignment or placing it in a table.';
$string['canselfcomment'] = 'Students can self-comment';
$string['canselfcomment_help'] = 'If enabled, students will be able to comment on their own attempt at the question.';
$string['canselfrate'] = 'Students can self-rate';
$string['canselfrate_help'] = 'If enabled, students will be able to rate on their own attempt at the question on a five-point star rating scale (rather that waiting for someone else to assess their work, if grades are relevant).';
$string['customav'] = 'Multiple widgets';
$string['downloadrecording'] = 'Download {$a}';
$string['err_audiotimelimit'] = 'Maximum audio recording duration cannot be greater than {$a} seconds.';
$string['err_closesquarebrackets'] = 'Missing close square bracket(s). {$a->format}';
$string['err_opensquarebrackets'] = 'Missing open square bracket(s). {$a->format}';
$string['err_placeholderformat'] = 'The placeholder format is either [[name:audio:duration]] or [[name:video:duration]], where name can only contain lower-case letters, numbers, hyphens and underscores, and must be no more than 32 characters long. The duration is optional and should be like \'01m20s\', \'02m\' or \'45s\'.';
$string['err_placeholderincorrectformat'] = 'A placeholder in the question text is not in the correct format.';
$string['err_placeholdermediatype'] = 'Widget type ‘{$a->text}’ is not valid.';
$string['err_placeholderneeded'] = 'You must add at least one placeholder to the question text.';
$string['err_placeholdermissingduration'] = '{$a} is missing duration. Only include the last colon if a duration is given. The colon has been removed for you.';
$string['err_placeholdernotallowed'] = 'You cannot use placeholders with Recording type {$a}.';
$string['err_placeholdertitle'] = '‘{$a->text}’ is not a valid name.';
$string['err_placeholdertitlecase'] = '‘{$a->text}’ is not a valid name. Names must be lower-case letters, without spaces. This has been fixed for you.';
$string['err_placeholdertitleduplicate'] = '‘{$a->text}’ has been used more than once. Each name must be different.';
$string['err_placeholdertitlelength'] = '‘{$a->text}’ is longer than {$a->maxlength} characters.';
$string['err_screentimelimit'] = 'Maximum video screen recording duration cannot be greater than {$a} seconds.';
$string['err_timelimit'] = 'Maximum recording duration cannot be greater than {$a}.';
$string['err_timelimitpositive'] = 'Maximum recording duration must be greater than 0.';
$string['err_videotimelimit'] = 'Maximum video recording duration cannot be greater than {$a} seconds.';
$string['err_zeroornegativetimelimit'] = '‘{$a}’ is not a valid. Maximum recording duration must be greater than 0.';
$string['feedbackfor'] = 'Feedback for \'{$a}\'';
$string['feedbackheader'] = 'Per-input feedback';
$string['filex'] = 'File {$a}';
$string['gumabort'] = 'Something strange happened which prevented the webcam/microphone from being used';
$string['gumabort_title'] = 'Something happened';
$string['gumnotallowed'] = 'The user must allow the browser access to the webcam/microphone/screen';
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
$string['mediatype'] = 'Type of recording';
$string['mediatype_help'] = 'Is the student being asked to record only one audio, only one video, or number of audios and/or videos.

<b>Single audio:</b> An audio recorder/player will be displayed at the bottom of the question text.

<b>Single video:</b> A video recorder/player will be displayed at the bottom of the question text.

<b>Single screen-recording:</b> A screen recorder/player will be displayed at the bottom of the question text.

<b>Multiple widgets:</b> placeholders for any number of the above sorts of inputs can be added to the question text. For example [[name1:audio]] or [[name2:video]] or [[name3:screen]]. The names must all be different and become the file names of the recordings.';
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
$string['optionsforscreen'] = 'Screen recording options';
$string['pause'] = 'Pause';
$string['pleaserecordsomethingineachpart'] = 'Please complete your answer.';
$string['pluginname'] = 'Record audio/video';
$string['pluginname_help'] = 'Students respond to the question text by recording audio, video or screen-recording (or a mixture of any combination if you select the \'Multiple widgets\' option) directly into their browser. This can then be graded manually, or by self-assessment if you have installed The Open University\'s free optional behaviour plugin.';
$string['pluginname_link'] = 'question/type/recordrtc';
$string['pluginnameadding'] = 'Adding a record audio/video question';
$string['pluginnameediting'] = 'Editing a record audio/video question';
$string['pluginnamesummary'] = 'Students respond to the question text by recording audio, video or screen-recording (or a mixture of any combination if you select the \'Multiple widgets\' option) directly into their browser. This can then be graded manually, or by self-assessment if you have installed The Open University\'s free optional behaviour plugin.';
$string['privacy:metadata'] = 'Record audio/video question type plugin allows question authors to set default options as user preferences.';
$string['privacy:preference:allowpausing'] = 'The \'Pause\' button is disabled/enabled for a given question.';
$string['privacy:preference:canselfcomment'] = 'The \'self commenting\' functionality is disabled/enabled for a given question.';
$string['privacy:preference:canselfrate'] = 'The \'self rating\' functionality is disabled/enabled for a given question.';
$string['privacy:preference:defaultmark'] = 'The default mark set for a given question.';
$string['privacy:preference:mediatype'] = 'Whether media type is set to \'Single audio\', \'Single video\' or \'Multiple widgets\'';
$string['privacy:preference:timelimitinseconds'] = 'The \'Maximum recording duration\' set for a given question.';
$string['recordagain'] = 'Re-record';
$string['recordagainx'] = 'Re-record {$a}';
$string['recordingfailed'] = 'Recording failed';
$string['recordrtc:downloadrecordings'] = 'Download recordings';
$string['resume'] = 'Resume';
$string['screen'] = 'Single screen-recording';
$string['screenbitrate'] = 'Screen bitrate';
$string['screenbitrate_desc'] = 'Quality of Screen recording (larger number means higher quality)';
$string['screentimelimit'] = 'Max screen recording duration';
$string['screentimelimit_desc'] = 'Maximum time that a question author can set for the screen recording length.';
$string['screensize'] = 'Video output resolution';
$string['screensize_desc'] = 'The size of the video output resolution.';
$string['selfassessmentheading'] = 'Settings for self-assessment';
$string['startcamera'] = 'Start camera';
$string['startcamerax'] = 'Start camera {$a}';
$string['startrecording'] = 'Start recording';
$string['startrecordingx'] = 'Start recording {$a}';
$string['stoprecording'] = 'Stop recording';
$string['startsharescreen'] = 'Start screen recording';
$string['startsharescreenx'] = 'Start screen recording {$a}';
$string['timedisplay'] = '{$a->mins}:{$a->secs}';
$string['timelimit'] = 'Maximum recording duration';
$string['timelimit_help'] = 'This is the longest duration of a recording that the student is allowed to make. If they reach this time, the recording will automatically stop. There is an upper limit to the value that can be set here. If you need a longer time, ask an administrator.';
$string['updateform'] = 'Update the form';
$string['updateformdone'] = 'The form was updated. You can add per-input feedback below.';
$string['uploadaborted'] = 'Saving aborted';
$string['uploadcomplete'] = 'Recording uploaded';
$string['uploadfailed'] = 'Upload failed';
$string['uploadfailed404'] = 'Upload failed (file too big?)';
$string['uploadpreparing'] = 'Preparing upload ...';
$string['uploadpreparingpercent'] = 'Preparing upload ({$a}%)';
$string['uploadprogress'] = 'Uploading ({$a})';
$string['video'] = 'Single video';
$string['videobitrate'] = 'Video bitrate';
$string['videobitrate_desc'] = 'Quality of video recording (larger number means higher quality)';
$string['videosize'] = 'Video size';
$string['videosize_desc'] = 'The size of the video.';
$string['videotimelimit'] = 'Max video recording duration';
$string['videotimelimit_desc'] = 'Maximum time that a question author can set for the video recording length.';
$string['xminutes'] = '{$a} minutes';
$string['xseconds'] = '{$a} seconds';
