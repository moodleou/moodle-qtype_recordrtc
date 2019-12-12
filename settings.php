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
 * Admin settings for audio recorder.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Audio bitrate.
    $name = get_string('audiobitrate', 'atto_recordrtc');
    $desc = get_string('audiobitrate_desc', 'atto_recordrtc');
    $default = '128000';
    $setting = new admin_setting_configtext('qtype_recordrtc/audiobitrate', $name, $desc, $default, PARAM_INT, 8);
    $settings->add($setting);

    // Recording audio time limit.
    $name = get_string('timelimit', 'atto_recordrtc');
    $desc = get_string('timelimit_desc', 'atto_recordrtc');
    $default = '120';
    $setting = new admin_setting_configtext('qtype_recordrtc/timelimit', $name, $desc, $default, PARAM_INT, 8);
    $settings->add($setting);
}
