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

        // Prepare a draft file area to store the recording.
        $draftitemid = $qa->prepare_response_files_draft_itemid(
                'recording', $options->context->id);

        // Question text.
        $result = html_writer::tag('div', $question->format_questiontext($qa), ['class' => 'qtext']);

        // TODO get URL of existing file, if there is one.
        $recordingurl = '';

        // TODO use langauge strings for the warnings.

        // Recording UI.
        $result .= '
            <div class="hide alert alert-danger https-warning">
                <h5>' . get_string('insecurewarningtitle', 'qtype_recordrtc') . '</h5>
                <p>' . get_string('insecurewarning', 'qtype_recordrtc') . '</div>
            </div>
            <div class="hide alert alert-danger no-webrtc-warning">
                <h5>' . get_string('nowebrtctitle', 'qtype_recordrtc') . '</h5>
                <p>' . get_string('nowebrtc', 'qtype_recordrtc') . '</div>
            </div>
            <div class="hide media-player">
                <audio>
                    <source src="' . $recordingurl . '">
                </audio>
            </div>
            <div class="record-button">
                <button type="button" class="btn btn-outline-danger" data-state="new">' .
                        get_string('startrecording', 'qtype_recordrtc') . '</button>
            </div>';

        // TODO get right max-upload-size.
        $uploadfilesizelimit = 100000000;
        $setting = [
            'audioBitRate' => get_config('qtype_recordrtc', 'audiobitrate'),
            'videoBitRate' => 0, // TODO.
            'timeLimit' => get_config('qtype_recordrtc', 'time-limit'),
            'maxUploadSize' => $uploadfilesizelimit,
        ];

        $PAGE->requires->strings_for_js($this->strings_for_js(), 'qtype_recordrtc');
        $PAGE->requires->js_call_amd('qtype_recordrtc/avrecording', 'init',
                [$qa->get_outer_question_div_unique_id(), $setting]);

        // Add a hidden form field with the draft item id.
        $result .= html_writer::empty_tag('input', ['type' => 'hidden',
                'name' => $qa->get_qt_field_name('recording'), 'value' => $draftitemid]);

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error([]), ['class' => 'validationerror']);
        }

        return $result;
    }

    public function strings_for_js() {
        return ['audiortc',
            'videortc',
            'gumabort_title',
            'gumabort',
            'gumnotallowed_title',
            'gumnotallowed',
            'gumnotfound_title',
            'gumnotfound',
            'gumnotreadable_title',
            'gumnotreadable',
            'gumnotsupported',
            'gumnotsupported_title',
            'gumoverconstrained_title',
            'gumoverconstrained',
            'gumsecurity_title',
            'gumsecurity',
            'gumtype_title',
            'gumtype',
            'startrecording',
            'recordagain',
            'stoprecording',
            'recordingfailed',
            'attachrecording',
            'norecordingfound_title',
            'norecordingfound',
            'nearingmaxsize_title',
            'nearingmaxsize',
            'uploadprogress',
            'uploadfailed',
            'uploadfailed404',
            'uploadaborted'];
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
