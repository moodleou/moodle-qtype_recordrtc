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
 * Recordrtc question definition class.
 *
 * @package    qtype_recordrtc
 * @copyright  2019 The OPen University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Represents a recordrtc question.
 *
 * @copyright  2019 The OPen University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc_question extends question_graded_automatically_with_countback {

    public function get_expected_data() {
        // TODO.
        return array();
    }

    public function start_attempt(question_attempt_step $step, $variant) {
        // TODO: Define this method.
    }

    /**
     * Summarises the
     * @return summary a string that summarises how the user responded. This
     * is used in the quiz responses report
     * */
    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            // TODO: Find the way to display a sting about the audio file to be used in the quiz responses report.
            return 'a function returning a string providing info about the audio file';
        }
        return null;
    }

    public function is_complete_response(array $response) {
        // TODO: If $response indicate that an audio file does NOT exist then retune false.

        return true;
    }

    public function get_validation_error(array $response) {
        if ($this->is_complete_response($response)) {
            return ''; // The audio file exist and is valid.
        }
        // The audio file does not exist or need to be rerecorded.
        return get_string('inputyouraudioresponse', 'qtype_recordrtc');
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        // TODO: We may compare some metadata such as the length of the recording.
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function get_correct_response() {
        // TODO: This will be done in the second/third phase of the audio question type when we can copare audio files.
        return null;
    }

    public function check_file_access($qa, $options, $component, $filearea,
            $args, $forcedownload) {
        // TODO.
        if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);

        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }

    public function grade_response(array $response) {
        // TODO: This can be done later, for now we call graded_state_for_fraction() to avoid error in question behaviours.
        $fraction = 0;
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function compute_final_grade($responses, $totaltries) {
        // TODO: This can be implemented in later phases when we supporting interactive behaviour.
        return 0;
    }
}
