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
 * Contains the helper class for the record audio and video question type.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_recordrtc\widget_info;


/**
 * Test helper class for the record audio, video and screen question type.
 */
class qtype_recordrtc_test_helper extends question_test_helper {
    public function get_test_questions(): array {
        return ['audio', 'customav', 'screen'];
    }

    /**
     * Makes an audio question instance.
     *
     * @return qtype_recordrtc_question
     */
    public function make_recordrtc_question_audio(): qtype_recordrtc_question {
        question_bank::load_question_definition_classes('recordrtc');
        $q = new qtype_recordrtc_question();
        test_question_maker::initialise_a_question($q);
        $q->name = 'Record audio question';
        $q->questiontext = '<p>Please record yourself talking about Moodle.</p>';
        $q->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $q->qtype = question_bank::get_qtype('recordrtc');
        $q->widgets = ['recording' => new widget_info('recording', 'audio')];
        return $q;
    }

    /**
     * Make the data what would be received from the editing form for an audio question.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_recordrtc_question_form_data_audio(): stdClass {
        $fromform = new stdClass();

        $fromform->name = 'Record audio question';
        $fromform->questiontext = [
                'text' => '<p>Please record yourself talking about Moodle.</p>',
                'format' => FORMAT_HTML,
            ];
        $fromform->mediatype = 'audio';
        $fromform->timelimitinseconds = 30;
        $fromform->defaultmark = 1.0;
        $fromform->generalfeedback = [
                'text' => '<p>I hope you spoke clearly and coherently.</p>',
                'format' => FORMAT_HTML,
            ];

        return $fromform;
    }

    /**
     * Makes an screen question instance.
     *
     * @return qtype_recordrtc_question
     */
    public function make_recordrtc_question_screen(): qtype_recordrtc_question {
        question_bank::load_question_definition_classes('recordrtc');
        $q = new qtype_recordrtc_question();
        test_question_maker::initialise_a_question($q);
        $q->name = 'Record screen question';
        $q->questiontext = '<p>Please record your screen.</p>';
        $q->generalfeedback = '<p>I hope you have shown it clearly.</p>';
        $q->qtype = question_bank::get_qtype('recordrtc');
        $q->widgets = ['recording' => new widget_info('recording', 'screen')];
        return $q;
    }

    /**
     * Make the data what would be received from the editing form for an screen question.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_recordrtc_question_form_data_screen(): stdClass {
        $fromform = new stdClass();

        $fromform->name = 'Record screen question';
        $fromform->questiontext = [
            'text' => '<p>Please record your screen.</p>',
            'format' => FORMAT_HTML,
        ];
        $fromform->mediatype = 'screen';
        $fromform->timelimitinseconds = 30;
        $fromform->defaultmark = 1.0;
        $fromform->generalfeedback = [
            'text' => '<p>I hope you have shown it clearly.</p>',
            'format' => FORMAT_HTML,
        ];

        return $fromform;
    }

    /**
     * Make the data what would be received from the editing form for an audio question.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_recordrtc_question_data_audio(): stdClass {
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
        $questiondata->options->answers = [];

        return $questiondata;
    }

    /**
     * Makes an audio question instance with multiple audio widgets.
     *
     * @return qtype_recordrtc_question
     */
    public function make_recordrtc_question_customav(): qtype_recordrtc_question {
        question_bank::load_question_definition_classes('recordrtc');
        $q = new qtype_recordrtc_question();
        test_question_maker::initialise_a_question($q);
        $q->name = 'Record customav question';
        $q->questiontext = '<p>Please record yourself talking about following aspects of Moodle.</p>
                            <p>Development: [[development:audio]]</p>
                            <p>Installation: [[installation:audio]]</p>
                            <p>User experience: [[user_experience:audio]]</p>
                            <p>Share your action: [[action:screen]]</p>';
        $q->mediatype = 'customav';
        $q->timelimitinseconds = 30;
        $q->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $q->qtype = question_bank::get_qtype('recordrtc');
        $q->widgets = [
            'development' => new widget_info('development', 'audio', $q->timelimitinseconds),
            'installation' => new widget_info('installation', 'audio', $q->timelimitinseconds),
            'user_experience' => new widget_info('user_experience', 'audio', $q->timelimitinseconds),
            'action' => new widget_info('action', 'screen', $q->timelimitinseconds),
        ];
        $q->widgets['development']->placeholder = '[[development:audio]]';
        $q->widgets['development']->feedback = '<p>I hope you mentioned unit testing in your answer.</p>';
        $q->widgets['installation']->placeholder = '[[installation:audio]]';
        $q->widgets['installation']->feedback = '<p>Did you consider <i>Windows</i> servers as well as <i>Linux</i>?</p>';
        $q->widgets['user_experience']->placeholder = '[[user_experience:audio]]';
        $q->widgets['user_experience']->feedback = '<p>Least said about this the better!</p>';
        $q->widgets['action']->placeholder = '[[action:screen]]';
        $q->widgets['action']->feedback = '<p>Share your action</p>';

        return $q;
    }

    /**
     * Make the data what would be received from the editing form for an audio question.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_recordrtc_question_form_data_customav(): stdClass {
        $fromform = new stdClass();

        $fromform->name = 'Record customav question';
        $fromform->questiontext = [
                'text' => '<p>Please record yourself talking about following aspects of Moodle.</p>
                        <p>Development: [[development:audio]]</p>
                        <p>Installation: [[installation:audio]]</p>
                        <p>User experience: [[user_experience:audio]]</p>
                        <p>Share your action: [[action:screen]]</p>',
                'format' => FORMAT_HTML,
            ];
        $fromform->mediatype = 'customav';
        $fromform->timelimitinseconds = 30;
        $fromform->defaultmark = 1.0;
        $fromform->generalfeedback = [
                'text' => '<p>I hope you spoke clearly and coherently.</p>',
                'format' => FORMAT_HTML,
            ];
        $fromform->feedbackfordevelopment = [
                'text' => '<p>I hope you mentioned unit testing in your answer.</p>',
                'format' => FORMAT_HTML,
            ];
        $fromform->feedbackforinstallation = [
                'text' => '<p>Did you consider <i>Windows</i> servers as well as <i>Linux</i>?</p>',
                'format' => FORMAT_HTML,
            ];
        $fromform->feedbackforuser_experience = [
                'text' => '<p>Least said about this the better!</p>',
                'format' => FORMAT_HTML,
            ];
        $fromform->feedbackforaction = [
                'text' => '<p>Share your action</p>',
                'format' => FORMAT_HTML,
            ];
        return $fromform;
    }

    /**
     * Make the data what would be received from the editing form for an audio question.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_recordrtc_question_data_customav(): stdClass {
        $questiondata = new stdClass();
        test_question_maker::initialise_question_data($questiondata);

        $questiondata->qtype = 'recordrtc';
        $questiondata->name = 'Record customav question';
        $questiondata->questiontext = '<p>Please record yourself talking about following aspects of Moodle.</p>
                    <p>Development: [[development:audio]]</p>
                    <p>Installation: [[installation:audio]]</p>
                    <p>User experience: [[user_experience:audio]]</p>
                    <p>Share your action: [[action:screen]]</p>';
        $questiondata->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $questiondata->defaultmark = 1.0;

        $questiondata->options = new stdClass();
        $questiondata->options->mediatype = 'customav';
        $questiondata->options->timelimitinseconds = 30;
        $questiondata->options->answers = [
                14 => (object) [
                        'id' => 14,
                        'answer' => 'development',
                        'answerformat' => FORMAT_PLAIN,
                        'fraction' => 0,
                        'feedback' => '<p>I hope you mentioned unit testing in your answer.</p>',
                        'feedbackformat' => FORMAT_HTML,
                    ],
                15 => (object) [
                        'id' => 15,
                        'answer' => 'installation',
                        'answerformat' => FORMAT_PLAIN,
                        'fraction' => 0,
                        'feedback' => '<p>Did you consider <i>Windows</i> servers as well as <i>Linux</i>?</p>',
                        'feedbackformat' => FORMAT_HTML,
                    ],
                16 => (object) [
                        'id' => 16,
                        'answer' => 'user_experience',
                        'answerformat' => FORMAT_PLAIN,
                        'fraction' => 0,
                        'feedback' => '<p>Least said about this the better!</p>',
                        'feedbackformat' => FORMAT_HTML,
                    ],
                17 => (object) [
                        'id' => 17,
                        'answer' => 'action',
                        'answerformat' => FORMAT_PLAIN,
                        'fraction' => 0,
                        'feedback' => '<p>Share your action</p>',
                        'feedbackformat' => FORMAT_HTML,
                    ],
            ];
        return $questiondata;
    }

    /**
     * Creates an empty draft area for the recording.
     *
     * @return int The draft area's itemid.
     */
    protected static function make_recording_draft_area(): int {
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
     * @param string $filename To store the file under. Defaults to 'recording.ogg'.
     */
    public static function add_recording_to_draft_area(int $draftid, string $fixturefile, string $filename) {
        global $USER;

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);

        // Create the file in the provided draft area.
        $fileinfo = [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftid,
            'filepath'  => '/',
            'filename'  => $filename,
        ];
        $fs->create_file_from_pathname($fileinfo, __DIR__ . '/fixtures/' . $fixturefile);
    }

    /**
     * Delete all files from a particular draft file area for the current user.
     *
     * @param int $draftid The itemid for the draft area.
     */
    public static function clear_draft_area(int $draftid) {
        global $USER;
        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftid);
    }

    /**
     * Generates a draft file area that contains the given fixture files.
     * You should ensure that a user is logged in with setUser before you run this function.
     *
     * @param array $fixturefiles fixture file name => filename to save as.
     * @return int The itemid of the generated draft file area.
     */
    public static function make_recordings_in_draft_area(array $fixturefiles): int {
        $draftid = self::make_recording_draft_area();
        self::clear_draft_area($draftid);
        foreach ($fixturefiles as $fixturefile => $filename) {
            self::add_recording_to_draft_area($draftid, $fixturefile, $filename);
        }
        return $draftid;
    }

    /**
     * Generates a question_file_saver that contains the given fixture files
     * You should ensure that a user is logged in with setUser before you run this function.
     *
     * @param array $fixturefiles fixture file name => filename to save as.
     * @return question_file_saver a question_file_saver that contains the given amount of dummy files, for use in testing.
     */
    public static function make_recordings_saver(array $fixturefiles): question_file_saver {
        return new question_file_saver(self::make_recordings_in_draft_area($fixturefiles),
            'question', 'response_recording');
    }
}
