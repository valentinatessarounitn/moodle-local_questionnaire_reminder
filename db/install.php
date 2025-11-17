<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
* Install script for the local_questionnaire_reminder plugin.
*
* This function is executed only once, during the initial installation of the plugin.
*
* @package     local_questionnaire_reminder
* @copyright   2025 valentina.tessaro@unitn.it
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

/**
* Function to execute during plugin installation.
*/
function xmldb_local_questionnaire_reminder_install() {
    global $DB;
    
    // Define table structure.
    $table = new xmldb_table('local_questionnaire_reminder');
    
    // Add fields.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('logdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
    $table->add_field('reminderstage', XMLDB_TYPE_TEXT, '2', null, null, null, null);
    $table->add_field('deliverysuccess', XMLDB_TYPE_INTEGER, '1', null, null, null, null);

    
    // Add primary key.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    
    // Create the table if it doesn't exist.
    if (!$DB->get_manager()->table_exists($table)) {
        $DB->get_manager()->create_table($table);
    }
}
