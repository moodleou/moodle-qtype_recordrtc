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
 * Defines the editing form for record audio (and video) questions.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/edit_question_form.php');


/**
 * The editing form for record audio (and video) questions.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc_edit_form extends question_edit_form {


    protected function definition_inner($mform) {
        // Header for setting options
        $mform->addElement('header', 'settingoptionsheader', get_string('settingoptions', 'qtype_recordrtc'));

        // Field for mediatype.
        $options = [qtype_recordrtc_question::MEDIATYPE_AUDIO => 'audio', qtype_recordrtc_question::MEDIATYPE_VIDEO => 'video'];
        $mform->addElement('select', 'mediatype', get_string('mediatype', 'qtype_recordrtc'), $options);
        $mform->addHelpButton('mediatype', 'mediatype', 'qtype_recordrtc');
        $mform->setDefault('mediatype', qtype_recordrtc_question::MEDIATYPE_AUDIO);

        // Field for timelimitinseconds. TODO: Find a way to display inline the Time and Time unit fields afetr sanity check messgae
//        $mform->addElement('duration', 'timelimitinseconds', get_string('timelimit', 'qtype_recordrtc'));
//        $mform->setType('timelimitinseconds', PARAM_INT);
//        $mform->addHelpButton('timelimitinseconds', 'timelimit', 'qtype_recordrtc');
//        $mform->setDefault('timelimitinseconds', qtype_recordrtc_question::TIMELIMIT_DEFAULT);

        // Field for timelimitinseconds.
        $mform->addElement('text', 'timelimitinseconds', get_string('timelimit', 'qtype_recordrtc'), ['size' =>'6']);
        $mform->setType('timelimitinseconds', PARAM_INT);
        $mform->addHelpButton('timelimitinseconds', 'timelimit', 'qtype_recordrtc');
        $mform->setDefault('timelimitinseconds', qtype_recordrtc_question::TIMELIMIT_DEFAULT);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['mediatype'] == qtype_recordrtc_question::MEDIATYPE_VIDEO) {
            $errors['mediatype'] = get_string('err_videonotyet', 'qtype_recordrtc');
        }
        $maxtimelimit = get_config('qtype_recordrtc', 'timelimit');
        if ($data['timelimitinseconds'] === 0 || $data['timelimitinseconds'] > $maxtimelimit) {
            $a = new stdClass();
            $a->max = $maxtimelimit;
            $a->current = $data['timelimitinseconds'];
            $errors['timelimitinseconds'] = get_string('err_timliemit', 'qtype_recordrtc', $a);
        }
        return $errors;
    }

    public function qtype() {
        return 'recordrtc';
    }
}
