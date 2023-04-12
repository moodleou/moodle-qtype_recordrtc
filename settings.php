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
 * Admin settings for the record audio and video question type.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Default settings for audio.
    $settings->add(new admin_setting_heading('audiovideoheading',
        get_string('optionsforaudioandvideo', 'qtype_recordrtc'), ''));

    // Default settings for audio.
    $settings->add(new admin_setting_heading('audiooptionsheading',
        get_string('optionsforaudio', 'qtype_recordrtc'), ''));

    // Recording time limit.
    $settings->add(new admin_setting_configduration('qtype_recordrtc/audiotimelimit',
            get_string('audiotimelimit', 'qtype_recordrtc'), get_string('audiotimelimit_desc', 'qtype_recordrtc'),
            600, 60));

    // Audio bitrate.
    $settings->add(new admin_setting_configtext('qtype_recordrtc/audiobitrate',
        get_string('audiobitrate', 'qtype_recordrtc'), get_string('audiobitrate_desc', 'qtype_recordrtc'),
        128000, PARAM_INT, 8));


    // Default settings for video.
    $settings->add(new admin_setting_heading('videooptionsheading',
        get_string('optionsforvideo', 'qtype_recordrtc'), ''));

    // Recording time limit for video.
    $settings->add(new admin_setting_configduration('qtype_recordrtc/videotimelimit',
            get_string('videotimelimit', 'qtype_recordrtc'), get_string('videotimelimit_desc', 'qtype_recordrtc'),
            300, 60));

    // Video bitrate.
    $settings->add(new admin_setting_configtext('qtype_recordrtc/videobitrate',
        get_string('videobitrate', 'qtype_recordrtc'), get_string('videobitrate_desc', 'qtype_recordrtc'),
        2500000, PARAM_INT, 8));

    // Video size settings.
    // Number of items to display in a box.
    $options = [
        '240,180' => '240 x 180 (4:3)',
        '320,180' => '320 x 180 (16:9)',
        '320,240' => '320 x 240 (4:3)',
        '426,240' => '426 x 240 (16:9)',
        '384,288' => '384 x 288 (4:3)',
        '512,288' => '512 x 288 (16:9)',
        '480,360' => '480 x 360 (4:3)',
        '640,360' => '640 x 360 (16:9))',
        '576,432' => '576 x 432 (4:3)',
        '640,480' => '640 x 480 (4:3)',
        '768,432' => '768 x 432 (16:9)',
        '768,576' => '768 x 576 (4:3)',
        '1280,720' => '1280 x 720 (16:9)',
        '1024,768' => '1024 x 768 (4:3)'
    ];
    $settings->add(new admin_setting_configselect('qtype_recordrtc/videosize',
        get_string('videosize', 'qtype_recordrtc'),
        get_string('videosize_desc', 'qtype_recordrtc'), '320,180', $options));

    // Default settings for screen record output.
    $settings->add(new admin_setting_heading('screenoptionsheading',
        get_string('optionsforscreen', 'qtype_recordrtc'), ''));

    // Recording time limit for screen.
    $settings->add(new admin_setting_configduration('qtype_recordrtc/screentimelimit',
        get_string('screentimelimit', 'qtype_recordrtc'), get_string('screentimelimit_desc', 'qtype_recordrtc'),
        300, 60));

    // Screen output bitrate.
    $settings->add(new admin_setting_configtext('qtype_recordrtc/screenbitrate',
        get_string('screenbitrate', 'qtype_recordrtc'), get_string('screenbitrate_desc', 'qtype_recordrtc'),
        2500000, PARAM_INT, 8));

    // Screen output settings.
    // Number of items to display in a box.
    $options = [
        '1280,720' => '1280 x 720 (16:9)',
        '1920,1080' => '1920 x 1080 (16:9)',
    ];
    $settings->add(new admin_setting_configselect('qtype_recordrtc/screensize',
        get_string('screensize', 'qtype_recordrtc'),
        get_string('screensize_desc', 'qtype_recordrtc'), '1280,720', $options));
}
