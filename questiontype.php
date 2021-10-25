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

use qtype_recordrtc\widget_info;

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
    const MAX_WIDGET_NAME_LENGTH = 32;

    /** @var string media type audio. */
    const MEDIA_TYPE_AUDIO = 'audio';

    /** @var string media type video. */
    const MEDIA_TYPE_VIDEO = 'video';

    /** @var string media type custom AV. */
    const MEDIA_TYPE_CUSTOM_AV = 'customav';

    /** @var string validate_widget_placeholders pattern. */
    const VALIDATE_WIDGET_PLACEHOLDERS = "/(\[\[)([A-Za-z0-9_-]+):([a-z]+)(:?([0-9]+m)?([0-9]+s)?)?(\]\])/";

    /** @var string get_widget_placeholders pattern. */
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

    public function save_defaults_for_new_questions(stdClass $fromform): void {
        parent::save_defaults_for_new_questions($fromform);
        $this->set_default_value('mediatype', $fromform->mediatype);
        $this->set_default_value('timelimitinseconds', $fromform->timelimitinseconds);
    }

    public function save_question_options($fromform) {
        global $DB;

        parent::save_question_options($fromform);
        if ($fromform->mediatype !== self::MEDIA_TYPE_CUSTOM_AV) {
            return;
        }

        $widgets = $this->get_widget_placeholders($fromform->questiontext);

        $context = $fromform->context;
        $oldanswers = $DB->get_records('question_answers',
                array('question' => $fromform->id), 'id ASC');

        // Insert all the new answers.
        foreach ($widgets as $widget) {
            $fieldname = 'feedbackfor' . $widget->name;

            // Check for, and ignore, completely blank answer from the form.
            if (html_is_blank($fromform->{$fieldname}['text'])) {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);
            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $fromform->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            $answer->answer = $widget->name;
            $answer->feedback = $this->import_or_save_files($fromform->{$fieldname},
                    $context, 'question', 'answerfeedback', $answer->id);
            $answer->feedbackformat = $fromform->{$fieldname}['format'];
            $DB->update_record('question_answers', $answer);
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);

        // Work out which widget placeholders we have.
        if ($questiondata->options->mediatype == self::MEDIA_TYPE_CUSTOM_AV) {
            $question->widgets = $this->get_widget_placeholders(
                    $questiondata->questiontext, $questiondata->options->timelimitinseconds);
        } else {
            $question->widgets = [];
        }
        if (empty($question->widgets)) {
            // There was no recorder in the question text. Add a default one.
            $widget = new widget_info('recording', $questiondata->options->mediatype,
                    $questiondata->options->timelimitinseconds);
            $question->questiontext .= html_writer::div($widget->placeholder);
            $question->widgets = [$widget->name => $widget];
        }

        // Prepare the per-widget feedback.
        foreach ($question->widgets as $widget) {
            foreach ($questiondata->options->answers as $answer) {
                if ($answer->answer == $widget->name) {
                    $widget->feedback = $answer->feedback;
                    $widget->feedbackformat = $answer->feedbackformat;
                    $widget->answerid = $answer->id;
                    break;
                }
            }
        }
    }

    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $output = '';
        $output .= '    <mediatype>' . $question->options->mediatype .
                "</mediatype>\n";
        $output .= '    <timelimitinseconds>' . $question->options->timelimitinseconds .
                "</timelimitinseconds>\n";
        $output .= $format->write_answers($question->options->answers);
        return $output;
    }

    public function import_from_xml($data, $question, qformat_xml $format, $extra = null) {
        $questiontype = $data['@']['type'];
        if ($questiontype != $this->name()) {
            return false;
        }

        $qo = $format->import_headers($data);
        $qo->qtype = $questiontype;

        $qo->mediatype = $format->getpath($data, array('#', 'mediatype', 0, '#'), self::MEDIA_TYPE_AUDIO);
        $qo->timelimitinseconds = $format->getpath($data, array('#', 'timelimitinseconds', 0, '#'),
                get_config('qtype_recordrtc', 'audiotimelimit'));

        // Load any answers and simulate the corresponding form data.
        if (isset($data['#']['answer'])) {
            foreach ($data['#']['answer'] as $answer) {
                $ans = $format->import_answer($answer);
                $fieldname = 'feedbackfor' . $ans->answer['text'];
                $qo->$fieldname = $ans->feedback;
            }
        }

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
        if (preg_match_all("/]]/", $qtext, $matches, PREG_SPLIT_NO_EMPTY, 0)) {
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
            // Validate widgetnames.
            $widgetnames = $matches[2];
            $widgetnamesused = [];
            foreach ($widgetnames as $widgetname) {
                if ($widgetname === '' || $widgetname === '-' || $widgetname === '_') {
                    $a->text = $widgetname;
                    return get_string('err_placeholdertitle', 'qtype_recordrtc', $a);
                }
                // The widgetname string exceeds the max length.
                if (strlen($widgetname) > self::MAX_WIDGET_NAME_LENGTH) {
                    $a->text = $widgetname;
                    $a->maxlength = self::MAX_WIDGET_NAME_LENGTH;
                    return get_string('err_placeholdertitlelength', 'qtype_recordrtc', $a);
                }
                if (preg_match('/[A-Z]/', $widgetname)) {
                    $a->text = $widgetname;
                    return get_string('err_placeholdertitlecase', 'qtype_recordrtc', $a);
                }
                if (isset($widgetnamesused[$widgetname])) {
                    $a->text = $widgetname;
                    return get_string('err_placeholdertitleduplicate', 'qtype_recordrtc', $a);
                }
                $widgetnamesused[$widgetname] = 1;
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
                    $placeholder = '[[' . $widgetnames[$key] . ':' . $mediatypes[$key] . $d . ']]';
                    if (!$d) {
                        continue;
                    }
                    $dur = trim($d, ':');
                    if (!$dur) {
                        return get_string('err_placeholdermissingduration', 'qtype_recordrtc', $placeholder);
                    }
                    $duration = widget_info::duration_to_seconds($dur);

                    if ($duration <= 0) {
                        return get_string('err_zeroornegativetimelimit', 'qtype_recordrtc', $dur);
                    }
                    if ($mediatypes[$key] === self::MEDIA_TYPE_AUDIO && $duration > $audiotimelimit) {
                        return get_string('err_audiotimelimit', 'qtype_recordrtc', $audiotimelimit);
                    }
                    if ($mediatypes[$key] === self::MEDIA_TYPE_VIDEO && $duration > $videotimelimit) {
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
     * @param int|null $questiontimelimit
     * @return widget_info[] indexed by widget name.
     */
    public function get_widget_placeholders(string $questiontext, int $questiontimelimit = null) : array {
        if ($questiontimelimit == null) {
            $questiontimelimit = self::DEFAULT_TIMELIMIT;
        }
        preg_match_all(self::GET_WIDGET_PLACEHOLDERS, $questiontext, $matches, PREG_SET_ORDER);
        $widgets = [];
        foreach ($matches as $match) {
            if ($match[3]) {
                $duration = widget_info::duration_to_seconds($match[3]);
            } else {
                $duration = $questiontimelimit;
            }
            $widget = new widget_info($match[1], $match[2], $duration);
            $widget->placeholder = $match[0];
            $widgets[$widget->name] = $widget;
        }
        return $widgets;
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
