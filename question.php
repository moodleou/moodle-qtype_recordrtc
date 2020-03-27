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
 * Question class for the record audio (and video) question type.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * A record audio (and video) question that is being attempted.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc_question extends question_with_responses {

    /**
     * @var int the maximum length recording, in seconds, the student is allowed to make.
     */
    public $timelimitinseconds;

    /**
     * @var string media type, 'audio' or 'video'.
     */
    public $mediatype;

    /**
     * @var string[] placeholder => filename
     */
    public $widgetplaceholders;

    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        global $CFG;
        if (is_readable($CFG->dirroot . '/question/behaviour/selfassess/behaviour.php') &&
                question_engine::get_behaviour_type($preferredbehaviour)->can_questions_finish_during_the_attempt()) {
            return question_engine::make_behaviour('selfassess', $qa, $preferredbehaviour);
        } else {
            return question_engine::make_behaviour('manualgraded', $qa, $preferredbehaviour);
        }
    }

    public function get_expected_data() {
        return ['recording' => question_attempt::PARAM_FILES];
    }

    /**
     * Get the upload file size limit that applies here.
     *
     * @param context $context the context we are in.
     * @return int max size in bytes.
     */
    public function get_upload_size_limit(context $context) {
        global $CFG;

        // This logic is roughly copied from lib/form/filemanager.php.

        // Get the course file size limit.
        $coursebytes = $maxbytes = 0;
        list($context, $course, $cm) = get_context_info_array($context->id);
        if (is_object($course)) {
            $coursebytes = $course->maxbytes;
        }

        // TODO should probably also get the activity file size limit, but filemanager doesn't.

        return get_user_max_upload_file_size($context, $CFG->maxbytes, $coursebytes);
    }

    public function summarise_response(array $response) {
        if (!isset($response['recording']) || $response['recording'] === '') {
            return get_string('norecording', 'qtype_recordrtc');
        }

        $files = $response['recording']->get_files();
        $file = reset($files);

        if (!$file) {
            return get_string('norecording', 'qtype_recordrtc');
        }

        return get_string('filex', 'qtype_recordrtc', $file->get_filename());
    }

    public function is_complete_response(array $response) {
        // Have all parts of the question been answered?
        if (!isset($response['recording']) || $response['recording'] === '') {
            return false;
        }

        $files = $response['recording']->get_files();
        foreach ($this->widgetplaceholders as $filename) {
            if (!$this->get_file_from_response($filename, $files)) {
                return false;
            }
        }
        return true;
    }

    public function is_gradable_response(array $response) {
        // Has any parts of the question been answered? If so we might give partial credit.
        if (!isset($response['recording']) || $response['recording'] === '') {
            return false;
        }

        $files = $response['recording']->get_files();
        return !empty($files);
    }

    /**
     * Get a specific file from the array of files in a resonse (or null).
     *
     * @param string $filename the file we want.
     * @param stored_file[] $files all the files from a response (e.g. $response['recording']->get_files();)
     * @return stored_file|null the file, if it exists, or null if not.
     */
    public function get_file_from_response(string $filename, array $files): ?stored_file {
        foreach ($files as $file) {
            if ($file->get_filename() === $filename) {
                return $file;
            }
        }

        return null;
    }

    public function get_validation_error(array $response) {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return get_string('pleaserecordsomethingineachpart', 'qtype_recordrtc');
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'recording');
    }

    public function get_correct_response() {
        // Not possible to give a correct response.
        return null;
    }

    public function check_file_access($qa, $options, $component, $filearea,
            $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'response_recording') {
            // Response recording always accessible.
            return true;
        }
        return parent::check_file_access($qa, $options, $component, $filearea,
                $args, $forcedownload);
    }
}
