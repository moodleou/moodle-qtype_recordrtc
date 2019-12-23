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

    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        // We don't need to export any settings (yet) but we need to return a non-empty string
        // to tell the system that we support export.
        return ' ';
    }

    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        $questiontype = $data['@']['type'];
        if ($questiontype != $this->name()) {
            return false;
        }

        $qo = $format->import_headers($data);
        $qo->qtype = $questiontype;

        return $qo;
    }
}
