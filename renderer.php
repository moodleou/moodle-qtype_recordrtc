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
        $output = '';

        $existingfiles = $qa->get_last_qt_files('recording', $options->context->id);
        if (!$options->readonly) {
            // Prepare a draft file area to store the recordings.
            $draftitemid = $qa->prepare_response_files_draft_itemid('recording', $options->context->id);

            // Add a hidden form field with the draft item id.
            $output .= html_writer::empty_tag('input', ['type' => 'hidden',
                    'name' => $qa->get_qt_field_name('recording'), 'value' => $draftitemid]);

            // Warning for browsers that won't work.
            $output .= $this->cannot_work_warnings();
        }

        if ($qa->get_state() == question_state::$invalid) {
            $output .= html_writer::nonempty_tag('div',
                    $question->get_validation_error([]), ['class' => 'validationerror']);
        }

        // Replace all the placeholders with the corresponding recording or player widget.
        $questiontext = $question->format_questiontext($qa);
        foreach ($question->widgetplaceholders as $placeholder => $filename) {
            $existingfile = $question->get_file_from_response($filename, $existingfiles);

            if ($options->readonly) {
                if ($existingfile) {
                    $thisitem = $this->playback_ui($qa->get_response_file_url($existingfile));
                } else {
                    $thisitem = $this->no_recording_message();
                }
            } else {

                if ($existingfile) {
                    $recordingurl = moodle_url::make_draftfile_url($draftitemid, '/', $filename);
                    $state = 'recorded';
                    $label = get_string('recordagain', 'qtype_recordrtc');
                } else {
                    $recordingurl = null;
                    $state = 'new';
                    $label = get_string('startrecording', 'qtype_recordrtc');
                }

                // Recording UI.
                $thisitem = $this->recording_ui($filename, $recordingurl, $state, $label);
            }

            $questiontext = str_replace($placeholder, $thisitem, $questiontext);
        }

        $output .= html_writer::tag('div', $questiontext, ['class' => 'qtext']);

        if (!$options->readonly) {
            // Initialise the JavaScript.
            $repositories = repository::get_instances(
                    ['type' => 'upload', 'currentcontext' => $options->context->id]);
            if (empty($repositories)) {
                throw new moodle_exception('errornouploadrepo', 'moodle');
            }
            $uploadrepository = reset($repositories); // Get the first (and only) upload repo.

            $setting = [
                    'audioBitRate' => (int) get_config('qtype_recordrtc', 'audiobitrate'),
                    'videoBitRate' => (int) get_config('qtype_recordrtc', 'videobitrate'),
                    'timeLimit' => (int) $question->timelimitinseconds,
                    'maxUploadSize' => $question->get_upload_size_limit($options->context),
                    'uploadRepositoryId' => (int) $uploadrepository->id,
                    'contextId' => $options->context->id,
                    'draftItemId' => $draftitemid,
            ];
            $PAGE->requires->strings_for_js($this->strings_for_js(), 'qtype_recordrtc');
            $PAGE->requires->js_call_amd('qtype_recordrtc/avrecording', 'init',
                    [$qa->get_outer_question_div_unique_id(), $setting, $question->mediatype]);
        }

        return $output;
    }

    /**
     * These messages are hidden unless revealed by the JavaScript.
     *
     * @return string HTML for the 'this can't work here' messages.
     */
    protected function cannot_work_warnings() {
        return '
                <div class="hide alert alert-danger https-warning">
                    <h5>' . get_string('insecurewarningtitle', 'qtype_recordrtc') . '</h5>
                    <p>' . get_string('insecurewarning', 'qtype_recordrtc') . '</p>
                </div>
                <div class="hide alert alert-danger no-webrtc-warning">
                    <h5>' . get_string('nowebrtctitle', 'qtype_recordrtc') . '</h5>
                    <p>' . get_string('nowebrtc', 'qtype_recordrtc') . '</p>
                </div>';
    }

    /**
     * Generate the HTML for the recording UI.
     *
     * Note: the JavaScript relies on a lot of the CSS class names here.
     *
     * @param string $filename the filename to use for this recording.
     * @param moodle_url|null $recordingurl URL for the recording, if there is one, else null.
     * @param string $state value for the data-state attribute of the record button.
     * @param string $label label for the record button.
     * @return string HTML to output.
     */
    protected function recording_ui(string $filename, ?moodle_url $recordingurl,
            string $state, string $label) {
        if ($recordingurl) {
            $mediaplayerhideclass = '';
            $norecordinghideclass = 'hide ';
        } else {
            $mediaplayerhideclass = 'hide ';
            $norecordinghideclass = '';

        }

        return '
                <span class="record-widget" data-recording-filename="' . $filename .'">
                    <span class="' . $norecordinghideclass . 'no-recording-placeholder">' .
                        get_string('norecording', 'qtype_recordrtc') .
                    '</span>
                    <span class="' . $mediaplayerhideclass . 'media-player">
                        <audio controls>
                            <source src="' . $recordingurl . '">
                        </audio>
                    </span>
                    <span class="hide saving-message">
                        <small></small>
                    </span>
                    <span class="record-button">
                        <button type="button" class="btn btn-outline-danger osep-smallbutton" data-state="' . $state . '">' . $label . '</button>
                    </span>
                </span>';
    }

    /**
     * Render the playback UI - e.g. when the question is reviewed.
     *
     * @param string $recordingurl URL for the recording.
     * @return string HTML to output.
     */
    protected function playback_ui(string $recordingurl) {
        return '
                <span class="playback-widget">
                    <span class="media-player">
                        <audio controls>
                            <source src="' . $recordingurl . '">
                        </audio>
                    </span>
                </span>';
    }

    /**
     * Render a message to say there is no recording.
     *
     * @return string HTML to output.
     */
    protected function no_recording_message() {
        return '
                <span class="playback-widget">
                    <span class="no-recording-placeholder">' .
                        get_string('norecording', 'qtype_recordrtc') .
                    '</span>
                </span>';
    }

    /**
     * Strings our JS will need.
     *
     * @return string[] lang string names from the qtype_recordrtc lang file.
     */
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
