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

/**
 * Unit tests for the widget_info class.
 *
 * @package   qtype_recordrtc
 * @copyright 2022 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \qtype_recordrtc\privacy\recorder_base
 */
class recorder_base_test extends \advanced_testcase {

    /**
     * Data provider for test_convert_duration_to_seconds.
     *
     * @return array the test cases.
     */
    public function widget_name_from_filename_cases(): array {
        return [
            ['recording.mp3', 'recording'],
            ['recording.ogg', 'recording'],
            ['my_introduction.webm', 'my introduction'],
        ];
    }

    /**
     * Tests for widget_info::duration_to_seconds.
     *
     * @dataProvider widget_name_from_filename_cases();
     *
     * @param string $filename The filename given.
     * @param string $widgetname The expected widget name.
     */
    public function test_duration_to_seconds(string $filename, string $widgetname): void {
        $recorder = new \qtype_recordrtc\output\audio_recorder($filename, 30, false, null, true);
        $this->assertEquals($widgetname, $recorder->get_widget_name());
    }
}
