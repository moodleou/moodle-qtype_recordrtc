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
 * Contains the the record audio and video question type class.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use qtype_recordrtc\widget_info;

global $CFG;
require_once($CFG->dirroot . '/question/type/recordrtc/questiontype.php');
require_once($CFG->dirroot . '/question/type/recordrtc/edit_recordrtc_form.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');


/**
 * Unit tests for the the record audio and video question type question type class.
 */
class qtype_recordrtc_test extends question_testcase {

    protected $qtype;

    protected function setUp(): void {
        $this->qtype = new qtype_recordrtc();
    }

    /**
     * Assert that two XML strings are essentially the same, ignoring irrelevant differences.
     * @param string $expectedxml Expected.
     * @param string $xml Actual.
     */
    protected function assert_same_xml($expectedxml, $xml) {
        $this->assertEquals(str_replace("\r\n", "\n", $expectedxml),
                str_replace("\r\n", "\n", $xml));
    }

    /**
     * Get the data representing a question, in the form returned by load_question.
     * @return stdClass
     */
    protected function get_test_question_data() {
        return test_question_maker::get_question_data('recordrtc', 'customav');
    }

    public function test_name() {
        $this->assertEquals($this->qtype->name(), 'recordrtc');
    }

    public function test_can_analyse_responses() {
        $this->assertFalse($this->qtype->can_analyse_responses());
    }

    public function test_get_random_guess_score() {
        $this->assertEquals(0, $this->qtype->get_random_guess_score($this->get_test_question_data()));
    }

    public function test_get_possible_responses() {
        $this->assertEquals([], $this->qtype->get_possible_responses($this->get_test_question_data()));
    }

    public function test_get_audio_filename() {
        $this->assertEquals('recording.mp3', $this->qtype->get_media_filename('recording', 'audio'));
    }

    public function test_get_video_filename() {
        $this->assertEquals('name.webm', $this->qtype->get_media_filename('name', 'video'));
    }

    public function test_get_widget_placeholders_no_placeholder() {
        $questiontext = 'Record your answer about your experience doing this Module.';
        $this->assertEquals([], $this->qtype->get_widget_placeholders($questiontext));
    }

    public function test_get_widget_placeholders_with_placeholders() {
        $questiontext = 'Record the answers:
        What is your name? [[name:audio]] Where do you live [[place:audio:1m10s]]';
        $timelimitinseconds = 15;
        $expected = [
                'name' => new widget_info('name', 'audio', 15),
                'place' => new widget_info('place', 'audio', 70),
            ];
        $expected['name']->placeholder = '[[name:audio]]';
        $expected['place']->placeholder = '[[place:audio:1m10s]]';
        $this->assertEquals($expected, $this->qtype->get_widget_placeholders($questiontext, $timelimitinseconds));
    }

    public function test_validate_widget_placeholders_valid() {
        $a = new \stdClass();
        $a->format = get_string('err_placeholderformat', 'qtype_recordrtc');

        // Valid question text.
        $questiontext = 'Record the answers:
        What is your name? [[name:audio]] Where do you live [[place:audio]]';
        $expected = null;
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'customav');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_missing_open() {
        $a = new \stdClass();
        $a->format = get_string('err_placeholderformat', 'qtype_recordrtc');

        // Missing [[.
        $questiontext = 'Record the answers:
        What is your name? [[name:audio]] Where do you live [place:audio]]';
        $expected = get_string('err_opensquarebrackets', 'qtype_recordrtc', $a);
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'audio');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_missing_close() {
        $a = new \stdClass();
        $a->format = get_string('err_placeholderformat', 'qtype_recordrtc');

        // Missing ]].
        $questiontext = 'Record the answers:
        What is your name? [[name:audio] Where do you live [[place:audio]]';
        $expected = get_string('err_closesquarebrackets', 'qtype_recordrtc', $a);
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'audio');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_invalid() {
        $a = new \stdClass();
        $a->format = get_string('err_placeholderformat', 'qtype_recordrtc');

        // Invalid placeholder.
        $questiontext = 'Record the answers:
        What is your name? [[name;audio]] Where do you live [[place:audio]]';
        $expected = get_string('err_placeholderincorrectformat', 'qtype_recordrtc', $a);
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'customav');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_missing_duration() {
        $questiontext = 'Record the answers: What is your name? [[name:audio:]] Where do you live [[place:audio:02m10s]]';
        $expected = get_string('err_placeholdermissingduration', 'qtype_recordrtc', '[[name:audio:]]');
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'customav');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_zero_duration() {
        $questiontext = 'Record the answers: What is your name? [[name:audio:00m00s]] Where do you live [[place:audio:02m10s]]';
        $expected = get_string('err_zeroornegativetimelimit', 'qtype_recordrtc', '00m00s');
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'customav');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_too_long() {
        $a = new \stdClass();
        $a->format = get_string('err_placeholderformat', 'qtype_recordrtc');

        // Invalid placeholder title.
        $questiontext = 'Record the answers:
        What is your name? [[this-is-a-long-placeholder-title-more-than-32-chars:audio]] ' .
                'Where do you live [[place:audio]]';
        $a->text = 'this-is-a-long-placeholder-title-more-than-32-chars';
        $a->maxlength = qtype_recordrtc::MAX_WIDGET_NAME_LENGTH;
        $expected = get_string('err_placeholdertitlelength', 'qtype_recordrtc', $a);
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'customav');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_unknown_media_type() {
        $a = new \stdClass();
        $a->format = get_string ('err_placeholderformat', 'qtype_recordrtc');

        // Invalid placeholder media type.
        $questiontext = 'Record the answers:
        What is your name? [[name:audiox]] Where do you live [[place:audio]]';
        $a->text = 'audiox';
        $expected = get_string ('err_placeholdermediatype', 'qtype_recordrtc', $a);
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'customav');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_upper_case() {
        $a = new \stdClass();
        $a->format = get_string('err_placeholderformat', 'qtype_recordrtc');

        // Valid question text.
        $questiontext = 'Record the answers:
        Where do you live? [[Place:audio]]';
        $a->text = 'Place';
        $expected = get_string ('err_placeholdertitlecase', 'qtype_recordrtc', $a);
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'customav');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_duplicate() {
        $a = new \stdClass();
        $a->format = get_string('err_placeholderformat', 'qtype_recordrtc');

        // Valid question text.
        $questiontext = 'Record the answers:
        Where do you live? [[place:audio]], Where were you born? [[place:audio]]';
        $a->text = 'place';
        $expected = get_string('err_placeholdertitleduplicate', 'qtype_recordrtc', $a);
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'customav');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_not_allowed() {

        // Placeholder(s) provided within the question text with mediatype set to 'audio'.
        $questiontext = 'Record the answers by saying your name [[name:audio]]';
        $expected = get_string('err_placeholdernotallowed', 'qtype_recordrtc',
            get_string('audio', 'qtype_recordrtc'));
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'audio');
        $this->assertEquals($expected, $actual);
    }

    public function test_validate_widget_placeholders_needed() {
        // No placeholder(s) provided within the question text with mediatype set to 'custonav'.
        $questiontext = 'Record the answers:
        What is your name? Where do you live?';
        $expected = get_string('err_placeholderneeded', 'qtype_recordrtc',
            get_string('customav', 'qtype_recordrtc'));
        $actual = $this->qtype->validate_widget_placeholders($questiontext, 'customav');
        $this->assertEquals($expected, $actual);
    }

    public function test_question_saving() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();

        $formdata = test_question_maker::get_question_form_data('recordrtc', 'customav');
        $formdata->category = "{$cat->id},{$cat->contextid}";
        qtype_recordrtc_edit_form::mock_submit((array) $formdata);

        $questiondata = test_question_maker::get_question_data('recordrtc', 'customav');
        $form = question_test_helper::get_question_editing_form($cat, $questiondata);

        $this->assertTrue($form->is_validated());

        $fromform = $form->get_data();

        $returnedfromsave = $this->qtype->save_question($questiondata, $fromform);
        $actualquestionsdata = question_load_questions(array($returnedfromsave->id));
        $actualquestiondata = end($actualquestionsdata);

        foreach ($questiondata as $property => $value) {
            if (!in_array($property, array('id', 'version', 'timemodified', 'timecreated', 'options'))) {
                $this->assertEquals($value, $actualquestiondata->$property);
            }
        }
    }

    public function test_xml_import() {
        $xml = '  <question type="recordrtc">
    <name>
      <text>Record audio question</text>
    </name>
    <questiontext format="html">
      <text><![CDATA[<p>Please record yourself talking about Moodle.</p>]]></text>
    </questiontext>
    <generalfeedback format="html">
      <text><![CDATA[<p>I hope you spoke clearly and coherently.</p>]]></text>
    </generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <mediatype>audio</mediatype>
    <timelimitinseconds>30</timelimitinseconds>
  </question>';
        $xmldata = xmlize($xml);

        $importer = new qformat_xml();
        $q = $importer->try_importing_using_qtypes($xmldata['question']);

        $expectedq = new stdClass();
        $expectedq->qtype = 'recordrtc';
        $expectedq->name = 'Record audio question';
        $expectedq->questiontext = '<p>Please record yourself talking about Moodle.</p>';
        $expectedq->questiontextformat = FORMAT_HTML;
        $expectedq->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $expectedq->generalfeedbackformat = FORMAT_HTML;
        $expectedq->defaultmark = 1;
        $expectedq->length = 1;
        $expectedq->penalty = 0;
        $expectedq->mediatype = 'audio';
        $expectedq->timelimitinseconds = 30;

        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_xml_import_custom_av() {
        $xml = '  <question type="recordrtc">
    <name>
      <text>Record audio question</text>
    </name>
    <questiontext format="html">
      <text><![CDATA[<p>Please record yourself talking about following aspects of Moodle.</p>
        <p>Development: [[development:audio]]</p>
        <p>Installation: [[installation:audio]]</p>
        <p>User experience: [[user_experience:audio]]</p>]]></text>
    </questiontext>
    <generalfeedback format="html">
      <text><![CDATA[<p>I hope you spoke clearly and coherently.</p>]]></text>
    </generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <mediatype>customav</mediatype>
    <timelimitinseconds>30</timelimitinseconds>
    <answer fraction="0" format="plain_text">
      <text>development</text>
      <feedback format="html">
        <text><![CDATA[<p>I hope you mentioned unit testing in your answer.</p>]]></text>
      </feedback>
    </answer>
    <answer fraction="0" format="plain_text">
      <text>installation</text>
      <feedback format="html">
        <text><![CDATA[<p>Did you consider <i>Windows</i> servers as well as <i>Linux</i>?</p>]]></text>
      </feedback>
    </answer>
    <answer fraction="0" format="plain_text">
      <text>user_experience</text>
      <feedback format="html">
        <text><![CDATA[<p>Least said about this the better!</p>]]></text>
      </feedback>
    </answer>
  </question>';
        $xmldata = xmlize($xml);

        $importer = new qformat_xml();
        $q = $importer->try_importing_using_qtypes($xmldata['question']);

        $expectedq = new stdClass();
        $expectedq->qtype = 'recordrtc';
        $expectedq->name = 'Record audio question';
        $expectedq->questiontext = '<p>Please record yourself talking about following aspects of Moodle.</p>
        <p>Development: [[development:audio]]</p>
        <p>Installation: [[installation:audio]]</p>
        <p>User experience: [[user_experience:audio]]</p>';
        $expectedq->questiontextformat = FORMAT_HTML;
        $expectedq->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $expectedq->generalfeedbackformat = FORMAT_HTML;
        $expectedq->defaultmark = 1;
        $expectedq->length = 1;
        $expectedq->penalty = 0;
        $expectedq->mediatype = 'customav';
        $expectedq->timelimitinseconds = 30;
        $expectedq->feedbackfordevelopment = [
                'text' => '<p>I hope you mentioned unit testing in your answer.</p>',
                'format' => FORMAT_HTML,
            ];
        $expectedq->feedbackforinstallation = [
                'text' => '<p>Did you consider <i>Windows</i> servers as well as <i>Linux</i>?</p>',
                'format' => FORMAT_HTML,
            ];
        $expectedq->feedbackforuser_experience = [
                'text' => '<p>Least said about this the better!</p>',
                'format' => FORMAT_HTML,
            ];

        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_xml_import_custom_av_with_empty_feedback() {
        $xml = '  <question type="recordrtc">
    <name>
      <text>Record audio question</text>
    </name>
    <questiontext format="html">
      <text><![CDATA[<p>Please record yourself talking about following aspects of Moodle.</p>
        <p>Development: [[development:audio]]</p>
        <p>Installation: [[installation:audio]]</p>
        <p>User experience: [[user_experience:audio]]</p>]]></text>
    </questiontext>
    <generalfeedback format="html">
      <text><![CDATA[<p>I hope you spoke clearly and coherently.</p>]]></text>
    </generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <mediatype>customav</mediatype>
    <timelimitinseconds>30</timelimitinseconds>
    <answer fraction="0" format="plain_text">
      <text>development</text>
      <feedback format="html">
        <text><![CDATA[<p>I hope you mentioned unit testing in your answer.</p>]]></text>
      </feedback>
    </answer>
    <answer fraction="0" format="plain_text">
      <text>installation</text>
      <feedback format="html">
        <text><![CDATA[<p>Did you consider <i>Windows</i> servers as well as <i>Linux</i>?</p>]]></text>
      </feedback>
    </answer>
    <answer fraction="0" format="plain_text">
      <text>user_experience</text>
      <feedback format="html">
        <text></text>
      </feedback>
    </answer>
  </question>';
        $xmldata = xmlize($xml);

        $importer = new qformat_xml();
        $q = $importer->try_importing_using_qtypes($xmldata['question']);

        $expectedq = new stdClass();
        $expectedq->qtype = 'recordrtc';
        $expectedq->name = 'Record audio question';
        $expectedq->questiontext = '<p>Please record yourself talking about following aspects of Moodle.</p>
        <p>Development: [[development:audio]]</p>
        <p>Installation: [[installation:audio]]</p>
        <p>User experience: [[user_experience:audio]]</p>';
        $expectedq->questiontextformat = FORMAT_HTML;
        $expectedq->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $expectedq->generalfeedbackformat = FORMAT_HTML;
        $expectedq->defaultmark = 1;
        $expectedq->length = 1;
        $expectedq->penalty = 0;
        $expectedq->mediatype = 'customav';
        $expectedq->timelimitinseconds = 30;
        $expectedq->feedbackfordevelopment = [
                'text' => '<p>I hope you mentioned unit testing in your answer.</p>',
                'format' => FORMAT_HTML,
        ];
        $expectedq->feedbackforinstallation = [
                'text' => '<p>Did you consider <i>Windows</i> servers as well as <i>Linux</i>?</p>',
                'format' => FORMAT_HTML,
        ];
        $expectedq->feedbackforuser_experience = [
                'text' => '',
                'format' => FORMAT_HTML,
        ];

        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_xml_import_custom_av_no_feedback() {
        $xml = '  <question type="recordrtc">
    <name>
      <text>Record audio question</text>
    </name>
    <questiontext format="html">
      <text><![CDATA[<p>Please record yourself talking about following aspects of Moodle.</p>
        <p>Development: [[development:audio]]</p>
        <p>Installation: [[installation:audio]]</p>
        <p>User experience: [[user_experience:audio]]</p>]]></text>
    </questiontext>
    <generalfeedback format="html">
      <text><![CDATA[<p>I hope you spoke clearly and coherently.</p>]]></text>
    </generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <mediatype>customav</mediatype>
    <timelimitinseconds>30</timelimitinseconds>
  </question>';
        $xmldata = xmlize($xml);

        $importer = new qformat_xml();
        $q = $importer->try_importing_using_qtypes($xmldata['question']);

        $expectedq = new stdClass();
        $expectedq->qtype = 'recordrtc';
        $expectedq->name = 'Record audio question';
        $expectedq->questiontext = '<p>Please record yourself talking about following aspects of Moodle.</p>
        <p>Development: [[development:audio]]</p>
        <p>Installation: [[installation:audio]]</p>
        <p>User experience: [[user_experience:audio]]</p>';
        $expectedq->questiontextformat = FORMAT_HTML;
        $expectedq->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $expectedq->generalfeedbackformat = FORMAT_HTML;
        $expectedq->defaultmark = 1;
        $expectedq->length = 1;
        $expectedq->penalty = 0;
        $expectedq->mediatype = 'customav';
        $expectedq->timelimitinseconds = 30;
        $this->assert(new question_check_specified_fields_expectation($expectedq), $q);
    }

    public function test_xml_export() {
        $qdata = new stdClass();
        $qdata->id = 123;
        $qdata->contextid = context_system::instance()->id;
        $qdata->idnumber = null;
        $qdata->qtype = 'recordrtc';
        $qdata->name = 'Record audio question';
        $qdata->questiontext = '<p>Please record yourself talking about following aspects of Moodle.</p>
        <p>Development: [[development:audio]]</p>
        <p>Installation: [[installation:audio]]</p>
        <p>User experience: [[user_experience:audio]]</p>';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 0;
        $qdata->hidden = 0;
        $qdata->options = new stdClass();
        $qdata->options->mediatype = 'customav';
        $qdata->options->timelimitinseconds = 30;
        $qdata->options->answers = [
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
            ];

        $exporter = new qformat_xml();
        $xml = $exporter->writequestion($qdata);

        $expectedxml = '<!-- question: 123  -->
  <question type="recordrtc">
    <name>
      <text>Record audio question</text>
    </name>
    <questiontext format="html">
      <text><![CDATA[<p>Please record yourself talking about following aspects of Moodle.</p>
        <p>Development: [[development:audio]]</p>
        <p>Installation: [[installation:audio]]</p>
        <p>User experience: [[user_experience:audio]]</p>]]></text>
    </questiontext>
    <generalfeedback format="html">
      <text><![CDATA[<p>I hope you spoke clearly and coherently.</p>]]></text>
    </generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0</penalty>
    <hidden>0</hidden>
    <idnumber></idnumber>
    <mediatype>customav</mediatype>
    <timelimitinseconds>30</timelimitinseconds>
    <answer fraction="0" format="plain_text">
      <text>development</text>
      <feedback format="html">
        <text><![CDATA[<p>I hope you mentioned unit testing in your answer.</p>]]></text>
      </feedback>
    </answer>
    <answer fraction="0" format="plain_text">
      <text>installation</text>
      <feedback format="html">
        <text><![CDATA[<p>Did you consider <i>Windows</i> servers as well as <i>Linux</i>?</p>]]></text>
      </feedback>
    </answer>
    <answer fraction="0" format="plain_text">
      <text>user_experience</text>
      <feedback format="html">
        <text><![CDATA[<p>Least said about this the better!</p>]]></text>
      </feedback>
    </answer>
  </question>
';

        // Hack so the test passes in both 3.5 and 3.6.
        if (strpos($xml, 'idnumber') === false) {
            $expectedxml = str_replace("    <idnumber></idnumber>\n", '', $expectedxml);
        }

        $this->assert_same_xml($expectedxml, $xml);
    }
}
