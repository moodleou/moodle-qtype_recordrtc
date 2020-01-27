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
 * Admin settings for the record audio (and video) question type.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Audio bitrate.
    $settings->add(new admin_setting_configtext('qtype_recordrtc/audiobitrate',
            get_string('audiobitrate', 'qtype_recordrtc'), get_string('audiobitrate_desc', 'qtype_recordrtc'),
            128000, PARAM_INT, 8));

    // Recording time limit.
    $settings->add(new admin_setting_configduration('qtype_recordrtc/timelimit',
            get_string('timelimit', 'qtype_recordrtc'), get_string('timelimit_desc', 'qtype_recordrtc'),
            600, 60));
}
