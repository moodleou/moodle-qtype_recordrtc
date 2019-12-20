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

        // TODO get right max-upload-size.
        $uploadfilesizelimit = 100000000;

        // TODO get URL of existing file, if there is one.
        $recordingurl = '';

        // Recording UI.
        $result .= '
        <div class="container-fluid" data-audio-bitrate="' .
                    get_config('qtype_recordrtc', 'audiobitrate') .
                    '" data-timelimit="' . get_config('qtype_recordrtc', 'timelimit') .
                    '" data-max-upload-size="' . $uploadfilesizelimit . '">
            <div class="row hide">
                <div class="col-xs-12">
                    <div id="alert-danger" class="alert alert-danger">
                        <strong>Insecure connection!</strong>
                        Your browser might not allow this plugin to work unless it is used either over HTTPS or from localhost</div>
                </div>
            </div>
            <div class="row hide">
                <div class="col-xs-1"></div>
                <div class="col-xs-10"><audio id="player">
                    <source src="' . $recordingurl . '">
                </audio></div>
                <div class="col-xs-1"></div>
            </div>
            <div class="row">
                <div class="col-xs-1"></div>
                <div class="col-xs-10">
                    <button id="start-stop" class="btn btn-lg btn-outline-danger btn-block">Start recording</button>
                </div>
                <div class="col-xs-1"></div>
            </div>
        </div>';

        $PAGE->requires->strings_for_js($this->strings_for_js(), 'qtype_recordrtc');
        $PAGE->requires->js_call_amd('qtype_recordrtc/avrecording', 'init', [$qa->get_outer_question_div_unique_id()]);

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
            'nowebrtc_title',
            'nowebrtc',
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
            'insecurealert_title',
            'insecurealert',
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
