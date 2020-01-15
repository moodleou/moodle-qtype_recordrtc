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
        $q->mediatype = 'audio';
        $q->timelimitinseconds = 30;
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
        $fromform->mediatype = 'audio';
        $fromform->timelimitinseconds = 30;
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
        $questiondata->defaultmark = 1.0;

        $questiondata->options = new stdClass();
        $questiondata->options->mediatype = 'audio';
        $questiondata->options->timelimitinseconds = 30;

        return $questiondata;
    }

    /**
     * Creates an empty draft area for the recording.
     *
     * @return int The draft area's itemid.
     */
    protected static function make_recording_draft_area() {
        $draftid = 0;
        $contextid = 0;

        // Create an empty file area.
        file_prepare_draft_area($draftid, $contextid, 'question', 'response_recording', null);
        return $draftid;
    }

    /**
     * Creates a recording in the provided draft area.
     *
     * @param int $draftid The itemid for the draft area in which the file should be created.
     * @param string $fixturefile The name of the file in the fixtures folder to copy.
     */
    public static function add_recording_to_draft_area(int $draftid, string $fixturefile) {
        global $USER;

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);

        // If there is already a recording present, delete it.
        $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftid);

        // Create the file in the provided draft area.
        $fileinfo = [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftid,
            'filepath'  => '/',
            'filename'  => qtype_recordrtc::AUDIO_FILENAME,
        ];
        $fs->create_file_from_pathname($fileinfo, __DIR__ . '/fixtures/' . $fixturefile);
    }

    /**
     * Generates a draft file area that contains the given fixture file as a file called recording.ogg.
     * You should ensure that a user is logged in with setUser before you run this function.
     *
     * @param string $fixturefile The name of the file in the fixtures folder to copy.
     * @return int The itemid of the generated draft file area.
     */
    public static function make_recording_in_draft_area(string $fixturefile) {
        $draftid = self::make_recording_draft_area();
        self::add_recording_to_draft_area($draftid, $fixturefile);
        return $draftid;
    }

    /**
     * Generates a question_file_saver that contains the given fixture file
     * as a file called recording.ogg. You should ensure that a user is logged
     * in with setUser before you run this function.
     *
     * @param string $fixturefile The name of the file in the fixtures folder to copy.
     * @return question_file_saver a question_file_saver that contains the given amount of dummy files, for use in testing.
     */
    public static function make_recording_saver(string $fixturefile) {
        return new question_file_saver(self::make_recording_in_draft_area($fixturefile),
                'question', 'response_recording');
    }
}
