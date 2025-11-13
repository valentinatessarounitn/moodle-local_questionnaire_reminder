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
* Logging utility for questionnaire reminder events.
*
* Records messages in the plugin's custom log table and outputs them via mtrace.
*
* @package     local_questionnaire_reminder
* @copyright   2025 valentina.tessaro@unitn.it
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
namespace local_questionnaire_reminder;

defined('MOODLE_INTERNAL') || die();

/**
* Helper class for logging questionnaire reminder messages.
*/
class logger {
    
    /** @var bool */
    private static bool $muted = false;
    
    /**
    * Disattiva la stampa via mtrace (usata nei test).
    */
    public static function mute(): void {
        self::$muted = true;
    }
    
    /**
    * Riattiva la stampa via mtrace.
    */
    public static function unmute(): void {
        self::$muted = false;
    }
    
    /**
    * Scrive un messaggio nel database e lo stampa via mtrace (se non silenziato).
    *
    * @param string $message Il messaggio da registrare.
    */
    public static function trace(string $message,$courseid = null,$userid = null, $reminderstage = null, $deliverysuccess = null): void {
        global $DB;
        
        // Validazione reminderstage.
        if (!is_null($reminderstage) && !in_array($reminderstage, ['default', 'finecorso','postcorso'], true)) {
            self::trace('Insert fallito: Valore reminderstage non valido: deve essere default, finecorso o postcorso. Messaggio originale: ' . $message);
            return;
        }
        
        // Validazione deliverysuccess (true/false/null).
        if (!is_null($deliverysuccess) && !is_bool($deliverysuccess)) {
            self::trace('Insert fallito: Valore deliverysuccess non valido: deve essere true, false o null. Messaggio originale: ' . $message);
            return;
        }
        
        $record = new \stdClass();
        $record->logdate = time();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->message = $message;
        $record->reminderstage = $reminderstage;
        // Salviamo deliverysuccess come intero (1 = true, 0 = false, null = non definito).
        $record->deliverysuccess = is_null($deliverysuccess) ? null : ($deliverysuccess ? 1 : 0);
        
        $DB->insert_record('local_questionnaire_reminder_log', $record);
        
        if (!self::$muted) {
            mtrace("[" . date('Y-m-d H:i:s') . "] " . $message);
        }
    }
}
