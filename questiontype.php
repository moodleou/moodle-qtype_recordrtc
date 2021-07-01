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

    /** @var string validate_widget_placeholders pattern */
    const VALIDATE_WIDGET_PLACEHOLDERS = "/(\[\[)([A-Za-z0-9_-]+):([a-z]+)(:?([0-9]+m)?([0-9]+s)?)?(\]\])/";

    /** @var string get_widget_placeholders pattern */
    const GET_WIDGET_PLACEHOLDERS = '/\[\[([a-z0-9_-]+):(audio|video):*([0-9]*m*[0-9]*s*)]]/i';


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
        $question->widgetplaceholders = $this->get_widget_placeholders($questiondata->questiontext, $question->timelimitinseconds);
        if (empty($question->widgetplaceholders)) {
            // There was no recorder in the question text. Add one placeholder to the question text with the title 'recording'.

            $question->questiontext .= html_writer::div(
                    $this->create_widget('recording', $question->mediatype, $question->timelimitinseconds, true));

            // The widgetplaceholders array's key used as placeholder to be replaced with  an audi/video widegt.
            // The value is an array containing title (filename without extension), medaitype (audio/video) and timelimit.
            $question->widgetplaceholders = $this->create_widget('recording', $question->mediatype, $question->timelimitinseconds);
        }
    }

    public function save_defaults_for_new_questions(stdClass $fromform): void {
        parent::save_defaults_for_new_questions($fromform);
        $this->set_default_value('mediatype', $fromform->mediatype);
        $this->set_default_value('timelimitinseconds', $fromform->timelimitinseconds);
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
                get_config('qtype_recordrtc', 'audiotimelimit'));

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
        preg_match_all(self::VALIDATE_WIDGET_PLACEHOLDERS, $qtext, $matches, PREG_PATTERN_ORDER, 0);

        // If medatype is audio or video, custom placeholer is not allowed.
        if (($mediatype === self::MEDIA_TYPE_AUDIO || $mediatype === self::MEDIA_TYPE_VIDEO) && $matches[2]) {
            return get_string('err_placeholdernotallowed', 'qtype_recordrtc',
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
                    return get_string('err_placeholdertitleduplicate', 'qtype_recordrtc', $a);
                }
                $titlesused[$title] = 1;
            }
            // Validate media types.
            $mediatypes = $matches[3];
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
            // If medatype is customav and duration is specified check duration validity.
            if ($mediatype === self::MEDIA_TYPE_CUSTOM_AV && $matches[4]) {
                // Validate durations.
                $audiotimelimit = get_config('qtype_recordrtc', 'audiotimelimit');
                $videotimelimit = get_config('qtype_recordrtc', 'videotimelimit');
                $durations = $matches[4];
                foreach ($durations as $key => $d) {
                    $placeholder = '[[' . $titles[$key] . ':' . $mediatypes[$key] . $d . ']]';
                    if (!$d) {
                        continue;
                    }
                    $dur = trim($d, ':');
                    if (!$dur) {
                        return get_string('err_placeholdermissingduration', 'qtype_recordrtc', $placeholder);
                    }
                    $duration = $this->convert_duration_to_seconds($dur);

                    if ($duration <= 0) {
                        return get_string('err_zeroornegativetimelimit', 'qtype_recordrtc', $dur);
                    }
                    if ($mediatypes[$key] === self::MEDIA_TYPE_AUDIO  && $duration > $audiotimelimit) {
                        return get_string('err_audiotimelimit', 'qtype_recordrtc', $audiotimelimit);
                    }
                    if ($mediatypes[$key] === self::MEDIA_TYPE_VIDEO  && $duration > $videotimelimit) {
                        return get_string('err_videotimelimit', 'qtype_recordrtc', $videotimelimit);
                    }
                }
            }
            // A media placeholder is not in a correct format.
            if (count($matches[0]) < $openingbrackets) {
                return get_string('err_placeholderincorrectformat', 'qtype_recordrtc', $a);
            }
            // If medatype is customav, there is need for custom placeholer(s).
            if ($mediatype === self::MEDIA_TYPE_CUSTOM_AV && !$matches[2]) {
                return get_string('err_placeholderneeded', 'qtype_recordrtc',
                    get_string($mediatype, 'qtype_recordrtc'));
            }
        }
        return null;
    }

    /**
     * Returns an array of widget placeholders when there are placeholders in question text
     * and when there is no placeholder in the question text, add one as default.
     *
     * @param $questiontext
     * @param int $questiontimelimit
     * @return array placeholder => [filename, mediatype, duration]
     */
    public function get_widget_placeholders(string $questiontext, int $questiontimelimit) : array {
        preg_match_all(self::GET_WIDGET_PLACEHOLDERS, $questiontext, $matches, PREG_SET_ORDER);
        $widgetplaceholders = [];
        foreach ($matches as $match) {
            if ($match[3]) {
                $duration = $this->convert_duration_to_seconds($match[3]);
            } else {
                $duration = $questiontimelimit;
            }
            $widgetplaceholders[$match[0]] = [$match[1], $match[2], $duration];
        }
        return $widgetplaceholders;
    }

    /**
     * Return an array as a widget placeholder.
     *
     * The array key is used as the widget placeholder to be replaced with an audi/video widegt when rendering.
     * The value is an array containing title (filename without extension), medaitype (audio/video) and timelimit.
     *
     * @param string $title
     * @param string $mediatype
     * @param string $timelimit
     * @param false $keyonly
     * @return array[]|string
     */
    public function create_widget(string $title, string $mediatype, string $timelimit, $placeholder = false) {
        $key = '[[' . $title . ':' . $mediatype . ':' . $timelimit . ']]';
        if ($placeholder) {
            return $key;
        }
        return  [$key => [$title, $mediatype, $this->convert_duration_to_seconds($timelimit)]];
    }

    /**
     * Return duration in seconds.
     *
     * @param string $duration duration
     * @return int duration in seconds.
     */
    public function convert_duration_to_seconds(string $duration) :int {
        $minutesandseconds = explode('m', $duration);
        // Only numbers followed by an 's'.
        if (count($minutesandseconds) === 1) {
            return trim($minutesandseconds[0], 's');
        }
        // Numbers followed by 'm', such as '1m', '2m'.
        if (!$minutesandseconds[1]) {
            return $minutesandseconds[0] * 60;
        }
        // Numbers followed by 'm', followed by numbers and 's' such as '1m20s'.
        if (is_number($minutesandseconds[1])) {
            $seconds = $minutesandseconds[1];
        } else {
            $seconds = trim($minutesandseconds[1], 's');
        }
        return ($minutesandseconds[0] * 60) + $seconds;
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
            return $filename . '.mp3';
        } else if ($mediatype === self::MEDIA_TYPE_VIDEO) {
            return $filename . '.webm';
        }
    }
}
