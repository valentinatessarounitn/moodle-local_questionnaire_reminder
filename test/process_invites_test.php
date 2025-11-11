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
* Unit tests for process_invites().
*
* @package     local_questionnaire_reminder
* @copyright   2025 Valentina Tessaro
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

declare(strict_types=1);

namespace local_questionnaire_reminder;

require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/../process_invites.php');

use advanced_testcase;

final class process_invites_test extends advanced_testcase {
    
    public function setUp(): void {
        $this->resetAfterTest(true);
    }
    
    public function test_no_courses_found(): void {
        ob_start();
        // Nessun corso creato → la funzione dovrebbe restituire 0 corsi.
        $courses = \local_questionnaire_reminder_get_courses_with_hidden_questionnaire();
        $this->assertIsArray($courses);
        $this->assertCount(0, $courses);
        ob_end_clean();
    }
    
    public function test_course_with_hidden_questionnaire_is_selected(): void {
        global $DB;
        
        ob_start();
        
        // Calcola date in modo che oggi sia il 75% della durata.
        $now = time();
        $duration = 8 * DAYSECS; // 8 giorni
        $start = $now - (int)($duration * 0.75);
        $end = $start + $duration;
        
        // Crea il corso.
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
        
        // Aggiungi un questionario nascosto.
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'questionnaire']);
        $questionnaire = $this->getDataGenerator()->create_module('questionnaire', ['course' => $course->id]);
        $cm = $DB->get_record('course_modules', ['instance' => $questionnaire->id, 'module' => $moduleid]);
        $DB->set_field('course_modules', 'visible', 0, ['id' => $cm->id]);
        
        // Esegui la funzione da testare.
        $courses = local_questionnaire_reminder_get_courses_with_hidden_questionnaire();
        
        // Verifica che il corso sia stato selezionato.
        $this->assertCount(1, $courses);
        $this->assertEquals($course->id, reset($courses)->id);
        
        ob_end_clean();
    }
    
    public function test_process_invites_executes_without_error(): void {
        ob_start();
        process_invites();
        ob_end_clean();
        $this->expectNotToPerformAssertions();
    }
}
