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
 * Recordrtc question type restore handler
 *
 * @package    qtype_recordrtc
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore plugin class that provides the necessary information needed to restore one ordering qtype plugin
 *
 * @package    qtype_recordrtc
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qtype_recordrtc_plugin extends restore_qtype_plugin {
    /**
     * Returns the qtype name.
     *
     * @return string The type name
     */
    protected static function qtype_name() {
        return 'recordrtc';
    }

    /**
     * Returns the paths to be handled by the plugin at question level
     */
    protected function define_question_plugin_structure() {

        $paths = array();

        // This qtype uses question_answers, add them.
        $this->add_question_question_answers($paths);

        // Add own qtype stuff.
        $paths[] = new restore_path_element(self::qtype_name(), $this->get_pathfor('/recordrtc'));

        return $paths;
    }

    /**
     * Process the qtype/recordrtc element
     *
     * @param array $data
     */
    public function process_recordrtc($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Detect if the question is created or mapped
        // "question" is the XML tag name, not the DB field name.
        $oldquestionid   = $this->get_old_parentid('question');
        $newquestionid   = $this->get_new_parentid('question');

        // If the question has been created by restore,
        // we need to create a "qtype_recordrtc_options" record
        // and create a mapping from the $oldid to the $newid.
        if ($this->get_mappingid('question_created', $oldquestionid)) {
            $data->questionid = $newquestionid;
            $newid = $DB->insert_record('qtype_recordrtc_options', $data);
            $this->set_mapping('qtype_recordrtc_options', $oldid, $newid);
        }
    }
}
