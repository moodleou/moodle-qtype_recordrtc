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
 * Question type class for the record audio and video question type.
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
 * The record audio and video question type question type.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc extends question_type {

    /** @var int default recording time limit in seconds. */
    const DEFAULT_TIMELIMIT = 30;

    /** @var int max length for media title. */
    const MAX_LENGTH_MEDIA_TITLE = 32;

    /** @var string media type audio */
    const MEDIA_TYPE_AUDIO = 'audio';

    /** @var string media type video */
    const MEDIA_TYPE_VIDEO = 'video';

    /** @var string media type custom AV  */
    const MEDIA_TYPE_CUSTOM_AV = 'customav';

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
        $mediatype = $questiondata->options->mediatype;
        parent::initialise_question_instance($question, $questiondata);
        $question->timelimitinseconds = $questiondata->options->timelimitinseconds;
        $question->mediatype = $questiondata->options->mediatype;
        $question->widgetplaceholders = $this->get_widget_placeholders($questiondata->questiontext);
        if (empty($question->widgetplaceholders)) {
            // There was no recorder in the question text. Add one placeholder to the question text with the title 'recording'.
            $question->questiontext .= html_writer::div('[[recording:' . $mediatype . ']]');

            // The widgetplaceholders array's key used as placeholder to be replaced with  an audi/video widegt.
            // The value is a array containing title (filename without extension) and the medaitype (audio or video).
            if ($mediatype === self::MEDIA_TYPE_AUDIO) {
                $question->widgetplaceholders =
                    ['[[recording:' . self::MEDIA_TYPE_AUDIO . ']]' => ['recording', self::MEDIA_TYPE_AUDIO]];
            } else {
                $question->widgetplaceholders =
                    ['[[recording:' . self::MEDIA_TYPE_VIDEO . ']]' => ['recording', self::MEDIA_TYPE_VIDEO]];
            }
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

        $qo->mediatype = $format->getpath($data, array('#', 'mediatype', 0, '#'), self::MEDIA_TYPE_AUDIO);
        $qo->timelimitinseconds = $format->getpath($data, array('#', 'timelimitinseconds', 0, '#'),
                get_config('timelimit', 'qtype_recordrtc'));

        return $qo;
    }

    /**
     * When there are placeholders in the question text, valiadte them and
     * return validation error and display the placeholders format to the question author.
     *
     * @param string $qtext
     * @param string $mediatype
     * @return string|null
     * @throws coding_exception
     */
    public function validate_widget_placeholders($qtext, $mediatype) {

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

        // If medatype is audio or video, custom placeholer is not allowed.
        if (($mediatype === self::MEDIA_TYPE_AUDIO || $mediatype === self::MEDIA_TYPE_VIDEO) && $matches[2]) {
            return get_string('err_placeholdernotallowed', 'qtype_recordrtc',
                get_string($mediatype, 'qtype_recordrtc'));
        }

        // If medatype is customav, there is need for custom placeholer(s).
        if ($mediatype === self::MEDIA_TYPE_CUSTOM_AV && !$matches[2]) {
            return get_string('err_placeholderneeded', 'qtype_recordrtc',
                get_string($mediatype, 'qtype_recordrtc'));
        }

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
                if ($mt !== self::MEDIA_TYPE_AUDIO && $mt !== self::MEDIA_TYPE_VIDEO) {
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
    public function get_widget_placeholders($questiontext) {
        preg_match_all('/\[\[([a-z0-9_-]+):(audio|video)]]/i', $questiontext, $matches, PREG_SET_ORDER);

        $widgetplaceholders = [];
        foreach ($matches as $match) {
            $widgetplaceholders[$match[0]] = [$match[1], $match[2]];
        }
        return $widgetplaceholders;
    }

    /**
     * Return the filename for a particular recorder.
     *
     * @param string $filename file base name without extension, E.g. 'recording-one'.
     * @param string $mediatype 'audio' or 'video'.
     * @return string the file name that should be used.
     */
    public static function get_media_filename(string $filename, string $mediatype) {
        if ($mediatype === self::MEDIA_TYPE_AUDIO) {
            return $filename . '.ogg';
        } else if ($mediatype === self::MEDIA_TYPE_VIDEO) {
            return $filename . '.webm';
        }
    }
}
