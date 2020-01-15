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
 * Question type class for the record audio (and video) question type.
 *
 * @package   qtype_recordrtc
 * @copyright The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/recordrtc/question.php');


/**
 * The record audio (and video) question type question type.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc extends question_type {
    /** @var string name of the audio recording within the file area. */
    const AUDIO_FILENAME = 'recording.ogg';

    public function is_manual_graded() {
        return true;
    }

    public function response_file_areas() {
        return ['recording'];
    }

    public function extra_question_fields() {
        return array('qtype_recordrtc_options', 'mediatype', 'timelimitinseconds');
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->timelimitinseconds = $questiondata->options->timelimitinseconds;
        $question->mediatype = $questiondata->options->mediatype;
    }

    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $output = '';
        $output .= '    <mediatype>' . $question->options->mediatype .
                "</mediatype>\n";
        $output .= '    <timelimitinseconds>' . $question->options->timelimitinseconds .
                "</timelimitinseconds>\n";
        return $output;
    }

    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        $questiontype = $data['@']['type'];
        if ($questiontype != $this->name()) {
            return false;
        }

        $qo = $format->import_headers($data);
        $qo->qtype = $questiontype;

        $qo->mediatype = $format->getpath($data, array('#', 'mediatype', 0, '#'), 'audio');
        $qo->timelimitinseconds = $format->getpath($data, array('#', 'timelimitinseconds', 0, '#'),
                get_config('timelimit', 'qtype_recordrtc'));

        return $qo;
    }
}
