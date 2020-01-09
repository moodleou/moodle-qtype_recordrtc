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
 * Contains the helper class for the select missing words question type tests.
 *
 * @package    qtype_recordrtc
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/recordrtc/question.php');


/**
 * Unit tests for the record audio (and video) question definition class.
 *
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc_question_test extends advanced_testcase {

    /**
     * Get a question instance to test.
     *
     * @return qtype_recordrtc_question the question.
     */
    protected function get_a_test_question() {
        return test_question_maker::make_question('recordrtc', 'audio');
    }

    /**
     * @return array get an audio question an a non-blank response.
     */
    protected function get_a_test_question_and_response() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $q = $this->get_a_test_question();
        $response = ['recording' =>
                qtype_recordrtc_test_helper::make_recording_saver('moodle-tim.ogg')];
        return [$q, $response];

    }

    public function test_summarise_response_blank() {
        $q = $this->get_a_test_question();
        $this->assertEquals('No recording', $q->summarise_response([]));
        $this->assertEquals('No recording', $q->summarise_response(['recording' => '']));
    }

    public function test_summarise_response_with_file() {
        list($q, $response) = $this->get_a_test_question_and_response();
        $this->assertEquals('File recording.ogg', $q->summarise_response($response));
    }

    public function test_is_complete_response_blank() {
        $q = $this->get_a_test_question();
        $this->assertFalse($q->is_complete_response([]));
    }

    public function test_is_complete_response_with_file() {
        list($q, $response) = $this->get_a_test_question_and_response();
        $this->assertTrue($q->is_complete_response($response));
    }

    public function test_get_validation_error_blank() {
        $q = $this->get_a_test_question();
        $this->assertEquals('Please record something.', $q->get_validation_error([]));
    }

    public function test_get_validation_error_with_file() {
        list($q, $response) = $this->get_a_test_question_and_response();
        $this->assertEquals('', $q->get_validation_error($response));
    }

    public function test_is_same_response_both_blank() {
        $q = $this->get_a_test_question();
        $this->assertTrue($q->is_same_response([], []));
    }

    public function test_is_same_response_one_blank() {
        list($q, $response) = $this->get_a_test_question_and_response();
        $this->assertFalse($q->is_same_response([], $response));
    }

    public function test_is_same_response_same_files() {
        list($q, $response) = $this->get_a_test_question_and_response();
        $this->assertTrue($q->is_same_response($response, $response));
    }

    public function test_is_same_response_different_files() {
        list($q, $response) = $this->get_a_test_question_and_response();
        $otherresponse = ['recording' =>
                qtype_recordrtc_test_helper::make_recording_saver('moodle-sharon.ogg')];

        $this->assertFalse($q->is_same_response($response, $otherresponse));
    }
}
