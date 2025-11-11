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
* Unit tests for process_postcourse_reminders().
*
* @package     local_questionnaire_reminder
* @copyright   2025 Valentina Tessaro
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_questionnaire_reminder;

require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/../process_postcourse_reminders.php');

use advanced_testcase;

final class process_postcourse_reminders_test extends advanced_testcase {
    
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }
    
    public function test_no_courses_found(): void {
        ob_start();
        $courses = local_questionnaire_reminder_get_courses_ended_7_days_ago_with_visible_questionnaire();
        $this->assertIsArray($courses);
        $this->assertCount(0, $courses);
        ob_end_clean();
    }
    
    public function test_course_ended_7_days_ago_is_selected(): void {
        global $DB;

        ob_start();
        
        $now = time();
        $start = $now - (14 * DAYSECS); // corso iniziato 14 giorni fa
        $end = strtotime(date('Y-m-d 23:59:59', strtotime('-7 days'))); // finito esattamente 7 giorni fa
        
        $course = $this->getDataGenerator()->create_course([
            'visible' => 1,
            'startdate' => $start,
            'enddate' => $end,
        ]);
        
        // Aggiungi campo custom "lingua".
        $fieldid = $DB->insert_record('customfield_field', (object)[
            'shortname' => 'lingua',
            'name' => 'Lingua',
            'type' => 'select',
            'configdata' => '{"required":"0","uniquevalues":"0","options":"ARABO\r\nCINESE\r\nFRANCESE\r\nINGLESE\r\nITALIANO\r\nRUSSO\r\nSPAGNOLO\r\nTEDESCO","defaultvalue":"","locked":"1","visibility":"2"}',           
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        
        $DB->insert_record('customfield_data', (object)[
            'fieldid' => $fieldid,
            'instanceid' => $course->id,
            'value' => $fieldid,
            'contextid' => \context_course::instance($course->id)->id,
            'valueformat' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        
        $questionnaire = $this->getDataGenerator()->create_module('questionnaire', ['course' => $course->id]);
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'questionnaire']);
        $cm = $DB->get_record('course_modules', ['instance' => $questionnaire->id, 'module' => $moduleid]);
        $DB->set_field('course_modules', 'visible', 1, ['id' => $cm->id]);
        
        $courses = local_questionnaire_reminder_get_courses_ended_7_days_ago_with_visible_questionnaire();
        $this->assertCount(1, $courses);
        $this->assertEquals($course->id, reset($courses)->id);
        ob_end_clean();
    }
    
    public function test_process_postcourse_reminders_executes_without_error(): void {
        ob_start();
        process_postcourse_reminders();
        ob_end_clean();
        $this->expectNotToPerformAssertions();
    }
}