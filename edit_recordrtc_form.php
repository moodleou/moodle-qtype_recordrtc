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
 * Defines the editing form for record audio and video questions.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/edit_question_form.php');


/**
 * The editing form for record audio and video questions.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc_edit_form extends question_edit_form {

    protected function definition_inner($mform) {

        // Field for mediatype.
        $mediaoptions = [
            qtype_recordrtc::MEDIA_TYPE_AUDIO => get_string('audio', 'qtype_recordrtc'),
            qtype_recordrtc::MEDIA_TYPE_VIDEO => get_string('video', 'qtype_recordrtc'),
            qtype_recordrtc::MEDIA_TYPE_CUSTOM_AV => get_string('customav', 'qtype_recordrtc')
        ];
        $mediatype = $mform->createElement('select', 'mediatype', get_string('mediatype', 'qtype_recordrtc'), $mediaoptions);
        $mform->insertElementBefore($mediatype, 'questiontext');
        $mform->addHelpButton('mediatype', 'mediatype', 'qtype_recordrtc');
        $mform->setDefault('mediatype', $this->get_default_value_wrapper('mediatype', qtype_recordrtc::MEDIA_TYPE_AUDIO));

        // Add instructions and widget placeholder templates for question authors to copy and paste into the question text.
        $qtype = new qtype_recordrtc();
        $audiowidget = $qtype->create_widget('recorder1', 'audio', '10m00s', true);
        $wideowidget = $qtype->create_widget('recorder2', 'video', '05m00s', true);
        $avplaceholder = $mform->createElement('static', 'avplaceholder', '', "$audiowidget &nbsp; $wideowidget");
        $avplaceholdergroup = $mform->createElement('group', 'avplaceholdergroup',
                get_string('avplaceholder', 'qtype_recordrtc'), [$avplaceholder]);
        $mform->hideIf('avplaceholdergroup', 'mediatype', 'noteq', qtype_recordrtc::MEDIA_TYPE_CUSTOM_AV);
        $mform->insertElementBefore($avplaceholdergroup, 'defaultmark');
        $mform->addHelpButton('avplaceholdergroup', 'avplaceholder', 'qtype_recordrtc');

        // Field for timelimitinseconds.
        $mform->addElement('duration', 'timelimitinseconds', get_string('timelimit', 'qtype_recordrtc'),
                ['units' => [60, 1], 'optional' => false]);
        $mform->addHelpButton('timelimitinseconds', 'timelimit', 'qtype_recordrtc');
        $mform->setDefault('timelimitinseconds',
                $this->get_default_value_wrapper('timelimitinseconds', qtype_recordrtc::DEFAULT_TIMELIMIT));
    }

    /**
     * Wrapper around get_default_value so we can still support older Moodle versions.
     *
     * @param string $name the name of the form field.
     * @param mixed $default default value.
     * @return string|null default value for a given form element.
     */
    protected function get_default_value_wrapper(string $name, $default): ?string {
        if (method_exists($this, 'get_default_value')) {
            return $this->get_default_value($name, $default);
        } else {
            return $default;
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate placeholders in the question text.
        $placeholdererrors = (new qtype_recordrtc)->validate_widget_placeholders($data['questiontext']['text'], $data['mediatype']);
        if ($placeholdererrors) {
            $errors['questiontext'] = $placeholdererrors;
        }

        // Validate the time limit.
        switch ($data['mediatype']) {
            case qtype_recordrtc::MEDIA_TYPE_AUDIO :
                $maxtimelimit = get_config('qtype_recordrtc', 'audiotimelimit');
                break;

            case qtype_recordrtc::MEDIA_TYPE_VIDEO :
            case qtype_recordrtc::MEDIA_TYPE_CUSTOM_AV :
                // We are using the 'Max video recording duration' for customav media type,
                // because it is shorter than 'Max audio recording duration' and we need to
                // use the value of $data['timelimitinseconds'] as default for widgets in
                // question text when the bespoke duration is not specified by the widget itself.
                $maxtimelimit = get_config('qtype_recordrtc', 'videotimelimit');
                break;

            default: // Should not get here.
                $maxtimelimit = qtype_recordrtc::DEFAULT_TIMELIMIT;
                break;
        }
        if ($data['timelimitinseconds'] > $maxtimelimit) {
            $errors['timelimitinseconds'] = get_string('err_timelimit', 'qtype_recordrtc', format_time($maxtimelimit));
        }
        if ($data['timelimitinseconds'] <= 0) {
            $errors['timelimitinseconds'] = get_string('err_timelimitpositive', 'qtype_recordrtc');
        }
        return $errors;
    }

    public function qtype() {
        return 'recordrtc';
    }
}
