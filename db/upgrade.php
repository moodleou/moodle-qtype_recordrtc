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
 */
function xmldb_qtype_recordrtc_upgrade($oldversion) {
    global $CFG, $DB;

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

    return true;
}


