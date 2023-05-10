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

use qtype_recordrtc\privacy\provider;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/recordrtc/classes/privacy/provider.php');

/**
 * Privacy provider tests class.
 *
 * @package   qtype_recordrtc
 * @copyright 2021 The Open university
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \qtype_recordrtc\privacy\provider
 */
class privacy_provider_test extends \core_privacy\tests\provider_testcase {
    // Include the privacy helper which has assertions on it.

    public function test_get_metadata() {
        $collection = new \core_privacy\local\metadata\collection('qtype_recordrtc');
        $actual = provider::get_metadata($collection);
        $this->assertEquals($collection, $actual);
    }

    public function test_export_user_preferences_no_pref() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test the export_user_preferences given different inputs
     * @dataProvider user_preference_provider

     * @param string $name The name of the user preference to get/set
     * @param string $value The value stored in the database
     * @param string $expected The expected transformed value
     */
    public function test_export_user_preferences(string $name, string $value, string $expected) {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        set_user_preference("qtype_recordrtc_$name", $value, $user);
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $this->assertTrue($writer->has_any_data());
        $preferences = $writer->get_user_preferences('qtype_recordrtc');
        foreach ($preferences as $key => $pref) {
            $preference = get_user_preferences("qtype_recordrtc_$key", null, $user->id);
            if ($preference === null) {
                continue;
            }
            $desc = get_string("privacy:preference:$key", 'qtype_recordrtc');
            $this->assertEquals($expected, $pref->value);
            $this->assertEquals($desc, $pref->description);
        }
    }

    /**
     * Create an array of valid user preferences for the Record audio/video question type.
     *
     * @return array Array of valid user preferences.
     */
    public function user_preference_provider(): array {
        return [
                'default mark 2' => ['defaultmark', '1.5', '1.5'],
                'mediatype audio' => ['mediatype', 'audio', get_string('audio', 'qtype_recordrtc')],
                'mediatype video' => ['mediatype', 'video', get_string('video', 'qtype_recordrtc')],
                'mediatype screen' => ['mediatype', 'screen', get_string('screen', 'qtype_recordrtc')],
                'mediatype customav' => ['mediatype', 'customav', get_string('customav', 'qtype_recordrtc')],
                'Max recording duration 1' => ['timelimitinseconds', '15', '15 seconds'],
                'Max recording duration 2' => ['timelimitinseconds', '60', '1 minutes'],
                'Max recording duration 3' => ['timelimitinseconds', '120', '2 minutes'],
                'Max recording duration 4' => ['timelimitinseconds', '65', '65 seconds'],
                'Max recording duration 5' => ['timelimitinseconds', '121', '121 seconds'],
        ];
    }
}
