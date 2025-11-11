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
* Unit tests for local_questionnaire_reminder_questionnaire_get_courses_with_hidden_questionnaire
*
* @package     local_questionnaire_reminder
* @copyright   2025 Valentina Tessaro
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_questionnaire_reminder;

require_once(__DIR__ . '/../process_invites.php');

use advanced_testcase;

final class get_courses_with_hidden_questionnaire_test extends advanced_testcase {
    
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }
    
    public function test_no_courses_found(): void {
        ob_start();
        $courses = local_questionnaire_reminder_get_courses_with_hidden_questionnaire();
        $this->assertIsArray($courses);
        $this->assertCount(0, $courses);
        ob_end_clean();
    }
    
    public function test_course_reaches_75_percent_today_with_hidden_questionnaire(): void {
        global $DB;
        
        ob_start();
        
        $this->resetAfterTest(true);
        
        $today = time();
        $duration = 40 * DAYSECS;
        $start = $today - (int)ceil($duration * 0.75);
        $end = $start + $duration;
        
        $course = $this->getDataGenerator()->create_course([
            'visible' => 1,
            'startdate' => $start,
            'enddate' => $end,
        ]);
        
        $contextid = \context_course::instance($course->id)->id;
        
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
            'value' => '1',
            'contextid' => $contextid,
            'valueformat' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        
        // Crea un questionario non visibile
        $questionnaire = $this->getDataGenerator()->create_module('questionnaire', ['course' => $course->id]);
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'questionnaire']);
        $this->assertNotFalse($moduleid, 'Modulo questionnaire non trovato');
        
        $cm = $DB->get_record('course_modules', ['instance' => $questionnaire->id, 'module' => $moduleid]);
        $DB->set_field('course_modules', 'visible', 0, ['id' => $cm->id]);
        $DB->set_field('course_modules', 'deletioninprogress', 0, ['id' => $cm->id]);
        
        // Esegui la funzione
        $courses = local_questionnaire_reminder_get_courses_with_hidden_questionnaire();
        
        // 1. Verifica che venga restituito un array
        $this->assertNotNull($courses, 'La funzione ha restituito null');
        $this->assertIsArray($courses, 'La funzione non ha restituito un array');
        
        // 2. Verifica che ci sia esattamente un corso
        $this->assertCount(1, $courses, 'Il numero di corsi restituiti non è corretto');
        
        // 3. Verifica che il corso restituito sia quello atteso
        $this->assertEquals($course->id, reset($courses)->id);
        
        ob_end_clean();
    }
}
