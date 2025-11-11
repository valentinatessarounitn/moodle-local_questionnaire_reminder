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
* Scheduled task for sending questionnaire reminders.
*
* Executes three steps daily:
* A. Sends initial invites when 75% of course duration is reached.
* B. Sends reminders at course end.
* C. Sends follow-up reminders one week after course end.
*
* @package     local_questionnaire_reminder
* @copyright   2025 valentina.tessaro@unitn.it
* @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
namespace local_questionnaire_reminder\task;

defined('MOODLE_INTERNAL') || die();

/**
* Task pianificato per inviare promemoria relativi ai questionari.
* Si compone di tre fasi:
* A. Quando il corso raggiunge il 75% della durata, rende visibile il questionario nascosto e comunica agli utenti di compilarlo.
* B. Sollecito alla fine del corso
* C. Sollecito una settimana dopo la fine del corso
*/
class send_reminders extends \core\task\scheduled_task {
    
    /**
    * Nome leggibile del task (visibile in interfaccia admin).
    *
    * @return string
    */
    public function get_name() {
        return get_string('sendreminders', 'local_questionnaire_reminder');
    }
    
    
    /**
    * Esecuzione del task pianificato.
    */
    public function execute() {
        global $CFG;
        
        require_once($CFG->dirroot . '/local/questionnaire_reminder/lib.php');
        
        // A. Inviti durante il corso (75% della durata)
        require_once($CFG->dirroot . '/local/questionnaire_reminder/process_invites.php');
        process_invites();
        
        // B. Sollecito alla fine del corso
        require_once($CFG->dirroot . '/local/questionnaire_reminder/process_endcourse_reminders.php');
        process_endcourse_reminders();
        
        // C. Sollecito una settimana dopo la fine del corso
        require_once($CFG->dirroot . '/local/questionnaire_reminder/process_postcourse_reminders.php');
        process_postcourse_reminders();    
    }
}
