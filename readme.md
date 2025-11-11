## 🧩 Descrizione
local_questionnaire_reminder è un plugin locale per Moodle che automatizza l'invio di promemoria agli utenti iscritti a corsi che includono attività di tipo questionnaire. Il plugin attiva il questionario al raggiungimento del 75% della durata del corso e invia notifiche agli utenti. Poi si occupa di inviare due solleciti, uno alla fine del corso e uno una settimana dopo la fine, agli utenti che non hanno completato la compilazione. 

## 🚀 Funzionalità principali

- Rende visibili i questionari nascosti associati ai corsi attivi che hanno raggiunto il 75% della durata del corso e invia messaggi personalizzati agli utenti.
- Alla fine del corso invia un sollecito personalizzato agli utenti che non hanno ancora completato il questionario.
- Una settimana dopo la fine del corso invia un sollecito personalizzato agli utenti che non hanno ancora completato il questionario.


## ⚙️ Installazione
Copia la cartella local/questionnaire_reminder nella directory local/ del tuo sito Moodle

```bash
git clone https://github.com/valentinatessarounitn/moodle-local_questionnaire_reminder.git questionnaire_reminder
```

Esegui l'aggiornamento del database da /admin/index.php

Configura il task pianificato in Amministrazione del sito → Server → Tasks pianificati

## 🛠️ Script CLI
Il plugin include uno script CLI che può essere eseguito manualmente o tramite task:

```bash
php admin/cli/scheduled_task.php --execute="local_questionnaire_reminder\task\send_reminders"
```

## 🛠️ Impostazioni del plugin

Le impostazioni del plugin sono disponibili in: `/admin/settings.php?section=local_questionnaire_reminder`

Da qui è possibile personalizzare gli header e i body delle email di:

- Invito alla compilazione del questionario
- Sollecito alla fine del corso
- Sollecito post-corso (una settimana dopo)

Ogni messaggio può essere adattato in base al tono, al contenuto e alla lingua desiderata.

## 📌 Convenzioni
Tutte le funzioni personalizzate usano il prefisso `local_questionnaire_reminder_` per evitare conflitti con funzioni globali o di altri plugin.

## 📂 Struttura

```
local/questionnaire_reminder
├── LICENSE
├── classes
│   ├── logger.php
│   └── task
│       └── send_reminders.php
├── db
│   ├── install.php
│   ├── install.xml
│   └── tasks.php
├── lang
│   └── en
│       └── local_questionnaire_reminder.php
├── lib.php
├── process_endcourse_reminders.php
├── process_invites.php
├── process_postcourse_reminders.php
├── readme.md
├── settings.php
├── test
│   ├── config_safe_test.php
│   ├── get_courses_ended_7_days_ago_with_visible_questionnaire_test.php
│   ├── get_courses_ending_today_with_visible_questionnaire_test.php
│   ├── get_courses_with_hidden_questionnaire_test.php
│   ├── get_users_without_responses_test.php
│   ├── process_endcourse_reminders_test.php
│   ├── process_invites_test.php
│   └── process_postcourse_reminders_test.php
└── version.php
```


