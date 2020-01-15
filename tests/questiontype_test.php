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
 * Contains the the record audio (and video) question type class.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/recordrtc/questiontype.php');
require_once($CFG->dirroot . '/question/type/recordrtc/edit_recordrtc_form.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');


/**
 * Unit tests for the the record audio (and video) question type question type class.
 */
class qtype_recordrtc_test extends question_testcase {

    protected $qtype;

    protected function setUp() {
        $this->qtype = new qtype_recordrtc();
    }

    protected function tearDown() {
        $this->qtype = null;
    }

    /**
     * Assert that two XML strings are essentially the same, ignoring irrelvant differences.
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
        return test_question_maker::get_question_data('recordrtc');
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

    public function test_question_saving() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category();

        $formdata = test_question_maker::get_question_form_data('recordrtc');
        $formdata->category = "{$cat->id},{$cat->contextid}";
        qtype_recordrtc_edit_form::mock_submit((array) $formdata);

        $questiondata = test_question_maker::get_question_data('recordrtc');
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

    public function test_xml_export() {
        $qdata = new stdClass();
        $qdata->id = 123;
        $qdata->contextid = context_system::instance()->id;
        $qdata->idnumber = null;
        $qdata->qtype = 'recordrtc';
        $qdata->name = 'Record audio question';
        $qdata->questiontext = '<p>Please record yourself talking about Moodle.</p>';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = '<p>I hope you spoke clearly and coherently.</p>';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 0;
        $qdata->hidden = 0;
        $qdata->options = new stdClass();
        $qdata->options->mediatype = 'audio';
        $qdata->options->timelimitinseconds = 30;

        $exporter = new qformat_xml();
        $xml = $exporter->writequestion($qdata);

        $expectedxml = '<!-- question: 123  -->
  <question type="recordrtc">
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
  </question>
';

        // Hack so the test passes in both 3.5 and 3.6.
        if (strpos($xml, 'idnumber') === false) {
            $expectedxml = str_replace("    <idnumber></idnumber>\n", '', $expectedxml);
        }

        $this->assert_same_xml($expectedxml, $xml);
    }
}
