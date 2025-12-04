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
* Core library functions for questionnaire reminder plugin.
*
* Contains shared utility functions used across scheduled tasks, message templates, and configuration fallback logic.
*
* @package     local_questionnaire_reminder
* @copyright   2025 valentina.tessaro@unitn.it
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

use local_questionnaire_reminder\logger as log;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/local/questionnaire_reminder/lib.php');

// Nota: tutte le funzioni definite in questo plugin utilizzano il prefisso "local_questionnaire_reminder_"
// per evitare conflitti con funzioni globali di Moodle o di altri plugin.
// Per esempio, questionnaire_get_users_without_responses dà problemi di ambiguità, quindi qui definiamo
// local_questionnaire_reminder_questionnaire_get_users_without_responses.

//////////////////////// SETTINGS HELPERS //////////////////////

/**
* Restituisce i valori di default per i template di messaggio.
*
* @return array
*/
function local_questionnaire_reminder_get_default_templates(): array {
    return [
        'subject_default' => 'Valutazione della didattica - {coursename}',
        'subject_finecorso' => 'Valutazione della didattica - {coursename}',
        'subject_postcorso' => 'Valutazione della didattica - {coursename}',
        'body_default' => "(English below)\n\nGentile studente/studentessa,\naiutaci a migliorare il nostro servizio!\n\nTi chiediamo di investire pochi minuti del tuo tempo per partecipare al questionario di valutazione della didattica relativamente al corso di lingua che stai frequentando.\n\nPer partecipare, vai al seguente link:\n{url}\n\nNel caso in cui tu avessi frequentato altri corsi oltre a questo, ti arriverà una email per ogni corso frequentato.\n\nRingraziandoti della collaborazione,\ncordiali saluti\nCentro Linguistico di Ateneo\nDirezione Didattica e Servizi agli Studenti\nUniversità degli Studi di Trento\n\n-----\n\nDear student,\nplease could you help us to improve our service!\n\nWe would like you to spend a few minutes of your time completing this student satisfaction survey with regard to the language course you attended.\n\nTo participate, go to the following link:\n{url}\n\nIf you have followed other courses in addition to this one, you will receive a separate email for each course attended.\n\nWith many thanks for taking part,\nKind regards,\nUniversity Language Centre,\nTeaching and Student Services Directorate,\nUniversity of Trento",
        'body_finecorso' => "(English below)\n\nGentile studente/studentessa,\nti ricordiamo di partecipare al questionario di valutazione della didattica relativamente al corso di lingua che hai frequentato.\n\nPer partecipare, vai al seguente link:\n{url}\n\nNel caso in cui tu avessi frequentato altri corsi oltre a questo, ti arriverà una email per ogni corso frequentato.\n\nRingraziandoti della collaborazione,\ncordiali saluti\nCentro Linguistico di Ateneo\nDirezione Didattica e Servizi agli Studenti\nUniversità degli Studi di Trento\n\n-----\n\nDear student,\nwe remind you to partecipate to the student satisfaction survey with regard to the language course you attended.\n\nTo participate, go to the following link:\n{url}\n\nIf you have followed other courses in addition to this one, you will receive a separate email for each course attended.\n\nWith many thanks for taking part,\nKind regards,\nUniversity Language Centre,\nTeaching and Student Services Directorate,\nUniversity of Trento",
        'body_postcorso' => "(English below)\n\nGentile studente/studentessa,\nti ricordiamo di partecipare al questionario di valutazione della didattica relativamente al corso di lingua che hai frequentato.\n\nPer partecipare, vai al seguente link:\n{url}\n\nNel caso in cui tu avessi frequentato altri corsi oltre a questo, ti arriverà una email per ogni corso frequentato.\n\nRingraziandoti della collaborazione,\ncordiali saluti\nCentro Linguistico di Ateneo\nDirezione Didattica e Servizi agli Studenti\nUniversità degli Studi di Trento\n\n-----\n\nDear student,\nwe remind you to partecipate to the student satisfaction survey with regard to the language course you attended.\n\nTo participate, go to the following link:\n{url}\n\nIf you have followed other courses in addition to this one, you will receive a separate email for each course attended.\n\nWith many thanks for taking part,\nKind regards,\nUniversity Language Centre,\nTeaching and Student Services Directorate,\nUniversity of Trento"
    ];
}

/**
* Restituisce il valore di un setting del plugin, garantendo un fallback al valore di default
* nel caso in cui il setting non sia stato definito, sia vuoto o sia stato cancellato.
*
* I valori di default sono definiti centralmente nella funzione
* local_questionnaire_reminder_get_default_templates(), evitando duplicazioni.
* 
* [INTERNAL] Restituisce un valore di setting con fallback al default.
* Non usare al di fuori di local_questionnaire_reminder_send_reminder_message().
*
* @param string $key La chiave del setting da recuperare (es. 'subject_default', 'body_finecorso', ecc.)
* @return string Il valore configurato dall'amministratore, oppure il valore di default se assente
*/
function _local_questionnaire_reminder_get_config_safe(string $key): string {
    $defaults = local_questionnaire_reminder_get_default_templates();
    $value = get_config('local_questionnaire_reminder', $key);
    if ($value === false || $value === null || trim($value) === '') {
        return $defaults[$key] ?? '';
    }
    return $value;
}

//////////////////////// SETTINGS HELPERS END //////////////////////


/**
* Invia un messaggio di promemoria a un utente.
*
* @param stdClass $user
* @param stdClass $course
* @param stdClass $questionnaire
* @param string $type Tipo di promemoria ('default', 'finecorso', 'postcorso')
*/
function local_questionnaire_reminder_send_reminder_message($user, $course, $questionnaire, $type = 'default') {
    
    // Costruiamo il soggetto e il corpo del messaggio sostituendo i placeholder
    $subjecttemplate = _local_questionnaire_reminder_get_config_safe("subject_$type");
    $bodytemplate = _local_questionnaire_reminder_get_config_safe("body_$type");
    $url = (new \moodle_url('/mod/questionnaire/view.php', ['id' => $questionnaire->id]))->out();
    
    $subject = str_replace('{coursename}', $course->fullname, $subjecttemplate);
    $body = str_replace(
        ['{coursename}', '{questionnairename}', '{url}'],
        [$course->fullname, $questionnaire->name, $url],
        $bodytemplate
    );
    
    
    $eventdata = new \core\message\message();
    $eventdata->component = 'local_questionnaire_reminder';
    $eventdata->name = 'reminder';
    $eventdata->userfrom = \core_user::get_noreply_user();
    $eventdata->userto = $user;
    $eventdata->subject = $subject;
    $eventdata->fullmessage = $body;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml = '';
    $eventdata->smallmessage = '';
    $eventdata->notification = 1;
    
    $success = message_send($eventdata);
    
    if ($success) {
        log::trace("Promemoria di tipo '$type' inviato correttamente a {$user->id} {$user->firstname} {$user->lastname} per il corso {$course->id} '{$course->fullname}' (questionario '{$questionnaire->id}').",
        $course->id, $user->id, $type, true);
    } else {
        log::trace("❌ Errore nell'invio del promemoria di tipo '$type' a {$user->id} {$user->firstname} {$user->lastname} per il corso {$course->id} '{$course->fullname}' (questionario '{$questionnaire->id}').",
        $course->id, $user->id, $type, false);
    }
}

/**
* Restituisce gli utenti iscritti al corso che non hanno completato il questionario.
*
* @param int $courseid ID del corso
* @param int $questionnaireid ID dell'istanza del questionario (cm.instance)
* @param int $groupid ID del gruppo (0 per tutti)
* @return array Array di oggetti utente con proprietà aggiuntiva ->questionnaire_status
*/
function local_questionnaire_reminder_questionnaire_get_users_without_responses(int $courseid, int $questionnaireid, int $groupid = 0): array {
    global $DB;
    
    // First get all users who can complete this questionnaire
    $cap = 'mod/questionnaire:submit';
    $context = context_course::instance($courseid);
    $users = get_enrolled_users($context, $cap, $groupid);

    // Ottieni solo le risposte complete associate al questionario
    $responses = $DB->get_records('questionnaire_response', [
        'questionnaireid' => $questionnaireid,
        'complete' => 'y'
    ]);
    
    // Costruisci un set di user ID che hanno completato
    $completeduserids = [];
    foreach ($responses as $response) {
        $completeduserids[$response->userid] = true;
    }
    
    $incomplete = [];
    
    foreach ($users as $user) {
        if (!isset($completeduserids[$user->id])) {
            $incomplete[] = $user;
        }
    }
    
    return $incomplete;
}

/**
* Restituisce il primo questionario associato a un corso in base alla visibilità.
*
* Cerca tra i moduli del corso un'attività di tipo "questionnaire" con visibilità specificata
* e non in fase di eliminazione. Restituisce un oggetto con:
* - ->id: ID del course_module
* - ->instance: ID dell'istanza del questionario
*
* @param int $courseid ID del corso
* @param bool $visible true per cercare questionari visibili, false per nascosti
* @return stdClass|null Oggetto con proprietà ->id e ->instance oppure null se non trovato
*/
function local_questionnaire_reminder_get_questionnaire_for_course(int $courseid, bool $visible): ?stdClass {
    global $DB;
    
    $sql = "
        SELECT cm.id AS id, cm.instance AS instance
        FROM {course_modules} cm
        JOIN {modules} m ON cm.module = m.id
        WHERE m.name = 'questionnaire'
          AND cm.course = :courseid
          AND cm.visible = :visible
          AND cm.deletioninprogress = 0
        ORDER BY cm.id ASC
        LIMIT 1
    ";
    
    $params = [
        'courseid' => $courseid,
        'visible' => $visible ? 1 : 0,
    ];
    
    return $DB->get_record_sql($sql, $params) ?: null;
}
