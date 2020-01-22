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
 * Recordrtc question type db upgrade script
 *
 * @package   qtype_recordrtc
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the recordrtc question type.
 *
 * @param int $oldversion the version we are upgrading from.
 * @return bool
 */
function xmldb_qtype_recordrtc_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020012100) {

        // Define table qtype_recordrtc_options to be created.
        $table = new xmldb_table('qtype_recordrtc_options');

        // Adding fields to table qtype_recordrtc_options.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('mediatype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timelimitinseconds', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '30');

        // Adding keys to table qtype_pmatch_test_responses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

        // Conditionally launch create table for qtype_recordrtc_options.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Question savepoint reached.
        upgrade_plugin_savepoint(true, 2020012100, 'qtype', 'recordrtc');
    }

    if ($oldversion < 2020012200) {

        // Changing type of field mediatype on table qtype_recordrtc_options to char.
        $table = new xmldb_table('qtype_recordrtc_options');
        $field = new xmldb_field('mediatype', XMLDB_TYPE_CHAR, '8', null, XMLDB_NOTNULL, null, 'audio', 'questionid');

        // Launch change of type for field mediatype.
        $dbman->change_field_type($table, $field);

        // Recordrtc savepoint reached.
        upgrade_plugin_savepoint(true, 2020012200, 'qtype', 'recordrtc');
    }

    if ($oldversion < 2020012201) {

        // Changing the default of field mediatype on table qtype_recordrtc_options to audio.
        $table = new xmldb_table('qtype_recordrtc_options');
        $field = new xmldb_field('mediatype', XMLDB_TYPE_CHAR, '8', null, XMLDB_NOTNULL, null, 'audio', 'questionid');

        // Launch change of default for field mediatype.
        $dbman->change_field_default($table, $field);

        // Recordrtc savepoint reached.
        upgrade_plugin_savepoint(true, 2020012201, 'qtype', 'recordrtc');
    }

    if ($oldversion < 2020012202) {

        // Update existing values in the mediatype column.
        $DB->set_field('qtype_recordrtc_options', 'mediatype', 'audio', ['mediatype' => '1']);
        $DB->set_field('qtype_recordrtc_options', 'mediatype', 'video', ['mediatype' => '2']);

        // Recordrtc savepoint reached.
        upgrade_plugin_savepoint(true, 2020012202, 'qtype', 'recordrtc');
    }

    if ($oldversion < 2020012203) {

        // Add semicolons between statements in questionvariables.
        $toupdatecount = $DB->count_records_sql("
                SELECT COUNT(1)
                  FROM {question} q
             LEFT JOIN {qtype_recordrtc_options} o ON o.questionid = q.id
                 WHERE q.qtype = ? AND o.id IS NULL", ['recordrtc']);
        if ($toupdatecount > 0) {
            $rs = $DB->get_recordset_sql("
                SELECT q.id
                  FROM {question} q
             LEFT JOIN {qtype_recordrtc_options} o ON o.questionid = q.id
                 WHERE q.qtype = ? AND o.id IS NULL", ['recordrtc']);
            $pbar = new progress_bar('createrecordrtcquestionoptions', 500, true);

            $done = 0;
            foreach ($rs as $row) {
                $pbar->update($done, $toupdatecount,
                        "Creating options for record audio questions - {$done}/{$toupdatecount} (id = {$row->id}).");

                $newoptions = new stdClass();
                $newoptions->questionid = $row->id;
                $newoptions->mediatype = 'audio';
                $newoptions->timelimitinseconds = 30;
                $DB->insert_record('qtype_recordrtc_options', $newoptions);

                $done++;
            }
            $pbar->update($done, $toupdatecount,
                    "Creating options for record audio questions - {$done}/{$toupdatecount}.");
            $rs->close();
        }

        // Recordrtc savepoint reached.
        upgrade_plugin_savepoint(true, 2020012203, 'qtype', 'recordrtc');
    }

    return true;
}


