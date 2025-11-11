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
* Step A of the daily reminder task: makes questionnaires visible and sends initial invites.
*
* @package     local_questionnaire_reminder
* @copyright   2025 valentina.tessaro@unitn.it
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

if (!defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}



use local_questionnaire_reminder\logger as log;


/**
* Rende visibili i questionari associati ai corsi attivi che hanno raggiunto il 75% della durata
* e invia un invito iniziale agli utenti che non hanno ancora compilato il questionario.
*/
function process_invites(): void {
    global $DB;
    log::trace("=== Inizio esecuzione attivazione questionari e primo invito ===");
    
    // Ottieni l'elenco dei corsi visibili con questionari nascosti e che hanno raggiunto il 75% della loro durata proprio nella giornata corrente
    $courses = local_questionnaire_reminder_get_courses_with_hidden_questionnaire(); 
    
    foreach ($courses as $course) {
        
        // Recupera il questionario nascosto associato - se ci sono più questionari, prende il primo
        $questionnaire = local_questionnaire_reminder_get_questionnaire_for_course($course->id, false);
        
        if (!$questionnaire) {
            log::trace("❌ Nessun questionario nascosto trovato nel corso {$course->id}, salto.");
            continue;
        }
        
        // Rendi visibile il questionario
        $cm = get_coursemodule_from_id('questionnaire', $questionnaire->id);
        $cm->visible = 1;
        $DB->update_record('course_modules', $cm);
        rebuild_course_cache($course->id);
        log::trace("✅ Questionario reso visibile per il corso {$course->fullname}");
        
        // Ottieni gli utenti iscritti al corso che non hanno completato il questionario
        $currentgroup = groups_get_activity_group($cm, true);
        $incompleteusers = local_questionnaire_reminder_questionnaire_get_users_without_responses($course->id, $questionnaire->instance, $currentgroup);
        
        // Invia messaggio agli utenti iscritti
        log::trace("📅 Invio promemoria di apertura questionario per {$course->fullname} a " . count($incompleteusers) . " utenti");
        
        foreach ($incompleteusers as $user) {
            log::trace("📧 Tentato invio messaggio a {$user->firstname} {$user->lastname} ({$user->email})");
            local_questionnaire_reminder_send_reminder_message($user, $course, $questionnaire, 'default');
        }
        
        log::trace("✔️ Completato l'invio per il corso {$course->fullname} (ID {$course->id}) ");
    }
    log::trace("=== Fine esecuzione attivazione questionari e primo invito ===");
}

//////////////////////// HELPERS ONLY FOR process_invites //////////////////////

/**
* Restituisce l'elenco dei corsi attivi che hanno almeno un questionario nascosto
* e che hanno raggiunto il 75% della loro durata proprio nella giornata corrente.
*
* La selezione include solo i corsi:
* - visibili e con date di inizio e fine valide
* - che hanno raggiunto oggi il ¾ della durata (calcolato come startdate + 75% * durata)
* - che possiedono un campo custom "lingua" valorizzato (fieldid = 1)
* - che contengono almeno un'attività di tipo "questionnaire" non visibile e non in fase di eliminazione
*
* Questo metodo è utilizzato per identificare i corsi che devono ricevere il primo invito
* alla compilazione del questionario, rendendo visibile l'attività e notificando gli utenti.
*
* @return array Elenco di oggetti corso che soddisfano i criteri
*/
function local_questionnaire_reminder_get_courses_with_hidden_questionnaire(): array {
    global $DB;
    
    /*
    Spiegazione:
    UNIX_TIMESTAMP converte una data DATETIME nel valore UNIX (secondi dal 1970).
    CURDATE() è già una data senza tempo (YYYY-MM-DD) con orario 00:00:00
    c.startdate + CEIL((c.enddate - c.startdate) * 0.75) restituisce il timestamp UNIX del momento in cui il corso raggiunge il 75% della durata.
        
    Possiamo verificare che il 75% della durata cada tra oggi alle 00:00 (incluso) e domani alle 00:00 (escluso):
    AND c.startdate + CEIL((c.enddate - c.startdate) * 0.75) >= UNIX_TIMESTAMP(CURDATE())
    AND c.startdate + CEIL((c.enddate - c.startdate) * 0.75) < UNIX_TIMESTAMP(CURDATE() + INTERVAL 1 DAY)

    Tanto una volta che il sondaggio viene eseguito giornalmente, non cambia nulla se il 75% cade in un intervallo di 24 ore.
    E una volta che viene reso visibile il questionario, poi non  viene più restituito dalla query.
    */
    
    $sql = "
    SELECT c.*
    FROM {course} c
    JOIN {customfield_data} c_lingua ON c.id = c_lingua.instanceid
    JOIN {customfield_field} c_lingua_field ON c_lingua.fieldid = c_lingua_field.id
        AND c_lingua_field.shortname = :linguashortname
    WHERE c.visible = 1
      AND c.startdate IS NOT NULL
      AND c.startdate > 0
      AND c.enddate IS NOT NULL
      AND c.enddate > 0
      AND c.startdate + CEIL((c.enddate - c.startdate) * 0.75) >= UNIX_TIMESTAMP(CURDATE())
      AND c.startdate + CEIL((c.enddate - c.startdate) * 0.75) < UNIX_TIMESTAMP(CURDATE() + INTERVAL 1 DAY)
      AND c_lingua.value > 0
      AND EXISTS (
          SELECT 1
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
          WHERE cm.course = c.id
            AND m.name = 'questionnaire'
            AND cm.deletioninprogress = 0
            AND cm.visible = 0
      )
    ";
    
    $params = [
        'linguashortname' => 'lingua',
    ];
    
    // Questa query seleziona tutti i corsi visibili che:
    // - hanno una data di inizio valida e già trascorsa (o uguale a oggi)
    // - hanno una data di fine valida
    // - hanno raggiunto esattamente oggi il ¾ della loro durata
    // - hanno il campo custom "lingua" valorizzato (fieldid = :linguafieldid)
    // - contengono almeno un'attività di tipo "questionnaire" non visibile e non in fase di eliminazione
    // Il risultato è usato per identificare i corsi che devono ricevere l'invito alla compilazione del questionario.
    $courses = $DB->get_records_sql($sql, $params);
    
    log::trace("Totale corsi con questionari nascosti al 75% alla data odierna: " . count($courses));
    
    return $courses;
}
