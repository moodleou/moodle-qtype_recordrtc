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
 * A record audio and video question that is being attempted.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_recordrtc_question extends question_with_responses {
    use \qbehaviour_selfassess\question_with_self_assessment;

    /**
     * @var qtype_recordrtc\widget_info[] the widgets that appear in this question, indexed by the widget name.
     */
    public $widgets;

    /**
     * @var bool whether the user can pause in the middle of recording.
     */
    public $allowpausing;

    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        global $CFG;
        if (is_readable($CFG->dirroot . '/question/behaviour/selfassess/behaviour.php') &&
                question_engine::get_behaviour_type($preferredbehaviour)->can_questions_finish_during_the_attempt()) {
            return question_engine::make_behaviour('selfassess', $qa, $preferredbehaviour);
        } else {
            return question_engine::make_behaviour('manualgraded', $qa, $preferredbehaviour);
        }
    }

    public function get_expected_data(): array {
        return ['recording' => question_attempt::PARAM_FILES];
    }

    /**
     * Get the upload file size limit that applies here.
     *
     * @param context $context the context we are in.
     * @return int max size in bytes.
     */
    public function get_upload_size_limit(context $context): int {
        global $CFG;

        // This logic is roughly copied from lib/form/filemanager.php.

        // Get the course file size limit.
        $coursebytes = 0;
        [$context, $course] = get_context_info_array($context->id);
        if (is_object($course)) {
            $coursebytes = $course->maxbytes;
        }

        // TODO should probably also get the activity file size limit, but filemanager doesn't.

        return get_user_max_upload_file_size($context, $CFG->maxbytes, $coursebytes);
    }

    public function summarise_response(array $response) {
        if (!isset($response['recording']) || $response['recording'] === '') {
            return get_string('norecording', 'qtype_recordrtc');
        }
        $files = $response['recording']->get_files();
        $savedfiles = [];
        foreach ($this->widgets as $widget) {
            $filename = qtype_recordrtc::get_media_filename($widget->name, $widget->type);
            $file = $this->get_file_from_response($filename, $files);
            if ($file) {
                $savedfiles[] = s($file->get_filename());
            }
        }
        if (!$savedfiles) {
            return get_string('norecording', 'qtype_recordrtc');
        }
        return implode(', ', $savedfiles);
    }

    public function is_complete_response(array $response): bool {
        // Have all parts of the question been answered?
        if (!isset($response['recording']) || $response['recording'] === '') {
            return false;
        }

        $files = $response['recording']->get_files();
        foreach ($this->widgets as $widget) {
            $filename = qtype_recordrtc::get_media_filename($widget->name, $widget->type);
            if (!$this->get_file_from_response($filename, $files)) {
                return false;
            }
        }
        return true;
    }

    public function is_gradable_response(array $response): bool {
        // Has any parts of the question been answered? If so we might give partial credit.
        if (!isset($response['recording']) || $response['recording'] === '') {
            return false;
        }

        $files = $response['recording']->get_files();
        return !empty($files);
    }

    /**
     * Get a specific file from the array of files in a resonse (or null).
     *
     * To support questions answered with recording format OGG to MP3, before we switched back again,
     * if you are looking for file.ogg, and file.mp3 is found, then that is returned,
     * and $filename (passed by reference) is updated.
     *
     * @param string $filename the file we want.
     * @param stored_file[] $files all the files from a response (e.g. $response['recording']->get_files();)
     * @return stored_file|null the file, if it exists, or null if not.
     */
    public function get_file_from_response(string &$filename, array $files): ?stored_file {
        $legacyfilename = null;
        if (substr($filename, -4) === '.ogg') {
            $legacyfilename = substr($filename, 0, -4) . '.mp3';
        }
        foreach ($files as $file) {
            if ($file->get_filename() === $filename) {
                return $file;
            } else if ($file->get_filename() === $legacyfilename) {
                $filename = $legacyfilename;
                return $file;
            }
        }

        return null;
    }

    public function get_validation_error(array $response): string {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return get_string('pleaserecordsomethingineachpart', 'qtype_recordrtc');
    }

    public function is_same_response(array $prevresponse, array $newresponse): bool {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'recording');
    }

    public function get_correct_response(): ?array {
        // Not possible to give a correct response.
        return null;
    }

    public function check_file_access($qa, $options, $component, $filearea,
            $args, $forcedownload): bool {
        if ($component == 'question' && $filearea == 'response_recording') {
            // Response recording always accessible.
            return true;
        }

        if ($component == 'question' && $filearea == 'answerfeedback') {
            $answerid = reset($args);
            foreach ($this->widgets as $widget) {
                if ($answerid == $widget->answerid) {
                    // See comment in the renderer about why we check both.
                    return $options->feedback || $options->generalfeedback;
                }
            }
            return false; // Not one of ours.
        }

        return parent::check_file_access($qa, $options, $component, $filearea,
                $args, $forcedownload);
    }
}
