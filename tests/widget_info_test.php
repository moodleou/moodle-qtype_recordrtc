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

use qtype_recordrtc;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/recordrtc/question.php');

/**
 * Unit tests for the widget_info class.
 *
 * @package   qtype_recordrtc
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \qtype_recordrtc\widget_info
 */
class widget_info_test extends \advanced_testcase {

    /**
     * Data provider for test_convert_duration_to_seconds.
     *
     * @return array the test cases.
     */
    public function duration_to_seconds_cases(): array {
        return [
            '20 seconds as 20s' => ['20s', 20, '20s'],
            '1 minute as 1m' => ['1m', 60, '1m00s'],
            '2 minutes and 10 seconds as 02m10s' => ['2m10s', 130, '2m10s'],
            '666 seconds as 11m6s' => ['11m6s', 666, '11m06s'],
            '1 hour as 60m00s' => ['60m00s', 3600, '60m00s']
        ];
    }

    /**
     * Tests for widget_info::duration_to_seconds.
     *
     * @dataProvider duration_to_seconds_cases();
     *
     * @param string $duration The input duration string.
     * @param int $seconds how many seconds that translates to.
     */
    public function test_duration_to_seconds(string $duration, int $seconds): void {
        $this->assertEquals($seconds, widget_info::duration_to_seconds($duration));
    }

    /**
     * Tests for widget_info::duration_to_seconds.
     *
     * @dataProvider duration_to_seconds_cases();
     *
     * @param string $notused Not used - just so we can use the same data provides for two tests.
     * @param int $seconds a period in seconds.
     * @param string $duration the normalised version of the input duration string for that.
     */
    public function test_seconds_to_duration(string $notused, int $seconds, string $duration): void {
        $this->assertEquals($duration, widget_info::seconds_to_duration($seconds));
    }

    public function test_make_placeholder(): void {
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

    public function test_constructor_enforces_admin_time_limits_for_audio() {
        $this->resetAfterTest();

        set_config('audiotimelimit', 123, 'qtype_recordrtc');

        $widget = new widget_info('welcome', 'audio', 456);
        $this->assertEquals(123, $widget->maxduration);

        $widget = new widget_info('welcome', 'audio', 45);
        $this->assertEquals(45, $widget->maxduration);
    }

    public function test_constructor_enforces_admin_time_limits_for_video() {
        $this->resetAfterTest();

        set_config('videotimelimit', 67, 'qtype_recordrtc');

        $widget = new widget_info('welcome', 'video', 89);
        $this->assertEquals(67, $widget->maxduration);

        $widget = new widget_info('welcome', 'video', 45);
        $this->assertEquals(45, $widget->maxduration);
    }

    public function test_constructor_enforces_admin_time_limits_for_screen() {
        $this->resetAfterTest();

        set_config('screentimelimit', 67, 'qtype_recordrtc');

        $widget = new widget_info('welcome', 'screen', 89);
        $this->assertEquals(67, $widget->maxduration);

        $widget = new widget_info('welcome', 'screen', 45);
        $this->assertEquals(45, $widget->maxduration);
    }
}
