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
 * Unit tests for what happens when a record audio (and video) question is attempted.
 *
 * @package    qtype_recordrtc
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');


/**
 * Unit tests for what happens when a record audio (and video) question is attempted.
 */
class qtype_recordrtc_walkthrough_testcase extends qbehaviour_walkthrough_test_base {

    /**
     * Helper to get the qa of the qusetion being attempted.
     *
     * @return question_attempt
     */
    protected function get_qa() {
        return $this->quba->get_question_attempt($this->slot);
    }

    /**
     * Prepares the data (draft file) to simulate a user submitting a given fixture file.
     *
     * @param string $fixturefile name of the file to submit.
     * @return array response data that would need to be passed to $this->process_submission().
     */
    protected function store_submission_file(string $fixturefile) {
        $response = $this->setup_empty_submission_fileares();
        qtype_recordrtc_test_helper::add_recording_to_draft_area(
                $response['recording'], $fixturefile);
        return $response;
    }

    /**
     * Prepares the data (draft file) but with no files in it.
     *
     * @return array response data that would need to be passed to $this->process_submission().
     */
    protected function setup_empty_submission_fileares() {
        $this->render();
        if (!preg_match('/name="' . preg_quote($this->get_qa()->get_qt_field_name('recording')) .
                '" value="(\d+)"/', $this->currentoutput, $matches)) {
            throw new coding_exception('Draft item id not found.');
        }
        return ['recording' => $matches[1]];
    }

    /**
     * Simulate a user submitting the recording from the given fixture file.
     *
     * @param string $fixturefile name of the file to submit.
     */
    protected function process_submission_of_file(string $fixturefile) {
        $this->process_submission($this->store_submission_file($fixturefile));
    }

    public function test_deferred_feedback_audio_with_attempt_on_last() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a recordrtc question in the DB.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio', ['category' => $cat->id]);

        // Start attempt at the question.
        $q = question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'deferredfeedback', 1);

        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->assertEquals('manualgraded', $this->get_qa()->get_behaviour_name());

        // Process a response and check the expected result.
        $this->process_submission_of_file('moodle-tim.ogg');

        $this->check_current_state(question_state::$complete);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->save_quba();

        // Save the same response again, and verify no new step is created.
        $this->load_quba();
        $this->process_submission_of_file('moodle-tim.ogg');

        $this->check_current_state(question_state::$complete);
        $this->check_current_mark(null);
        $this->check_step_count(2);

        // Now submit all and finish.
        $this->finish();
        $this->check_current_state(question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(3);
        $this->save_quba();

        // Now start a new attempt based on the old one.
        $this->load_quba();
        $oldqa = $this->get_question_attempt();

        $q = question_bank::load_question($question->id);
        $this->quba = question_engine::make_questions_usage_by_activity('unit_test',
                context_system::instance());
        $this->quba->set_preferred_behaviour('deferredfeedback');
        $this->slot = $this->quba->add_question($q, 1);
        $this->quba->start_question_based_on($this->slot, $oldqa);

        $this->check_current_state(question_state::$complete);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->save_quba();

        // Now save the same response again, and ensure that a new step is not created.
        $this->process_submission_of_file('moodle-tim.ogg');

        $this->check_current_state(question_state::$complete);
        $this->check_current_mark(null);
        $this->check_step_count(1);
    }
}
