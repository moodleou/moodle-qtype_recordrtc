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
 * The record audio (and video) question type question renderer class.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');


/**
 * Generates output for record audio (and video) questions.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc_renderer extends qtype_renderer {

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        global $PAGE;
        $question = $qa->get_question();
        $existingresponsefiles = $qa->get_last_qt_files('recording', $options->context->id);
        $existingresponsefile = null;
        foreach ($existingresponsefiles as $file) {
            if ($file->get_filename() === qtype_recordrtc::AUDIO_FILENAME) {
                $existingresponsefile = $file;
                break;
            }
        }

        // Question text.
        $result = html_writer::tag('div', $question->format_questiontext($qa), ['class' => 'qtext']);

        if ($options->readonly) {
            if ($existingresponsefile) {
                // Playback UI.
                $recordingurl = $qa->get_response_file_url($existingresponsefile);
                $result .= '
                    <div class="media-player">
                        <audio controls>
                            <source src="' . $recordingurl . '">
                        </audio>
                    </div>';
            } else {
                // Message to say there is no recording.
                $result .= html_writer::div(get_string('norecording', 'qtype_recordrtc'),
                        'alert alert-secondary');
            }

        } else {
            $repositories = repository::get_instances(
                    ['type' => 'upload', 'currentcontext' => $options->context->id]);
            if (empty($repositories)) {
                throw new moodle_exception('errornouploadrepo', 'moodle');
            }
            $uploadrepository = reset($repositories); // Get the first (and only) upload repo.

            // Prepare a draft file area to store the recording.
            $draftitemid = $qa->prepare_response_files_draft_itemid(
                    'recording', $options->context->id);

            $recordingurl = '';
            $state = 'new';
            $label = get_string('startrecording', 'qtype_recordrtc');
            $mediaplayerinitiallyhidden = 'hide ';
            if ($existingresponsefile) {
                $recordingurl = moodle_url::make_draftfile_url($draftitemid, '/', qtype_recordrtc::AUDIO_FILENAME);
                $state = 'recorded';
                $label = get_string('recordagain', 'qtype_recordrtc');
                $mediaplayerinitiallyhidden = '';
            }

            // Recording UI.
            $result .= '
                <div class="hide alert alert-danger https-warning">
                    <h5>' . get_string('insecurewarningtitle', 'qtype_recordrtc') . '</h5>
                    <p>' . get_string('insecurewarning', 'qtype_recordrtc') . '</p>
                </div>
                <div class="hide alert alert-danger no-webrtc-warning">
                    <h5>' . get_string('nowebrtctitle', 'qtype_recordrtc') . '</h5>
                    <p>' . get_string('nowebrtc', 'qtype_recordrtc') . '</p>
                </div>
                <div class="' . $mediaplayerinitiallyhidden . 'media-player">
                    <audio controls>
                        <source src="' . $recordingurl . '">
                    </audio>
                </div>
                <div class="hide saving-message">
                    <small></small>
                </div>
                <div class="record-button">
                    <button type="button" class="btn btn-outline-danger" data-state="' . $state . '">' . $label . '</button>
                </div>';

            // Add a hidden form field with the draft item id.
            $result .= html_writer::empty_tag('input', ['type' => 'hidden',
                    'name' => $qa->get_qt_field_name('recording'), 'value' => $draftitemid]);

            // Initialise the JavaScript.
            $uploadfilesizelimit = $question->get_upload_size_limit($options->context);
            $setting = [
                'audioBitRate' => get_config('qtype_recordrtc', 'audiobitrate'),
                'videoBitRate' => get_config('qtype_recordrtc', 'videobitrate'),
                'timeLimit' => $question->timelimitinseconds,
                'maxUploadSize' => $uploadfilesizelimit,
                'uploadRepositoryId' => $uploadrepository->id,
                'contextId' => $options->context->id,
                'draftItemId' => $draftitemid,
            ];

            $PAGE->requires->strings_for_js($this->strings_for_js(), 'qtype_recordrtc');
            $PAGE->requires->js_call_amd('qtype_recordrtc/avrecording', 'init',
                    [$qa->get_outer_question_div_unique_id(), $setting, $question->mediatype]);
        }

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error([]), ['class' => 'validationerror']);
        }

        return $result;
    }

    public function strings_for_js() {
        return [
            'gumabort',
            'gumabort_title',
            'gumnotallowed',
            'gumnotallowed_title',
            'gumnotfound',
            'gumnotfound_title',
            'gumnotreadable',
            'gumnotreadable_title',
            'gumnotsupported',
            'gumnotsupported_title',
            'gumoverconstrained',
            'gumoverconstrained_title',
            'gumsecurity',
            'gumsecurity_title',
            'gumtype',
            'gumtype_title',
            'nearingmaxsize',
            'nearingmaxsize_title',
            'recordagain',
            'recordingfailed',
            'startrecording',
            'stoprecording',
            'uploadaborted',
            'uploadcomplete',
            'uploadfailed',
            'uploadfailed404',
            'uploadpreparing',
            'uploadprogress',
        ];
    }

    /**
     * Map icons for font-awesome themes.
     *
     * @return array of icon mappings.
     */
    public function qtype_recordrtc_get_fontawesome_icon_map() {
        return [
            'atto_recordrtc:i/audiortc' => 'fa-microphone',
            'atto_recordrtc:i/videortc' => 'fa-video-camera',
        ];
    }
}
