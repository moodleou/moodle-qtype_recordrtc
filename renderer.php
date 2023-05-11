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
 * The record audio and video question type question renderer class.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');

use qtype_recordrtc\output\audio_playback;
use qtype_recordrtc\output\audio_recorder;
use qtype_recordrtc\output\video_playback;
use qtype_recordrtc\output\video_recorder;
use qtype_recordrtc\output\screen_playback;
use qtype_recordrtc\output\screen_recorder;

/**
 * Generates output for record audio and video questions.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc_renderer extends qtype_renderer {

    public function formulation_and_controls(question_attempt $qa, question_display_options $options): string {
        /** @var qtype_recordrtc_question $question */
        $question = $qa->get_question();
        $candownload = has_capability('qtype/recordrtc:downloadrecordings', $this->page->context);
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

        // Before we prepare the question text for display, which include applying the
        // Moodle text filters, we have to protect the placeholders with
        // <span class="nolink">...</span> tags.
        $questiontext = $question->questiontext;
        foreach ($question->widgets as $widget) {
            $questiontext = str_replace($widget->placeholder, $widget->get_protected_placeholder(),
                    $questiontext);
        }
        $questiontext = $question->format_text($questiontext, $question->questiontextformat,
                $qa, 'question', 'questiontext', $question->id);

        // Replace all the placeholders with the corresponding recording or player widget.
        foreach ($question->widgets as $widget) {
            $filename = qtype_recordrtc::get_media_filename($widget->name, $widget->type);
            $existingfile = $question->get_file_from_response($filename, $existingfiles);

            if ($options->readonly) {
                // Review.
                if ($existingfile) {
                    $recordingurl = $qa->get_response_file_url($existingfile);
                } else {
                    $recordingurl = null;
                }

                switch ($widget->type) {
                    case 'audio':
                        $playback = new audio_playback($filename, $recordingurl, $candownload);
                        break;
                    case 'screen':
                        $playback = new screen_playback($filename, $recordingurl, $candownload);
                        break;
                    default:
                        $playback = new video_playback($filename, $recordingurl, $candownload);
                        break;
                }

                $thisitem = $this->render($playback);
                if ($existingfile) {
                    // The next line should logically just check ->feedback, but for some reason,
                    // manual graded behaviour always sets that to false, so check general feedback
                    // option too.
                    if (($options->feedback || $options->generalfeedback) && $widget->feedback !== '') {
                        $thisitem .= html_writer::div(
                                $question->format_text(
                                        $widget->feedback, $widget->feedbackformat,
                                        $qa, 'question', 'answerfeedback', $widget->answerid),
                                'specificfeedback');
                    }
                }

            } else {
                // Being attempted.
                if ($existingfile) {
                    $recordingurl = moodle_url::make_draftfile_url($draftitemid, '/', $filename);
                } else {
                    $recordingurl = null;
                }

                switch ($widget->type) {
                    case 'audio':
                        $recorder = new audio_recorder($filename,
                            $widget->maxduration, $question->allowpausing, $recordingurl, $candownload);
                        break;
                    case 'screen':
                        $recorder = new screen_recorder($filename,
                            $widget->maxduration, $question->allowpausing, $recordingurl, $candownload);
                        break;
                    default:
                        $recorder = new video_recorder($filename,
                            $widget->maxduration, $question->allowpausing, $recordingurl, $candownload);
                        break;
                }

                // Recording UI.
                $thisitem = $this->render($recorder);
            }

            $questiontext = str_replace($widget->get_protected_placeholder(), $thisitem, $questiontext);
        }

        $output .= html_writer::tag('div', $questiontext, ['class' => 'qtext']);

        if (!$options->readonly) {
            // Initialise the JavaScript.
            $repositories = repository::get_instances(
                    ['type' => 'upload', 'currentcontext' => $options->context]);
            if (empty($repositories)) {
                throw new moodle_exception('errornouploadrepo', 'moodle');
            }
            $uploadrepository = reset($repositories); // Get the first (and only) upload repo.
            [$videowidth, $videoheight] = explode(',', get_config('qtype_recordrtc', 'videosize'));
            [$videoscreenwidth, $videoscreenheight] = explode(',', get_config('qtype_recordrtc', 'screensize'));
            $setting = [
                'audioBitRate' => (int) get_config('qtype_recordrtc', 'audiobitrate'),
                'videoBitRate' => (int) get_config('qtype_recordrtc', 'videobitrate'),
                'screenBitRate' => (int) get_config('qtype_recordrtc', 'screenbitrate'),
                'maxUploadSize' => $question->get_upload_size_limit($options->context),
                'uploadRepositoryId' => (int) $uploadrepository->id,
                'contextId' => $options->context->id,
                'draftItemId' => $draftitemid,
                'videoWidth' => (int) $videowidth,
                'videoHeight' => (int) $videoheight,
                'screenWidth' => (int) $videoscreenwidth,
                'screenHeight' => (int) $videoscreenheight,
            ];
            $this->page->requires->strings_for_js($this->strings_for_js(), 'qtype_recordrtc');
            $this->page->requires->js_call_amd('qtype_recordrtc/avrecording', 'init',
                    [$qa->get_outer_question_div_unique_id(), $setting]);
        }
        return $output;
    }

    /**
     * These messages are hidden unless revealed by the JavaScript.
     *
     * @return string HTML for the 'this can't work here' messages.
     */
    protected function cannot_work_warnings(): string {
        return $this->render_from_template('qtype_recordrtc/cannot_work_warnings', []);
    }

    /**
     * Strings our JS will need.
     *
     * @return string[] lang string names from the qtype_recordrtc lang file.
     */
    public function strings_for_js(): array {
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
            'pause',
            'recordagainx',
            'recordingfailed',
            'resume',
            'startrecording',
            'stoprecording',
            'startsharescreen',
            'timedisplay',
            'uploadaborted',
            'uploadcomplete',
            'uploadfailed',
            'uploadfailed404',
            'uploadpreparing',
            'uploadpreparingpercent',
            'uploadprogress',
        ];
    }

    /**
     * Map icons for font-awesome themes.
     *
     * @return array of icon mappings.
     */
    public function qtype_recordrtc_get_fontawesome_icon_map(): array {
        return [
            'atto_recordrtc:i/audiortc' => 'fa-microphone',
            'atto_recordrtc:i/videortc' => 'fa-video-camera',
        ];
    }
}
