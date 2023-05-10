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

namespace qtype_recordrtc;

use qtype_recordrtc_question;
use qtype_recordrtc_test_helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
 * Unit tests for what happens when a record audio, video and screen question is attempted.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \qtype_recordrtc_question
 */
class walkthrough_test extends \qbehaviour_walkthrough_test_base {

    /**
     * Helper to get the qa of the qusetion being attempted.
     *
     * @return \question_attempt
     */
    protected function get_qa(): \question_attempt {
        return $this->quba->get_question_attempt($this->slot);
    }

    /**
     * Prepares the data (draft file) to simulate a user submitting a given fixture file.
     *
     * @param string $fixturefile name of the file to submit.
     * @param string $filename filename to submit the file under.
     * @return array response data that would need to be passed to $this->process_submission().
     */
    protected function store_submission_file(
            string $fixturefile, string $filename = 'recording.ogg'): array {
        $response = $this->setup_empty_submission_fileares();
        qtype_recordrtc_test_helper::clear_draft_area($response['recording']);
        qtype_recordrtc_test_helper::add_recording_to_draft_area(
                $response['recording'], $fixturefile, $filename);
        return $response;
    }

    /**
     * Prepares the data (draft file) to simulate a user submitting several files.
     *
     * @param array $fixturefiles list of files to submit file to submit.
     * @return array response data that would need to be passed to $this->process_submission().
     */
    protected function store_submission_files(array $fixturefiles): array {
        $response = $this->setup_empty_submission_fileares();
        qtype_recordrtc_test_helper::clear_draft_area($response['recording']);
        foreach ($fixturefiles as $filename => $fixturefile) {
            qtype_recordrtc_test_helper::add_recording_to_draft_area(
                    $response['recording'], $fixturefile, $filename);
        }
        return $response;
    }

    /**
     * Prepares the data (draft file) but with no files in it.
     *
     * @return array response data that would need to be passed to $this->process_submission().
     */
    protected function setup_empty_submission_fileares(): array {
        $this->render();
        if (!preg_match('/name="' . preg_quote($this->get_qa()->get_qt_field_name('recording')) .
                '" value="(\d+)"/', $this->currentoutput, $matches)) {
            throw new \coding_exception('Draft item id not found.');
        }
        return ['recording' => $matches[1]];
    }

    /**
     * Simulate a user submitting the recording from the given fixture file.
     *
     * @param string $fixturefile name of the file to submit.
     */
    protected function process_submission_of_file(string $fixturefile): void {
        $this->process_submission($this->store_submission_file($fixturefile));
    }

    /**
     * Simulate a user submitting several recordings for different inputs.
     *
     * @param array $fixturefiles Array filename to upload (based on widget name) => fixture file name.
     */
    protected function process_submission_of_files(array $fixturefiles) {
        $this->process_submission($this->store_submission_files($fixturefiles));
    }

    public function test_deferred_feedback_audio_with_attempt_on_last() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a recordrtc question in the DB.
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'audio', ['category' => $cat->id]);

        // Start attempt at the question.
        $q = \question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'deferredfeedback', 1);

        $this->check_current_state(\question_state::$todo);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->assertEquals('manualgraded', $this->get_qa()->get_behaviour_name());

        // Process a response and check the expected result.
        $this->process_submission_of_file('moodle-tim.ogg');

        $this->check_current_state(\question_state::$complete);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->save_quba();

        // Save the same response again, and verify no new step is created.
        $this->load_quba();
        $this->process_submission_of_file('moodle-tim.ogg');

        $this->check_current_state(\question_state::$complete);
        $this->check_current_mark(null);
        $this->check_step_count(2);

        // Now submit all and finish.
        $this->finish();
        $this->check_current_state(\question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(3);
        $this->save_quba();

        // Now start a new attempt based on the old one.
        $this->load_quba();
        $oldqa = $this->get_question_attempt();

        $q = \question_bank::load_question($question->id);
        $this->quba = \question_engine::make_questions_usage_by_activity('unit_test',
                \context_system::instance());
        $this->quba->set_preferred_behaviour('deferredfeedback');
        $this->slot = $this->quba->add_question($q, 1);
        $this->quba->start_question_based_on($this->slot, $oldqa);

        $this->check_current_state(\question_state::$complete);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->save_quba();

        // Now save the same response again, and ensure that a new step is not created.
        $this->process_submission_of_file('moodle-tim.ogg');

        $this->check_current_state(\question_state::$complete);
        $this->check_current_mark(null);
        $this->check_step_count(1);
    }

    public function test_custom_av_with_per_widget_feedback() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a recordrtc question in the DB.
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();
        $question = $generator->create_question('recordrtc', 'customav', ['category' => $cat->id]);

        // Start attempt at the question.
        /** @var qtype_recordrtc_question $q */
        $q = \question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'deferredfeedback', 1);

        $this->check_current_state(\question_state::$todo);
        $this->check_current_mark(null);
        $this->check_step_count(1);
        $this->assertEquals('manualgraded', $this->get_qa()->get_behaviour_name());

        // Process a response and check the expected result.
        $this->process_submission_of_files([
            'development.ogg' => 'moodle-tim.ogg',
            'user_experience.ogg' => 'moodle-sharon.ogg',
            'action.webm' => 'moodle-screen.webm',
        ]);

        $this->check_current_state(\question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->save_quba();

        // Save the same response again, and verify no new step is created.
        $this->load_quba();
        $this->process_submission_of_files([
            'development.ogg' => 'moodle-tim.ogg',
            'user_experience.ogg' => 'moodle-sharon.ogg',
            'action.webm' => 'moodle-screen.webm',
        ]);
        // Feedback should not be visible during the attempt.
        $this->render();
        $this->assertStringNotContainsString($q->widgets['development']->feedback, $this->currentoutput);

        $this->check_current_state(\question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_step_count(2);

        // Now submit all and finish.
        $this->finish();
        $this->check_current_state(\question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->check_step_count(3);
        $this->save_quba();
        // Feedback should be visible after the attempt, but only for the things that were answered.
        $this->render();
        $this->assertStringContainsString($q->widgets['development']->feedback, $this->currentoutput);
        $this->assertStringNotContainsString($q->widgets['installation']->feedback, $this->currentoutput);
        $this->assertStringContainsString($q->widgets['user_experience']->feedback, $this->currentoutput);
        $this->assertStringContainsString($q->widgets['action']->feedback, $this->currentoutput);

        // Feedback display should respect the display options.
        $this->displayoptions->generalfeedback = false; // See comment in the renderer.
        $this->render();
        $this->assertStringNotContainsString($q->widgets['development']->feedback, $this->currentoutput);
        $this->assertStringNotContainsString($q->widgets['installation']->feedback, $this->currentoutput);
        $this->assertStringNotContainsString($q->widgets['user_experience']->feedback, $this->currentoutput);
        $this->assertStringNotContainsString($q->widgets['action']->feedback, $this->currentoutput);
    }

    public function test_custom_av_rendering_with_glossary_filter() {
        global $CFG, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();

        // There was a bug where the question broke if you had the word 'audio'
        // in a glossary set to autolink.

        // Create a glossary set to autolink, containing the word 'audio'.
        filter_set_global_state('glossary', TEXTFILTER_ON);
        $CFG->glossary_linkentries = 1;

        // Create a test course.
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        // Create a glossary.
        $glossary = $this->getDataGenerator()->create_module('glossary',
                ['course' => $course->id, 'mainglossary' => 1]);

        // Create two entries with ampersands and one normal entry.
        /** @var \mod_glossary_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_glossary');
        $audioentry = $generator->create_content($glossary, ['concept' => 'audio']);
        $moodleentry = $generator->create_content($glossary, ['concept' => 'Moodle']);

        // Create a recordrtc question in the DB.
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category(['contextid' => $coursecontext->id]);
        $question = $generator->create_question('recordrtc', 'customav', ['category' => $cat->id]);

        // Start attempt at the question.
        /** @var qtype_recordrtc_question $q */
        $q = \question_bank::load_question($question->id);
        $this->start_attempt_at_question($q, 'deferredfeedback', 1);

        // Verify that the output contains the 'Moodle' glossary entry link,
        // to verify that auto-linking is working.
        $PAGE->set_context($coursecontext);
        $this->render();
        $this->assert_contains_glossary_link($moodleentry, $this->currentoutput);

        // ... but that the output does does not contain a link to the audio entry.
        $this->assert_does_not_contain_glossary_link($audioentry, $this->currentoutput);

        // Also check that we have an audio recorder widget in there.
        $this->assertStringContainsString('<span class="qtype_recordrtc-audio-widget', $this->currentoutput);
    }

    /**
     * Assert that some HTML contains a Moodle glossary link.
     *
     * @param \stdClass $glossaryentry from the generator.
     * @param string $html HTML to test.
     */
    protected function assert_contains_glossary_link(\stdClass $glossaryentry, string $html): void {
        $this->assertMatchesRegularExpression($this->get_glossary_link_regexp($glossaryentry), $html);
    }

    /**
     * Assert that some HTML does not contains a Moodle glossary link.
     *
     * @param \stdClass $glossaryentry from the generator.
     * @param string $html HTML to test.
     */
    protected function assert_does_not_contain_glossary_link(\stdClass $glossaryentry, string $html): void {
        $this->assertDoesNotMatchRegularExpression($this->get_glossary_link_regexp($glossaryentry), $html);
    }

    /**
     * Asssert that some HTML contains a Moodle glossary link.
     *
     * @param \stdClass $glossaryentry from the generator.
     */
    protected function get_glossary_link_regexp(\stdClass $glossaryentry): string {
        // If you are wondering, eid= is part of the link URL, and title is the title
        // attribute of the HTML tag.
        return '~eid=' . $glossaryentry->id . '.*?title="(.*?)' . $glossaryentry->concept . '"~';
    }
}
