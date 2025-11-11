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
* Unit tests for local_questionnaire_reminder_get_courses_ending_today_with_visible_questionnaire
*
* @package     local_questionnaire_reminder
* @copyright   2025 Valentina Tessaro
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_questionnaire_reminder;

require_once(__DIR__ . '/../process_endcourse_reminders.php');

use advanced_testcase;

class local_questionnaire_reminder_get_courses_ending_today_with_visible_questionnaire_test extends advanced_testcase {
    
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }
    
    private function getCustomFieldId(): int {
        global $DB;
        // Aggiungi custom field lingua se non esiste.
        $field = $DB->get_record('customfield_field', ['shortname' => 'lingua']);
        
        if ($field) {
            return $field->id;
        }
        
        $fieldid = $DB->insert_record('customfield_field', [
            'shortname' => 'lingua',
            'name' => 'Lingua',
            'type' => 'select',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        
        return $fieldid;
    }
    
    /**
    * Crea un corso di test con i parametri richiesti.
    */
    private function create_test_course($enddate, $visible = 1, $lingua = 1, $withquestionnaire = true, $questionnairevisible = 1) {
        global $DB;
        
        // Crea corso.
        $course = $this->getDataGenerator()->create_course([
            'visible' => $visible,
            'startdate' => time() - DAYSECS,
            'enddate' => $enddate,
        ]);
        
        $DB->insert_record('customfield_data', [
            'fieldid' => $this->getCustomFieldId(),
            'instanceid' => $course->id,
            'value' => $lingua,
            'intvalue' => $lingua,
            'valueformat' => 0,
            'contextid' => \context_course::instance($course->id)->id,
            'valuetrust' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        
        
        // Aggiungi questionario se richiesto.
        if ($withquestionnaire) {
            $this->getDataGenerator()->create_module('questionnaire', [
                'course' => $course->id,
                'visible' => $questionnairevisible,
            ]);
        }
        
        return $course;
    }
    
    public function test_course_with_visible_questionnaire_and_enddate_today_is_returned() {
        ob_start();
        $course = $this->create_test_course(strtotime('today'));
        
        $courses = local_questionnaire_reminder_get_courses_ending_today_with_visible_questionnaire();
        $this->assertArrayHasKey($course->id, $courses);
        ob_end_clean();
    }
    
    public function test_course_without_questionnaire_is_not_returned() {
        ob_start();
        $course = $this->create_test_course(strtotime('today'), 1, 1, false);
        
        $courses = local_questionnaire_reminder_get_courses_ending_today_with_visible_questionnaire();
        $this->assertArrayNotHasKey($course->id, $courses);
        ob_end_clean();
    }
    
    public function test_course_with_hidden_questionnaire_is_not_returned() {
        ob_start();
        $course = $this->create_test_course(strtotime('today'), 1, 1, true, 0);
        
        $courses = local_questionnaire_reminder_get_courses_ending_today_with_visible_questionnaire();
        $this->assertArrayNotHasKey($course->id, $courses);
        ob_end_clean();
    }
    
    public function test_course_with_enddate_not_today_is_not_returned() {
        ob_start();
        $course = $this->create_test_course(strtotime('tomorrow'));
        
        $courses = local_questionnaire_reminder_get_courses_ending_today_with_visible_questionnaire();
        $this->assertArrayNotHasKey($course->id, $courses);
        ob_end_clean();
    }
    
    public function test_course_with_lingua_zero_is_not_returned() {
        ob_start();
        $course = $this->create_test_course(strtotime('today'), 1, 0);
        
        $courses = local_questionnaire_reminder_get_courses_ending_today_with_visible_questionnaire();
        $this->assertArrayNotHasKey($course->id, $courses);
        ob_end_clean();
    }
    
    public function test_course_not_visible_is_not_returned() {
        ob_start();
        $course = $this->create_test_course(strtotime('today'), 0);
        
        $courses = local_questionnaire_reminder_get_courses_ending_today_with_visible_questionnaire();
        $this->assertArrayNotHasKey($course->id, $courses);
        ob_end_clean();
    }
    
    public function test_multiple_valid_courses_are_returned() {
        ob_start();
        $course1 = $this->create_test_course(strtotime('today'));
        $course2 = $this->create_test_course(strtotime('today'));
        
        $courses = local_questionnaire_reminder_get_courses_ending_today_with_visible_questionnaire();
        $this->assertArrayHasKey($course1->id, $courses);
        $this->assertArrayHasKey($course2->id, $courses);
        $this->assertCount(2, $courses);
        ob_end_clean();
    }
}
