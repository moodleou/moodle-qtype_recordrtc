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
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/recordrtc/question.php');


/**
 * Unit tests for the record audio, video and screen question definition class.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \qtype_recordrtc_question
 */
class question_test extends \advanced_testcase {

    /**
     * Get a question instance to test.
     *
     * @return qtype_recordrtc_question the question.
     */
    protected function get_a_test_question(): qtype_recordrtc_question {
        return \test_question_maker::make_question('recordrtc', 'audio');
    }

    /**
     * Get a question instance, with a response.
     *
     * @return array get an audio/video/screen question an a non-blank response.
     */
    protected function get_a_test_question_and_response(): array {
        $this->resetAfterTest();
        $this->setAdminUser();

        $q = $this->get_a_test_question();
        $response = ['recording' =>
            qtype_recordrtc_test_helper::make_recordings_saver(['moodle-tim.ogg' => 'recording.ogg'])];
        return [$q, $response];

    }

    public function test_summarise_response_blank() {
        $q = $this->get_a_test_question();
        $this->assertEquals('No recording', $q->summarise_response([]));
        $this->assertEquals('No recording', $q->summarise_response(['recording' => '']));
    }

    public function test_summarise_response_with_file() {
        [$q, $response] = $this->get_a_test_question_and_response();
        $this->assertEquals('recording.ogg', $q->summarise_response($response));
    }

    /**
     * Get a question instance, with multiple responses.
     *
     * @return array get an audio question non-blank responses.
     */
    protected function get_a_test_question_and_responses(): array {
        $this->resetAfterTest();
        $this->setAdminUser();

        $q = \test_question_maker::make_question('recordrtc', 'customav');
        $response = ['recording' =>
                qtype_recordrtc_test_helper::make_recordings_saver(['moodle-tim.ogg' => 'development.ogg',
                    'small.mp3' => 'installation.mp3',
                    'moodle-sharon.ogg' => 'user_experience.ogg'])];
        return [$q, $response];
    }

    /**
     * Test summarise_response with blank responses for customav question.
     */
    public function test_summarise_responses_blank() {
        $q = \test_question_maker::make_question('recordrtc', 'customav');
        $this->assertEquals('No recording', $q->summarise_response([]));
        $this->assertEquals('No recording', $q->summarise_response(['development' => '', 'installation' => '']));
    }

    /**
     * Test summarise_response with multiple responses for customav question.
     */
    public function test_summarise_response_with_files() {
        [$q, $responses] = $this->get_a_test_question_and_responses();
        $this->assertEquals('development.ogg, installation.mp3, user_experience.ogg',
            $q->summarise_response($responses));
    }

    public function test_is_complete_response_blank() {
        $q = $this->get_a_test_question();
        $this->assertFalse($q->is_complete_response([]));
    }

    public function test_is_complete_response_with_file() {
        [$q, $response] = $this->get_a_test_question_and_response();
        $this->assertTrue($q->is_complete_response($response));
    }

    public function test_get_validation_error_blank() {
        $q = $this->get_a_test_question();
        $this->assertEquals('Please complete your answer.',
            $q->get_validation_error([]));
    }

    public function test_get_validation_error_with_file() {
        [$q, $response] = $this->get_a_test_question_and_response();
        $this->assertEquals('', $q->get_validation_error($response));
    }

    public function test_is_same_response_both_blank() {
        $q = $this->get_a_test_question();
        $this->assertTrue($q->is_same_response([], []));
    }

    public function test_is_same_response_one_blank() {
        [$q, $response] = $this->get_a_test_question_and_response();
        $this->assertFalse($q->is_same_response([], $response));
    }

    public function test_is_same_response_same_files() {
        [$q, $response] = $this->get_a_test_question_and_response();
        $this->assertTrue($q->is_same_response($response, $response));
    }

    public function test_is_same_response_different_files() {
        [$q, $response] = $this->get_a_test_question_and_response();
        $otherresponse = ['recording' =>
            qtype_recordrtc_test_helper::make_recordings_saver(['moodle-sharon.ogg' => 'recording.ogg'])];

        $this->assertFalse($q->is_same_response($response, $otherresponse));
    }
}
