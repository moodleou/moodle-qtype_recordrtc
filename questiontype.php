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
 * The record audio and video question type.
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

    /** @var string media type audio. */
    const MEDIA_TYPE_HIDDEN_AUDIO = 'haudio';

    /** @var string media type video. */
    const MEDIA_TYPE_HIDDEN_VIDEO = 'hvideo';

    /** @var array all media types */
    const MEDIA_TYPES = [
        self::MEDIA_TYPE_AUDIO,
        self::MEDIA_TYPE_VIDEO,
        self::MEDIA_TYPE_CUSTOM_AV,
        self::MEDIA_TYPE_HIDDEN_AUDIO,
        self::MEDIA_TYPE_HIDDEN_VIDEO,
    ];

    const MEDIA_TYPES_WITHOUT_PREQUESTION = [
        self::MEDIA_TYPE_AUDIO,
        self::MEDIA_TYPE_VIDEO,
        self::MEDIA_TYPE_CUSTOM_AV,
    ];

    /** @var string validate_widget_placeholders pattern. */
    const VALIDATE_WIDGET_PLACEHOLDERS = "/\[\[([A-Za-z0-9 _-]+):([a-z]+)(:(?:[0-9]+m)?(?:[0-9]+s)?)?\]\]/";

    /** @var string get_widget_placeholders pattern. */
    const GET_WIDGET_PLACEHOLDERS = '/\[\[([a-z0-9_-]+):(audio|video):*([0-9]*m*[0-9]*s*)]]/i';

    public function is_manual_graded(): bool {
        return true;
    }

    public function response_file_areas(): array {
        return ['recording'];
    }

    /**
     * If your question type has a table that extends the question table, and
     * you want the base class to automatically save, backup and restore the extra fields,
     * override this method to return an array wherer the first element is the table name,
     * and the subsequent entries are the column names (apart from id and questionid).
     *
     * @return mixed array as above, or null to tell the base class to do nothing.
     */
    public function extra_question_fields(): array {
        return [
            'qtype_recordrtc_options',
            'mediatype', 'prequestion', 'timelimitinseconds', 'allowpausing', 'canselfrate', 'canselfcomment', 'denyrerecord',
        ];
    }

    public function save_defaults_for_new_questions(stdClass $fromform): void {
        global $CFG;

        parent::save_defaults_for_new_questions($fromform);
        $this->set_default_value('mediatype', $fromform->mediatype);
        $this->set_default_value('timelimitinseconds', $fromform->timelimitinseconds);
        $this->set_default_value('allowpausing', $fromform->allowpausing);
        $this->set_default_value('denyrerecord', $fromform->denyrerecord);
        if (is_readable($CFG->dirroot . '/question/behaviour/selfassess/behaviour.php')) {
            // These settings are only relevant if the behaviour is installed.
            $this->set_default_value('canselfrate', $fromform->canselfrate);
            $this->set_default_value('canselfcomment', $fromform->canselfcomment);
        }
    }

    /**
     * Saves question-type specific options
     * This is called by {@link save_question()} to save the question-type specific data
     *
     * You can process edit form here, before saving it in the DB
     * Saved fields depends on {@see extra_question_fields()}
     *
     * @param object $question  This holds the information from the editing form,
     *      it is not a standard question object.
     *
     * @return object|null $result->error or $result->notice
     */
    public function save_question_options($question) {
        $fromform = $question;

        // Save custom fields
        $prequestion_text = '';
        if (isset($fromform->prequestion['text'])) {
            /** For the preprocessing @see \qtype_recordrtc_edit_form::data_preprocessing_prequestion() */
            $prequestion_text = trim($fromform->prequestion['text']);

            if (!empty($prequestion_text) && !empty($fromform->prequestion['itemid'])) {
                $prequestion_text = file_save_draft_area_files($fromform->prequestion['itemid'],
                    $question->context->id, 'qtype_recordrtc', 'prequestion', (int)($question->id ?? null),
                    $this->fileoptions, $prequestion_text);
            }
        }
        $fromform->prequestion = $prequestion_text;

        $response = parent::save_question_options($fromform);

        // Update previous answers
        if ($fromform->mediatype === static::MEDIA_TYPE_CUSTOM_AV) {
            $this->update_custom_av_answers($fromform);
        }

        return $response;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);

        // Work out which widget placeholders we have.
        if ($questiondata->options->mediatype === static::MEDIA_TYPE_CUSTOM_AV) {
            $question->widgets = $this->get_widget_placeholders(
                    $questiondata->questiontext, $questiondata->options->timelimitinseconds);
        } else {
            $question->widgets = [];
        }
        if (empty($question->widgets)) {
            // There was no recorder in the question text. Add a default one.
            $widget = new widget_info('recording', $questiondata->options->mediatype,
                    $questiondata->options->timelimitinseconds);

            if (!$widget->is_hidden_type()){
                $question->questiontext .= html_writer::div($widget->placeholder);
            }
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

    public function export_to_xml($question, qformat_xml $format, $extra = null): string {
        $output = '    <mediatype>' . $question->options->mediatype .
                "</mediatype>\n";
        $output .= '    <timelimitinseconds>' . $question->options->timelimitinseconds .
                "</timelimitinseconds>\n";
        $output .= '    <allowpausing>' . $question->options->allowpausing . "</allowpausing>\n";
        $output .= '    <canselfrate>' . $question->options->canselfrate . "</canselfrate>\n";
        $output .= '    <canselfcomment>' . $question->options->canselfcomment . "</canselfcomment>\n";
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

        $qo->mediatype = $format->getpath($data, ['#', 'mediatype', 0, '#'], self::MEDIA_TYPE_AUDIO);
        $qo->timelimitinseconds = $format->getpath($data, ['#', 'timelimitinseconds', 0, '#'],
                get_config('qtype_recordrtc', 'audiotimelimit'));
        $qo->allowpausing = $format->getpath($data, ['#', 'allowpausing', 0, '#'], 0);
        $qo->canselfrate = $format->getpath($data, ['#', 'canselfrate', 0, '#'], 0);
        $qo->canselfcomment = $format->getpath($data, ['#', 'canselfcomment', 0, '#'], 0);

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
     * @param object $fromform
     *
     * @return void
     */
    protected function update_custom_av_answers($fromform){
        global $DB;
        if (empty($fromform) || $fromform->mediatype !== static::MEDIA_TYPE_CUSTOM_AV) {
            return;
        }

        $widgets = $this->get_widget_placeholders($fromform->questiontext);

        $context = $fromform->context;
        $oldanswers = $DB->get_records('question_answers',
            ['question' => $fromform->id], 'id ASC');

        // Insert all the new answers.
        foreach ($widgets as $widget) {
            $fieldname = 'feedbackfor' . $widget->name;

            // Check for, and ignore, when answer(feedback) is not set on the form.
            if (!isset($fromform->{$fieldname})) {
                continue;
            }
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

        // Delete any leftover old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', ['id' => $oldanswer->id]);
        }
    }

    /**
     * When there are placeholders in the question text, validate them.
     *
     * @param string $qtext the question text in which to validate the placeholders.
     * @param string $mediatype the overall media type of the question.
     * @return string[] with two elements, a description of the errors ('' if none) and a fixed question text, if possible.
     */
    public function validate_widget_placeholders(string $qtext, string $mediatype): array {

        // The placeholder format.
        $a = new stdClass();
        $a->format = get_string('err_placeholderformat', 'qtype_recordrtc');

        // Check correctness of open and close square brackets within the question text.
        $openingbrackets = substr_count($qtext, '[[');
        $closingbrackets = substr_count($qtext, ']]');
        if ($openingbrackets < $closingbrackets) {
            return [get_string('err_opensquarebrackets', 'qtype_recordrtc', $a), ''];
        } else if ($openingbrackets > $closingbrackets) {
            return [get_string('err_closesquarebrackets', 'qtype_recordrtc', $a), ''];
        }
        preg_match_all(self::VALIDATE_WIDGET_PLACEHOLDERS, $qtext, $matches);
        [$placeholders, $widgetnames, $widgettypes, $durations] = $matches;

        // If mediatype is customav, there is need for custom placeholer(s).
        if ($mediatype === static::MEDIA_TYPE_CUSTOM_AV) {
            if (!$matches[2]){
                return [get_string('err_placeholderneeded', 'qtype_recordrtc'), ''];
            }
        } else {
            // If media type is audio or video, custom place-holder is not allowed, and that is the only check required.
            if ($placeholders) {
                return [get_string('err_placeholdernotallowed', 'qtype_recordrtc',
                    get_string($mediatype, 'qtype_recordrtc')), ''];
            } else {
                return ['', ''];
            }
        }

        // Check all properties of all widgets, and collect the results.
        $allplacehodlerproblems = [];
        $wasfixed = false;

        // Validate widget names.
        $widgetnamesused = [];
        foreach ($widgetnames as $key => $widgetname) {
            // Characters used.
            if (preg_match('/[A-Z ]/', $widgetname)) {
                $a->text = $widgetname;
                $allplacehodlerproblems[] = get_string('err_placeholdertitlecase', 'qtype_recordrtc', $a);

                $fixedwidgetname = str_replace(' ', '_', core_text::strtolower($widgetname));
                $fixedplaceholder = substr($placeholders[$key], 0, 2) . $fixedwidgetname .
                        substr($placeholders[$key], 2 + strlen($widgetname));

                $qtext = str_replace($placeholders[$key], $fixedplaceholder, $qtext);
                $widgetnames[$key] = $fixedwidgetname;
                $widgetname = $fixedwidgetname;
                $placeholders[$key] = $fixedplaceholder;
                $wasfixed = true;

            } else if ($widgetname === '' || $widgetname === '-' || $widgetname === '_') {
                $a->text = $widgetname;
                $allplacehodlerproblems[] = get_string('err_placeholdertitle', 'qtype_recordrtc', $a);
            }

            // The widget name string exceeds the max length.
            if (strlen($widgetname) > self::MAX_WIDGET_NAME_LENGTH) {
                $a->text = $widgetname;
                $a->maxlength = self::MAX_WIDGET_NAME_LENGTH;
                $allplacehodlerproblems[] = get_string('err_placeholdertitlelength', 'qtype_recordrtc', $a);
            }

            // Uniqueness.
            if (isset($widgetnamesused[$widgetname])) {
                $a->text = $widgetname;
                $allplacehodlerproblems[] = get_string('err_placeholdertitleduplicate', 'qtype_recordrtc', $a);
            }
            $widgetnamesused[$widgetname] = 1;
        }

        // Validate media types.
        foreach ($widgettypes as $mt) {
            if ($mt !== self::MEDIA_TYPE_AUDIO && $mt !== self::MEDIA_TYPE_VIDEO) {
                $a->text = $mt;
                $allplacehodlerproblems[] = get_string('err_placeholdermediatype', 'qtype_recordrtc', $a);
            }
        }

        // Validate durations.
        $audiotimelimit = get_config('qtype_recordrtc', 'audiotimelimit');
        $videotimelimit = get_config('qtype_recordrtc', 'videotimelimit');
        foreach ($durations as $key => $d) {
            if (!$d) {
                continue;
            }

            $dur = trim($d, ':');
            if (!$dur) {
                $allplacehodlerproblems[] = get_string('err_placeholdermissingduration', 'qtype_recordrtc', $placeholders[$key]);
                $fixedplaceholder = substr($placeholders[$key], 0, -3) . substr($placeholders[$key], -2);
                $qtext = str_replace($placeholders[$key], $fixedplaceholder, $qtext);
                $placeholders[$key] = $fixedplaceholder;
                $wasfixed = true;
                continue;
            }

            $duration = widget_info::duration_to_seconds($dur);
            $type = $widgettypes[$key];
            if ($duration <= 0) {
                $allplacehodlerproblems[] = get_string('err_zeroornegativetimelimit', 'qtype_recordrtc', $dur);

            } else if ($duration > $audiotimelimit && ($type === static::MEDIA_TYPE_AUDIO || $type === static::MEDIA_TYPE_HIDDEN_AUDIO)) {
                $allplacehodlerproblems[] = get_string('err_audiotimelimit', 'qtype_recordrtc', $audiotimelimit);

            } else if ($duration > $videotimelimit && ($type === static::MEDIA_TYPE_VIDEO || $type === static::MEDIA_TYPE_HIDDEN_VIDEO)) {
                $allplacehodlerproblems[] = get_string('err_videotimelimit', 'qtype_recordrtc', $videotimelimit);
            }
        }

        // A media placeholder is not in a correct format.
        if (count($matches[0]) < $openingbrackets) {
            $allplacehodlerproblems[] = get_string('err_placeholderincorrectformat', 'qtype_recordrtc', $a);
        }

        // If there are any problems, explain the correct format.
        if ($allplacehodlerproblems) {
            $allplacehodlerproblems[] = get_string('err_placeholderformat', 'qtype_recordrtc');
        }

        return [implode('<br>', $allplacehodlerproblems), $wasfixed ? $qtext : ''];
    }

    /**
     * Returns an array of widget placeholders when there are placeholders in question text
     * and when there is no placeholder in the question text, add one as default.
     *
     * @param string $questiontext
     * @param int|null $questiontimelimit
     * @return widget_info[] indexed by widget name.
     */
    public function get_widget_placeholders(string $questiontext, int $questiontimelimit = null): array {
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
    public static function get_media_filename(string $filename, string $mediatype): string {
        if ($mediatype === static::MEDIA_TYPE_AUDIO || $mediatype === static::MEDIA_TYPE_HIDDEN_AUDIO) {
            return $filename . '.ogg';
        } else if ($mediatype === static::MEDIA_TYPE_VIDEO || $mediatype === static::MEDIA_TYPE_HIDDEN_VIDEO) {
            return $filename . '.webm';
        }
        throw new coding_exception('Unknown media type ' . $mediatype);
    }

    /**
     * Return possible media type options with translated names
     *
     * @return array
     */
    public static function get_media_type_options(): array {
        $options = [];
        foreach (static::MEDIA_TYPES as $media_type){
            $options[$media_type] = get_string($media_type, 'qtype_recordrtc');
        }

        return $options;
    }
}
