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

    const PLUGINNAME = 'qtype_recordrtc';
    const TEMPLATE = '' .
    '<div class="qtype_recordrtc container-fluid">' .
      '<div class="bs_row hide">' .
        '<div class="bs_col12">' .
          '<div id="alert-danger" class="alert bs_al_dang">' .
            '<strong>{{insecurealert_title}}</strong> {{insecurealert}}' .
          '</div>' .
        '</div>' .
      '</div>' .
      '<div class="{{bs_row}} hide">' .
        '{{#if isAudio}}' .
          '<div class="{{bs_col}}1"></div>' .
          '<div class="{{bs_col}}10">' .
            '<audio id="player"></audio>' .
          '</div>' .
          '<div class="{{bs_col}}1"></div>' .
        '{{else}}' .
          '<div class="{{bs_col}}12">' .
            '<video id="player"></video>' .
          '</div>' .
        '{{/if}}' .
      '</div>' .
      '<div class="{{bs_row}}">' .
        '<div class="{{bs_col}}1"></div>' .
        '<div class="{{bs_col}}10">' .
          '<button id="start-stop" class="{{bs_ss_btn}}">startrecording</button>' .
        '</div>' .
        '<div class="{{bs_col}}1"></div>' .
      '</div>' .
      '<div class="{{bs_row}} hide">' .
        '<div class="{{bs_col}}3"></div>' .
        '<div class="{{bs_col}}6">' .
          '<button id="upload" class="btn btn-primary btn-block">{{attachrecording}}</button>' .
        '</div>' .
        '<div class="{{bs_col}}3"></div>' .
      '</div>' .
    '</div>';

    public function formulation_and_controls(question_attempt $qa,
                                             question_display_options $options) {
        global $PAGE;
        $question = $qa->get_question();
        $questiontext = $question->format_questiontext($qa);
        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));
        /* Some code to restore the state of the question as you move back and forth
        from one question to another in a quiz and some code to disable the input fields
        once a quesiton is submitted/marked */


        /* if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }*/

      $startrecordingbutton = "
        <div class= \"qtype_recordrtc container-fluid\" id=\"\">
            <div class=\"row hide\">
                <div class=\"col-xs-12\">
                    <div id=\"alert-danger\" class=\"alert alert-danger\"><strong>Insecure connection!</strong> Your browser might not allow this plugin to work unless it is used either over HTTPS or from localhost</div>
                </div>
            </div>
            <div class=\"row hide\">
                <div class=\"col-xs-1\"></div>
                <div class=\"col-xs-10\"><audio id=\"player\"></audio></div>
                <div class=\"col-xs-1\"></div>
            </div>
            <div class=\"row\" id=\"\">
                <div class=\"col-xs-1\"></div>
                <div class=\"col-xs-10\" id=\"\">
                    <button id=\"start-stop\" class=\"btn btn-lg btn-outline-danger btn-block\">Start recording</button>
                </div>
                <div class=\"col-xs-1\"></div>
            </div>
        </div>";

//      $stoprecording = "
//      <div class=\"atto_recordrtc container-fluid\" id=\"\">
//          <div class=\"row hide\">
//            <div class=\"col-xs-12\"><div id=\"alert-danger\" class=\"alert alert-danger\"><strong>Insecure connection!</strong> Your browser might not allow this plugin to work unless it is used either over HTTPS or from localhost</div></div></div><div class=\"row hide\"><div class=\"col-xs-1\"></div><div class=\"col-xs-10\"><audio id=\"player\"></audio></div><div class=\"col-xs-1\"></div></div><div class=\"row\" id=\"yui_3_17_2_1_1576749864475_1396\"><div class=\"col-xs-1\"></div><div class=\"col-xs-10\" id=\"yui_3_17_2_1_1576749864475_1395\"><button id=\"start-stop\" class=\"btn btn-lg btn-block btn-danger\">Stop recording (<span id=\"minutes\">01</span>:<span id=\"seconds\">49</span>)</button></div><div class=\"col-xs-1\"></div></div><div class=\"row hide\"><div class=\"col-xs-3\"></div><div class=\"col-xs-6\"><button id=\"upload\" class=\"btn btn-primary btn-block\">Attach recording</button></div><div class=\"col-xs-3\"></div></div>
//      </div>";

        $recordagainbutton = "
        <div class=\"atto_recordrtc container-fluid\" id=\"\">
            <div class=\"row hide\">
                <div class=\"col-xs-12\">
                    <div id=\"alert-danger\" class=\"alert alert-danger\"><strong>Insecure connection!</strong> Your browser might not allow this plugin to work unless it is used either over HTTPS or from localhost</div>
                </div>
             </div>
             <div class=\"row\" id=\"\">
                <div class=\"col-xs-1\" id=\"\"></div>
                <div class=\"col-xs-10\" id=\"\"><audio id=\"player\" src=\"blob:https://mk4359.vledev3.open.ac.uk/a0bc36d5-36a7-4303-8b99-b0e6e340aaa0\" controls=\"\"></audio></div>
                <div class=\"col-xs-1\"></div>
            </div>
            <div class=\"row\" id=\"\">
                <div class=\"col-xs-1\"></div>
                <div class=\"col-xs-10\" id=\"\"><button id=\"start-stop\" class=\"btn btn-lg btn-block btn-outline-danger\">Record again</button></div>
                <div class=\"col-xs-3\"></div>
            </div>
        </div>";

        //$PAGE->requires->strings_for_js($this->atto_recordrtc_strings_for_js(), 'atto_recordrtc');
        $PAGE->requires->js_call_amd('qtype_recordrtc/recordrtc', 'init', [$this->atto_recordrtc_strings_for_js()]);

        if ($qa->get_state_class(true) === 'notyetanswered') {
            $html = "
<div class='qtype_recordrtc container-fluid'>            
<audio width=\"800\" height=\"2\" controls>
  Your browser does not support the audio tag.
</audio>
</div>";            $result .= $html;
            $result .= $startrecordingbutton;
        } else {
            $result .= $recordagainbutton;
        }
        return $result;
    }

    public function specific_feedback(question_attempt $qa)
    {
        // TODO.
        return '';
    }

    public function correct_response(question_attempt $qa)
    {
        // TODO.
        return '';
    }


    /**
     * Set params for this plugin.
     *
     * @param string $elementid
     * @param stdClass $options - the options for the editor, including the context.
     * @param stdClass $fpoptions - unused.
     */
    public function atto_recordrtc_params_for_js($elementid, $options, $fpoptions)
    {
        $context = $options['context'];
        if (!$context) {
            $context = context_system::instance();
        }

        $sesskey = sesskey();
        $allowedtypes = get_config('atto_recordrtc', 'allowedtypes');
        $audiobitrate = get_config('atto_recordrtc', 'audiobitrate');
        $videobitrate = get_config('atto_recordrtc', 'videobitrate');
        $timelimit = get_config('atto_recordrtc', 'timelimit');

        // Update $allowedtypes to account for capabilities.
        $audioallowed = $allowedtypes === 'audio' || $allowedtypes === 'both';
        $videoallowed = $allowedtypes === 'video' || $allowedtypes === 'both';
        $audioallowed = $audioallowed && has_capability('atto/recordrtc:recordaudio', $context);
        $videoallowed = $videoallowed && has_capability('atto/recordrtc:recordvideo', $context);
        if ($audioallowed && $videoallowed) {
            $allowedtypes = 'both';
        } else if ($audioallowed) {
            $allowedtypes = 'audio';
        } else if ($videoallowed) {
            $allowedtypes = 'video';
        } else {
            $allowedtypes = '';
        }

        $maxrecsize = get_max_upload_file_size();
        if (!empty($options['maxbytes'])) {
            $maxrecsize = min($maxrecsize, $options['maxbytes']);
        }
        $audiortcicon = 'i/audiortc';
        $videortcicon = 'i/videortc';
        $params = array('contextid' => $context->id,
            'sesskey' => $sesskey,
            'allowedtypes' => $allowedtypes,
            'audiobitrate' => $audiobitrate,
            'videobitrate' => $videobitrate,
            'timelimit' => $timelimit,
            'audiortcicon' => $audiortcicon,
            'videortcicon' => $videortcicon,
            'maxrecsize' => $maxrecsize
        );

        return $params;
    }

    /**
     * Initialise the js strings required for this module.
     */
    public function atto_recordrtc_strings_for_js()
    {
        global $PAGE;

        $strings = array('audiortc',
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
            'uploadaborted'
        );

        $PAGE->requires->strings_for_js($strings, 'atto_recordrtc');
        return $strings;
   }

    /**
     * Map icons for font-awesome themes.
     */
    public function atto_recordrtc_get_fontawesome_icon_map()
    {
        return [
            'atto_recordrtc:i/audiortc' => 'fa-microphone',
            'atto_recordrtc:i/videortc' => 'fa-video-camera'
        ];
    }
}
