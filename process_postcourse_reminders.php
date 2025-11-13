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
* Step C of the daily reminder task: sends reminders one week after course end.
*
* @package     local_questionnaire_reminder
* @copyright   2025 valentina.tessaro@unitn.it
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();


use local_questionnaire_reminder\logger as log;

/**
* Invia un promemoria agli utenti che non hanno compilato il questionario
* una settimana dopo la fine del corso.
*/
function process_postcourse_reminders(): void {
    global $DB;
    
    log::trace("=== Inizio esecuzione sollecito post-corso (7 giorni dopo fine) ===");
    
    // Ottieni i corsi terminati esattamente 7 giorni fa con questionari visibili
    $courses = local_questionnaire_reminder_get_courses_ended_7_days_ago_with_visible_questionnaire();
    
    foreach ($courses as $course) {
        
        // Recupera il questionario visibile associato al corso
        $questionnaire = local_questionnaire_reminder_get_questionnaire_for_course($course->id, true);
        
        if (!$questionnaire) {
            log::trace("❌ Nessun questionario visibile trovato nel corso {$course->id}, salto.", $course->id);
            continue;
        }
        
        // Recupera il modulo attività
        $cm = get_coursemodule_from_instance('questionnaire', $questionnaire->instance);
        if (!$cm) {
            log::trace("⚠️ Impossibile recuperare il coursemodule per il questionario {$questionnaire->instance}, salto.", $course->id);
            continue;
        }
        
        // Ottieni gli utenti iscritti che non hanno completato il questionario
        $currentgroup = groups_get_activity_group($cm, true);
        $incompleteusers = local_questionnaire_reminder_questionnaire_get_users_without_responses($course->id, $questionnaire->instance, $currentgroup);
        
        log::trace("📅 Invio sollecito post-corso per {$course->fullname} a " . count($incompleteusers) . " utenti", $course->id);
        
        foreach ($incompleteusers as $user) {
            log::trace("📧 Tentato invio messaggio a {$user->firstname} {$user->lastname} ({$user->email})", $course->id, $user->id, 'postcorso');
            local_questionnaire_reminder_send_reminder_message($user, $course, $questionnaire, 'postcorso');
        }
        
        log::trace("✔️ Completato l'invio per il corso {$course->fullname} (ID {$course->id})", $course->id);
    }
    
    log::trace("=== Fine esecuzione sollecito post-corso ===");
}

//////////////////////// HELPERS ONLY FOR process_postcourse_reminders //////////////////////

/**
* Restituisce l'elenco dei corsi attivi che sono terminati esattamente 7 giorni fa
* e che contengono almeno un questionario visibile.
*
* La selezione include solo i corsi:
* - visibili e con date di inizio e fine valide
* - la cui data di fine è esattamente 7 giorni fa
* - che possiedono un campo custom "lingua" valorizzato (fieldid = 1)
* - che contengono almeno un'attività di tipo "questionnaire" visibile e non in fase di eliminazione
*
* Questo metodo è utilizzato per identificare i corsi che devono ricevere
* il sollecito alla compilazione del questionario una settimana dopo la fine.
*
* @return array Elenco di oggetti corso che soddisfano i criteri
*/
function local_questionnaire_reminder_get_courses_ended_7_days_ago_with_visible_questionnaire(): array {
    global $DB;
    
    $sql = "
    SELECT c.*
    FROM {course} c
    JOIN {customfield_data} c_lingua ON c.id = c_lingua.instanceid
    JOIN {customfield_field} c_lingua_field ON c_lingua.fieldid = c_lingua_field.id
        AND c_lingua_field.shortname = :linguashortname
    WHERE c.visible = 1
      AND c.startdate IS NOT NULL
      AND c.startdate > 0
      AND c.startdate <= UNIX_TIMESTAMP(CURDATE())
      AND c.enddate IS NOT NULL
      AND c.enddate > 0
      AND DATE(FROM_UNIXTIME(c.enddate)) = DATE_SUB(CURDATE(), INTERVAL 7 DAY)
      AND c_lingua.value > 0
      AND EXISTS (
          SELECT 1
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
          WHERE cm.course = c.id
            AND m.name = 'questionnaire'
            AND cm.deletioninprogress = 0
            AND cm.visible = 1
      )
    ";
    
    $params = [
        'linguashortname' => 'lingua',
    ];
    
    $courses = $DB->get_records_sql($sql, $params);
    
    log::trace("Totale corsi con questionari visibili terminati 7 giorni fa: " . count($courses));
    
    return $courses;
}
