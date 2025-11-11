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
* Unit tests for _local_questionnaire_reminder_get_config_safe().
*
* @package     local_questionnaire_reminder
* @copyright   2025 Valentina Tessaro
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

/**
* Test per la funzione _local_questionnaire_reminder_get_config_safe().
* 
* Config presente e valido → La funzione deve restituire il valore configurato.
* Config mancante (false o null) → La funzione deve restituire il valore di default.
* Config vuoto (stringa vuota o solo spazi) → La funzione deve restituire il valore di default.
* Chiave non presente nei defaults → La funzione deve restituire stringa vuota.
*/
class local_questionnaire_reminder_config_safe_test extends advanced_testcase {
    
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }
    
    public function test_returns_config_value_when_present() {
        set_config('mykey', 'customvalue', 'local_questionnaire_reminder');
        
        $result = _local_questionnaire_reminder_get_config_safe('mykey');
        $this->assertEquals('customvalue', $result);
    }
    
    public function test_returns_default_when_config_is_null() {
        unset_config('mykey', 'local_questionnaire_reminder');
        
        $defaults = local_questionnaire_reminder_get_default_templates();
        $expected = $defaults['mykey'] ?? '';
        
        $result = _local_questionnaire_reminder_get_config_safe('mykey');
        $this->assertEquals($expected, $result);
    }
    
    public function test_returns_default_when_config_is_empty_string() {
        set_config('mykey', '', 'local_questionnaire_reminder');
        
        $defaults = local_questionnaire_reminder_get_default_templates();
        $expected = $defaults['mykey'] ?? '';
        
        $result = _local_questionnaire_reminder_get_config_safe('mykey');
        $this->assertEquals($expected, $result);
    }
    
    public function test_returns_empty_string_when_key_not_in_defaults() {
        unset_config('nonexistentkey', 'local_questionnaire_reminder');
        
        $result = _local_questionnaire_reminder_get_config_safe('nonexistentkey');
        $this->assertEquals('', $result);
    }
}