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

    /** @var int default recording time limit in seconds. */
    const DEFAULT_TIMELIMIT = 30;

    /** @var int max length for media title. */
    const MAX_LENGTH_MEDIA_TITLE = 32;

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
        $question->widgetplaceholders = $this->get_widget_placeholders($questiondata->questiontext);
        if (empty($question->widgetplaceholders)) {
            // There was no recorder in the question text. Add one for the renderer.
            $question->questiontext .= html_writer::div('[[audio]]');
            $question->widgetplaceholders = ['[[audio]]' => 'recording.ogg'];
        }
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

    /**
     * When there are placeholders in the question text, valiadte them and
     * return validation error and display the placeholders format to the question author.
     *
     * @param $qtext
     * @return string|null
     * @throws coding_exception
     */
    public function validate_widget_placeholders($qtext) {

        // The placeholder format.
        $a = new \stdClass();
        $a->text = null;
        $a->format = get_string('err_placeholderformat', 'qtype_recordrtc');

        // Check correctness of open and close square brackets within the question text.
        $openingbrackets = 0;
        $closingbrackets = 0;
        if (preg_match_all("/\[\[/", $qtext, $matches, PREG_SPLIT_NO_EMPTY, 0)) {
            $openingbrackets = count($matches[0]);
        }
        if (preg_match_all("/\]\]/", $qtext, $matches, PREG_SPLIT_NO_EMPTY, 0)) {
            $closingbrackets = count($matches[0]);
        }
        if ($openingbrackets || $closingbrackets) {
            if ($openingbrackets < $closingbrackets) {
                return get_string('err_opensquarebrackets', 'qtype_recordrtc', $a);
            }
            if ($openingbrackets > $closingbrackets) {
                return get_string('err_closesquarebrackets', 'qtype_recordrtc', $a);
            }
        }
        $pattern = "/(\[\[)([A-Za-z0-9_-]+)(:)([a-z]+)(]])/";
        preg_match_all($pattern, $qtext, $matches, PREG_PATTERN_ORDER, 0);
        if ($matches) {
            // Validate titles.
            $titles = $matches[2];
            $titlesused = [];
            foreach ($titles as $key => $title) {
                if ($title === '' || $title === '-' || $title === '_') {
                    $a->text = $title;
                    return get_string('err_placeholdertitle', 'qtype_recordrtc', $a);
                }
                // The title string exeeds the max length.
                if (strlen($title) > self::MAX_LENGTH_MEDIA_TITLE) {
                    $a->text = $title;
                    $a->maxlength = self::MAX_LENGTH_MEDIA_TITLE;
                    return get_string('err_placeholdertitlelength', 'qtype_recordrtc', $a);
                }
                if (preg_match('/[A-Z]/', $title)) {
                    $a->text = $title;
                    return get_string('err_placeholdertitlecase', 'qtype_recordrtc', $a);
                }
                if (isset($titlesused[$title])) {
                    $a->text = $title;
                    return get_string('err_placeholdertitleduplicate', 'qtype_recordrtc', $title);
                }
                $titlesused[$title] = 1;
            }
            // Validate media types.
            $mediatypes = $matches[4];
            foreach ($mediatypes as $key => $mt) {
                if ($mt !== 'audio') {
                    $a->text = $mt;
                    return get_string('err_placeholdermediatype', 'qtype_recordrtc', $a);
                }
            }
            // A media placeholder is not in a correct format.
            if (count($matches[0]) < $openingbrackets) {
                return get_string('err_placeholderincorrectformat', 'qtype_recordrtc', $a);
            }
        }
        return null;
    }

    /**
     * Returns an array of widget placeholders when there are placeholders in question text
     * and when there is no placeholder in the question text, add one as default.
     *
     * @param $questiontext
     * @return array placeholder => filename
     */
    public function get_widget_placeholders($questiontext)
    {
        preg_match_all('/\[\[([a-z0-9_-]+):(audio|video)]]/i', $questiontext, $matches, PREG_SET_ORDER);

        $widgetplaceholders = [];
        foreach ($matches as $match) {
            $widgetplaceholders[$match[0]] = $this->get_media_filename($match[1], $match[2]);
        }
        return $widgetplaceholders;
    }

    /**
     * Return the filename for a particular recorder.
     *
     * @param string $filename file base name. E.g. 'recording-one'.
     * @param string $mediatype 'audio' or 'video'.
     * @return string the file name that should be used.
     */
    public function get_media_filename(string $filename, string $mediatype) {
        if ($mediatype === 'audio') {
            return $filename . '.ogg';
        } else {
            return $filename . '.webm';
        }
    }
}
