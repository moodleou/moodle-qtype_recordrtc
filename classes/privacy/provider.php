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
 * Privacy provider for the record audio and video question type.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_recordrtc\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for qtype_recordrtc implementing user_preference_provider.
 *
 * @copyright  2021 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This component has data.
    // We need to return default options that have been set a user preferences.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference('qtype_recordrtc_defaultmark', 'privacy:preference:defaultmark');
        $collection->add_user_preference('qtype_recordrtc_mediatype', 'privacy:preference:mediatype');
        $collection->add_user_preference('qtype_recordrtc_timelimitinseconds', 'privacy:preference:timelimitinseconds');
        $collection->add_user_preference('qtype_recordrtc_pausing', 'privacy:preference:allowpausing');
        $collection->add_user_preference('qtype_recordrtc_canselfrate', 'privacy:preference:canselfrate');
        $collection->add_user_preference('qtype_recordrtc_canselfcomment', 'privacy:preference:canselfcomment');
        return $collection;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $preference = get_user_preferences('qtype_recordrtc_defaultmark', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:defaultmark', 'qtype_recordrtc');
            writer::export_user_preference('qtype_recordrtc', 'defaultmark', $preference, $desc);
        }

        $preference = get_user_preferences('qtype_recordrtc_mediatype', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:mediatype', 'qtype_recordrtc');
            writer::export_user_preference('qtype_recordrtc', 'mediatype',
                    get_string($preference, 'qtype_recordrtc'), $desc);
        }

        $preference = get_user_preferences('qtype_recordrtc_timelimitinseconds', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:timelimitinseconds', 'qtype_recordrtc');
            writer::export_user_preference('qtype_recordrtc', 'timelimitinseconds',
                    self::get_number_with_unit($preference), $desc);
        }
        $preference = get_user_preferences('qtype_recordrtc_pausing', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:allowpausing', 'qtype_recordrtc');
            writer::export_user_preference('qtype_recordrtc', 'allowpausing',
                    get_string($preference, 'qtype_recordrtc'), $desc);
        }
        $preference = get_user_preferences('qtype_recordrtc_canselfrate', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:canselfrate', 'qtype_recordrtc');
            writer::export_user_preference('qtype_recordrtc', 'canselfrate',
                    get_string($preference, 'qtype_recordrtc'), $desc);
        }
        $preference = get_user_preferences('qtype_recordrtc_canselfcomment', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:canselfcomment', 'qtype_recordrtc');
            writer::export_user_preference('qtype_recordrtc', 'canselfcomment',
                    get_string($preference, 'qtype_recordrtc'), $desc);
        }
    }

    /**
     * Convert number in seconds to number and unit (15 is '15 seconds', 120 is '2 minutes', 72 is '72 seconds')
     *
     * @param int $preference number in seconds.
     * @return string, number with unit (seconds or minutes).
     */
    private static function get_number_with_unit(int $preference): string {
        if ($preference >= MINSECS && $preference % MINSECS == 0) {
            return get_string('xminutes', 'qtype_recordrtc', $preference / 60);
        }
        return get_string('xseconds', 'qtype_recordrtc', $preference);
    }
}
