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

use qtype_recordrtc\widget_info;

global $CFG;
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/recordrtc/question.php');


/**
 * Unit tests for the widget_info class.
 *
 * @copyright  2021 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class widget_info_test extends advanced_testcase {

    /**
     * Data provider for test_convert_duration_to_seconds.
     *
     * @return array the test cases.
     */
    public function duration_to_seconds_cases() {
        return [
            '20 seconds as 20s' => ['20s', 20, '20s'],
            '1 minute as 1m' => ['1m', 60, '1m00s'],
            '2 minutes and 10 seconds as 02m10s' => ['2m10s', 130, '2m10s'],
            '666 seconds as 11m6s' => ['11m6s', 666, '11m06s'],
            '1 hour as 60m00s' => ['60m00s', 3600, '60m00s']
        ];
    }

    /**
     * @dataProvider duration_to_seconds_cases();
     */
    public function test_duration_to_seconds(string $duration, int $seconds, string $notused) {
        $this->assertEquals($seconds, widget_info::duration_to_seconds($duration));
    }

    /**
     * @dataProvider duration_to_seconds_cases();
     */
    public function test_seconds_to_duration(string $notused, int $seconds, string $duration) {
        $this->assertEquals($duration, widget_info::seconds_to_duration($seconds));
    }

    public function test_make_placeholder() {
        $this->assertEquals('[[welcome:audio:30s]]', widget_info::make_placeholder('welcome', 'audio', 30));
    }

    public function test_constructor() {
        $widget = new widget_info('welcome', 'audio');
        $this->assertEquals('welcome', $widget->name);
        $this->assertEquals(qtype_recordrtc::MEDIA_TYPE_AUDIO, $widget->type);
        $this->assertEquals(qtype_recordrtc::DEFAULT_TIMELIMIT, $widget->maxduration);
        $this->assertEquals('[[welcome:audio:30s]]', $widget->placeholder);
        $this->assertEquals('', $widget->feedback);
        $this->assertEquals(FORMAT_HTML, $widget->feedbackformat);
    }
}
