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
 * Plugin settings for questionnaire reminder.
 *
 * Defines configurable templates for reminder messages sent at different stages of a course.
 *
 * Access path in Moodle:
 * Path: Amministrazione / Plugin / Plugin locali / Questionnaire Reminder
 * URL: .../admin/settings.php?section=local_questionnaire_reminder
 *
 * @package     local_questionnaire_reminder
 * @copyright   2025 valentina.tessaro@unitn.it
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/questionnaire_reminder/lib.php');

if ($hassiteconfig) {
    
    $prefix = 'local_questionnaire_reminder/';
    $defaults = local_questionnaire_reminder_get_default_templates();
    
    $settings = new admin_settingpage('local_questionnaire_reminder', get_string('pluginname', 'local_questionnaire_reminder'));
    
    $settings->add(new admin_setting_configtext(
        $prefix . 'subject_default',
        'Oggetto promemoria (invito iniziale)',
        'Oggetto del messaggio per il primo invito',
        $defaults['subject_default'],
        PARAM_TEXT,
        80, // larghezza visibile del campo
    ));
    
    $settings->add(new admin_setting_configtextarea(
        $prefix . 'body_default',
        'Testo promemoria (invito iniziale)',
        'Testo del messaggio per il primo invito',
        $defaults['body_default'],
        PARAM_TEXT
    ));
    
    $settings->add(new admin_setting_configtext(
        $prefix . 'subject_finecorso',
        'Oggetto promemoria (fine corso)',
        'Oggetto del messaggio per il sollecito alla fine del corso',
        $defaults['subject_finecorso'],
        PARAM_TEXT,
        80 // larghezza visibile del campo
    ));
    
    $settings->add(new admin_setting_configtextarea(
        $prefix . 'body_finecorso',
        'Testo promemoria (fine corso)',
        'Testo del messaggio per il sollecito alla fine del corso',
        $defaults['body_finecorso'],
        PARAM_TEXT
    ));
    
    $settings->add(new admin_setting_configtext(
        $prefix . 'subject_postcorso',
        'Oggetto promemoria (post corso)',
        'Oggetto del messaggio per il sollecito dopo la fine del corso',
        $defaults['subject_postcorso'],
        PARAM_TEXT,
        80 // larghezza visibile del campo
    ));
    
    $settings->add(new admin_setting_configtextarea(
        $prefix . 'body_postcorso',
        'Testo promemoria (post corso)',
        'Testo del messaggio per il sollecito dopo la fine del corso',
        $defaults['body_postcorso'],
        PARAM_TEXT
    ));
    
    $ADMIN->add('localplugins', $settings);
}