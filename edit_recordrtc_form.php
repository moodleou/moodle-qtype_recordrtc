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

        // Field for mediatype.
        $mform->addElement('hidden', 'mediatype', 'audio');
        $mform->setType('mediatype', PARAM_ALPHA);

        // Field for timelimitinseconds.
        $mform->addElement('duration', 'timelimitinseconds', get_string('timelimit', 'qtype_recordrtc'),
                ['units' => [60, 1], 'optional' => false]);
        $mform->addHelpButton('timelimitinseconds', 'timelimit', 'qtype_recordrtc');
        $mform->setDefault('timelimitinseconds', qtype_recordrtc::DEFAULT_TIMELIMIT);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate placeholders in the question text.
        $placeholdererrors = (new qtype_recordrtc)->validate_widget_placeholders($data['questiontext']['text']);
        if ($placeholdererrors) {
            $errors['questiontext'] = $placeholdererrors;
        }

        // Validate the time.
        $maxtimelimit = get_config('qtype_recordrtc', 'timelimit');
        if ($data['timelimitinseconds'] <= 0) {
            $errors['timelimitinseconds'] = get_string('err_timelimitpositive', 'qtype_recordrtc');
        } else if ($data['timelimitinseconds'] > $maxtimelimit) {
            $errors['timelimitinseconds'] = get_string('err_timelimit', 'qtype_recordrtc',
                    format_time($maxtimelimit));
        }

        return $errors;
    }

    public function qtype() {
        return 'recordrtc';
    }
}
