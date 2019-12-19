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
 * Contains the helper class for the record audio (and video) question type.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the record audio (and video) question type.
 */
class qtype_recordrtc_test_helper extends question_test_helper {
    public function get_test_questions() {
        return ['audio'];
    }

    /**
     * Makes an audio question instance.
     *
     * @return qtype_recordrtc_question
     */
    public function make_recordrtc_question_audio() {
        question_bank::load_question_definition_classes('recordrtc');
        $q = new qtype_recordrtc_question();
        test_question_maker::initialise_a_question($q);
        $q->name = 'Record audio question';
        $q->questiontext = '<p>Please record yourself talking about Moodle.</p>';
        $q->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $q->qtype = question_bank::get_qtype('recordrtc');
        return $q;
    }

    /**
     * Make the data what would be received from the editing form for an audio question.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_recordrtc_question_form_data_audio() {
        $fromform = new stdClass();

        $fromform->name = 'Record audio question';
        $fromform->questiontext = ['text' => '<p>Please record yourself talking about Moodle.</p>', 'format' => FORMAT_HTML];
        $fromform->defaultmark = 1.0;
        $fromform->generalfeedback = ['text' => '<p>I hope you spoke clearly and coherently.</p>', 'format' => FORMAT_HTML];

        return $fromform;
    }

    /**
     * Make the data what would be received from the editing form for an audio question.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_recordrtc_question_data_audio() {
        $questiondata = new stdClass();
        test_question_maker::initialise_question_data($questiondata);

        $questiondata->qtype = 'recordrtc';
        $questiondata->name = 'Record audio question';
        $questiondata->questiontext = '<p>Please record yourself talking about Moodle.</p>';
        $questiondata->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';

        return $questiondata;
    }
}
