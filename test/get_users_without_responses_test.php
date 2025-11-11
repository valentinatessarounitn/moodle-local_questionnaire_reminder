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
 * Unit tests for local_questionnaire_reminder_questionnaire_get_users_without_responses.
 *
 * @package     local_questionnaire_reminder
 * @copyright   2025 Valentina Tessaro
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questionnaire_reminder;

require_once(__DIR__ . '/../lib.php');

use advanced_testcase;

final class get_users_without_responses_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_no_users_enrolled(): void {
        $course = $this->getDataGenerator()->create_course();
        $questionnaire = $this->getDataGenerator()->create_module('questionnaire', ['course' => $course->id]);

        $users = local_questionnaire_reminder_questionnaire_get_users_without_responses($course->id, $questionnaire->id);
        $this->assertIsArray($users);
        $this->assertCount(0, $users);
    }

    public function test_user_without_response_is_returned(): void {
        $course = $this->getDataGenerator()->create_course();
        $questionnaire = $this->getDataGenerator()->create_module('questionnaire', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $users = local_questionnaire_reminder_questionnaire_get_users_without_responses($course->id, $questionnaire->id);
        $this->assertCount(1, $users);
        $this->assertEquals($user->id, $users[0]->id);
    }

    public function test_user_with_completed_response_is_excluded(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $questionnaire = $this->getDataGenerator()->create_module('questionnaire', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $DB->insert_record('questionnaire_response', (object)[
            'questionnaireid' => $questionnaire->id,
            'userid' => $user->id,
            'complete' => 'y',
            'submitted' => time(),
            'grade' => 0,
        ]);

        $users = local_questionnaire_reminder_questionnaire_get_users_without_responses($course->id, $questionnaire->id);
        $this->assertCount(0, $users);
    }
}