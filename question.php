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
    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        return question_engine::make_behaviour('manualgraded', $qa, $preferredbehaviour);
    }

    public function get_expected_data() {
        return ['recording' => question_attempt::PARAM_FILES];
    }

    public function summarise_response(array $response) {
        if (!isset($response['recording'])) {
            return null;
        }

        $files = $response['recording']->get_files();
        $file = reset($files);

        if (!$file) {
            return null;
        }

        return get_string('filex', 'qtype_recordrtc', $file->get_filename());
    }

    public function is_complete_response(array $response) {
        return isset($response['recording']);
    }

    public function get_validation_error(array $response) {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return get_string('pleaserecordsomething', 'qtype_recordrtc');
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
        if ($component == 'question' && $filearea == 'response_attachments') {
            // Response recording always accessible.
            return true;
        }
        return parent::check_file_access($qa, $options, $component, $filearea,
                $args, $forcedownload);
    }
}
