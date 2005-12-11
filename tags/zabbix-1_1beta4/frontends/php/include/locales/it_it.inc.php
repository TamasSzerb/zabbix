<?php
/* 
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/

// Italian translation by Michele Rui @ METSO 
?>
<?php
	global $TRANSLATION;

	$TRANSLATION=array(

	"S_DATE_FORMAT_YMDHMS"=>		"d M H:i:s",
	"S_DATE_FORMAT_YMD"=>			"d M Y",
	"S_HTML_CHARSET"=>			"iso-8859-1",

//	about.php
	"S_ABOUT_ZABBIX"=>			"Cos'� ZABBIX",
	"S_INFORMATION_ABOUT_ZABBIX"=>		"Informazioni su ZABBIX (v1.1beta4)",
	"S_HOMEPAGE_OF_ZABBIX"=>		"Questa � la homepage di ZABBIX",
	"S_HOMEPAGE_OF_ZABBIX_DETAILS"=>	"Questa � la homepage di ZABBIX.",
	"S_LATEST_ZABBIX_MANUAL"=>		"Il manuale pi� recente di ZABBIX",
	"S_LATEST_ZABBIX_MANUAL_DETAILS"=>	"L'ultima versione del manuale.",
	"S_DOWNLOADS"=>				"Downloads",
	"S_DOWNLOADS_DETAILS"=>			"L'ultima versione di ZABBIX pu� essere scaricata da qui.",
	"S_FEATURE_REQUESTS"=>			"Richiedi nuove funzionalit�",
	"S_FEATURE_REQUESTS_DETAILS"=>		"Se vuoi nuove funzionalit�=> prosegui.",
	"S_FORUMS"=>				"Forums",
	"S_FORUMS_DETAILS"=>			"Discussioni su ZABBIX.",
	"S_BUG_REPORTS"=>			"Segnala un errore",
	"S_BUG_REPORTS_DETAILS"=>		"C'� un errore in ZABBIX ? => Segnalalo.",
	"S_MAILING_LISTS"=>			"Liste di distribuzione",
	"S_MAILING_LISTS_DETAILS"=>		"Liste di distribuzione relative a ZABBIX.",
	
//	actions.php
	"S_ACTIONS"=>				"Azioni",
	"S_ACTION_ADDED"=>			"Azione aggiunta!",
	"S_CANNOT_ADD_ACTION"=>			"Non riesco ad aggiungere l'azione",
	"S_ACTION_UPDATED"=>			"Ho aggiornato l'azione",
	"S_CANNOT_UPDATE_ACTION"=>		"Non posso aggiornare l'azione",
	"S_ACTION_DELETED"=>			"Azione rimossa!",
	"S_CANNOT_DELETE_ACTION"=>		"Non posso cancellare l'azione",
	"S_SCOPE"=>				"Campo d'azione",
	"S_SEND_MESSAGE_TO"=>			"Manda il messaggio a:",
	"S_WHEN_TRIGGER"=>			"Quando l'innesco diventa",
	"S_DELAY"=>				"Con ritardo (in sec)",
	"S_SUBJECT"=>				"Oggetto",
	"S_ON"=>				"ON",
	"S_OFF"=>				"OFF",
	"S_NO_ACTIONS_DEFINED"=>		"Nessuna azione definita",
	"S_NEW_ACTION"=>			"Nuova azione",
	"S_SINGLE_USER"=>			"Utente singolo",
	"S_USER_GROUP"=>			"Gruppo di utenti",
	"S_GROUP"=>				"Gruppo",
	"S_USER"=>				"Utente",
	"S_WHEN_TRIGGER_BECOMES"=>		"Quando l'innesco diventa",
	"S_ON_OR_OFF"=>				"ON oppure OFF",
	"S_DELAY_BETWEEN_MESSAGES_IN_SEC"=>	"Ritardo tra ogni messaggio (in secondi)",
	"S_MESSAGE"=>				"Messaggio",
	"S_THIS_TRIGGER_ONLY"=>			"Solo questo evento",
	"S_ALL_TRIGGERS_OF_THIS_HOST"=>		"Tutti gli inneschi di questo dispositivo",
	"S_ALL_TRIGGERS"=>			"Tutti gli inneschi",
	"S_USE_IF_TRIGGER_SEVERITY"=>		"Usa se l'importanza dell'innesco � >= a",
	"S_NOT_CLASSIFIED"=>			"Non classificato",
	"S_INFORMATION"=>			"Solo informativo",
	"S_WARNING"=>				"Avvertimento",
	"S_AVERAGE"=>				"Allarme medio",
	"S_HIGH"=>				"Grave allarme",
	"S_DISASTER"=>				"Disastro!",

//	alarms.php
	"S_ALARMS"=>				"Allarmi",
	"S_ALARMS_SMALL"=>			"Allarmi",
	"S_ALARMS_BIG"=>			"ALLARMI",
	"S_SHOW_ONLY_LAST_100"=>		"Mostra solo gli ultimi 100",
	"S_SHOW_ALL"=>				"Mostra tutti",
	"S_TIME"=>				"Data e ora",
	"S_STATUS"=>				"Stato",
	"S_DURATION"=>				"Durata",
	"S_SUM"=>				"Somma",
	"S_TRUE_BIG"=>				"VERO",
	"S_FALSE_BIG"=>				"FALSO",
	"S_DISABLED_BIG"=>			"DISABILITATO",
	"S_UNKNOWN_BIG"=>			"NON RILEVABILE",

//	alerts.php
	"S_ALERT_HISTORY_SMALL"=>		"Storico allarmi",
	"S_ALERT_HISTORY_BIG"=>			"STORICO ALLARMI",
	"S_ALERTS_BIG"=>			"ALLARMI",
	"S_TYPE"=>				"Tipo",
	"S_RECIPIENTS"=>			"Destinatari",
	"S_ERROR"=>				"Errore",
	"S_SENT"=>				"spedito",
	"S_NOT_SENT"=>				"non spedito",
	"S_NO_ALERTS"=>				"Nessun allarme",
	"S_SHOW_NEXT_100"=>			"Mostra i prossimi 100",
	"S_SHOW_PREVIOUS_100"=>			"Mostra i 100 precedenti",

//	charts.php
	"S_CUSTOM_GRAPHS"=>			"Grafici",
	"S_GRAPHS_BIG"=>			"GRAFICI",
	"S_NO_GRAPHS_TO_DISPLAY"=>		"Nessun grafico da visualizzare",
	"S_SELECT_GRAPH_TO_DISPLAY"=>		"Seleziona il grafico",
	"S_PERIOD"=>				"Periodo",
	"S_1H"=>				"1h",
	"S_2H"=>				"2h",
	"S_4H"=>				"4h",
	"S_8H"=>				"8h",
	"S_12H"=>				"12h",
	"S_24H"=>				"24h",
	"S_WEEK_SMALL"=>			"settimana",
	"S_MONTH_SMALL"=>			"mese",
	"S_YEAR_SMALL"=>			"anno",
	"S_KEEP_PERIOD"=>			"Blocco del periodo",
	"S_ON_C"=>				"BLOCCALO",
	"S_OFF_C"=>				"sbloccalo",
	"S_MOVE"=>				"Spostati di",
	"S_SELECT_GRAPH_DOT_DOT_DOT"=>		"Seleziona...",

// Colors
	"S_BLACK"=>				"Nero",
	"S_BLUE"=>				"Blu",
	"S_CYAN"=>				"Azzurro",
	"S_DARK_BLUE"=>				"Blu scuro",
	"S_DARK_GREEN"=>			"Verde scuro",
	"S_DARK_RED"=>				"Rosso scuro",
	"S_DARK_YELLOW"=>			"Ocra",
	"S_GREEN"=>				"Verde",
	"S_RED"=>				"Rosso",
	"S_WHITE"=>				"Bianco",
	"S_YELLOW"=>				"Giallo",

//	config.php
	"S_CONFIGURATION_OF_ZABBIX"=>		"Configurazione di ZABBIX",
	"S_CONFIGURATION_OF_ZABBIX_BIG"=>	"CONFIGURAZIONE DI ZABBIX",
	"S_CONFIGURATION_UPDATED"=>		"Configurazione aggiornata",
	"S_CONFIGURATION_WAS_NOT_UPDATED"=>	"La configuration non � stata aggiornat",
	"S_ADDED_NEW_MEDIA_TYPE"=>		"Nuovo mezzo aggiunto",
	"S_NEW_MEDIA_TYPE_WAS_NOT_ADDED"=>	"Il nuovo mezzo non � stato aggiunto",
	"S_MEDIA_TYPE_UPDATED"=>		"Mezzo aggiornato!",
	"S_MEDIA_TYPE_WAS_NOT_UPDATED"=>	"Il mezzo non � stato aggiornato",
	"S_MEDIA_TYPE_DELETED"=>		"Il mezzo � stato rimosso",
	"S_MEDIA_TYPE_WAS_NOT_DELETED"=>	"Il mezzo non � stato rimosso",
	"S_CONFIGURATION"=>			"Configurazione",
	"S_DO_NOT_KEEP_ACTIONS_OLDER_THAN"=>	"Non mantenere le azioni pi� vecchie di (in giorni)",
	"S_DO_NOT_KEEP_EVENTS_OLDER_THAN"=>	"Non mantenere gli eventi pi� vecchi di (in giorni)",
	"S_MEDIA_TYPES_BIG"=>			"TIPI DI MEZZO",
	"S_NO_MEDIA_TYPES_DEFINED"=>		"Nessun mezzo definito",
	"S_SMTP_SERVER"=>			"SMTP server",
	"S_SMTP_HELO"=>				"SMTP helo",
	"S_SMTP_EMAIL"=>			"SMTP email",
	"S_SCRIPT_NAME"=>			"Nome script",
	"S_DELETE_SELECTED_MEDIA"=>		"Cancellare il mezzo selezionato?",
	"S_DELETE_SELECTED_IMAGE"=>		"Cancellare l'immagine selezionata?",
	"S_HOUSEKEEPER"=>			"Pulizia database",
	"S_MEDIA_TYPES"=>			"Tipi di mezzo",
	"S_ESCALATION_RULES"=>			"Regole di escalation",
	"S_ESCALATION"=>			"Escalation",
	"S_ESCALATION_RULES_BIG"=>		"REGOLE DI ESCALATION",
	"S_NO_ESCALATION_RULES_DEFINED"=>	"Nessuna regola di escalation definita",
	"S_NO_ESCALATION_DETAILS"=>		"Nessun dettaglio di escalation",
	"S_ESCALATION_DETAILS_BIG"=>		"DETTAGLI ESCALATION",
	"S_ESCALATION_ADDED"=>			"Escalation aggiunta",
	"S_ESCALATION_WAS_NOT_ADDED"=>		"L'escalation non � stata aggiunta",
	"S_ESCALATION_RULE_ADDED"=>		"Regola di escalation aggiunta",
	"S_ESCALATION_RULE_WAS_NOT_ADDED"=>	"La regola di escalation non � stata aggiunta",
	"S_ESCALATION_RULE_UPDATED"=>		"Regola di escalation aggiornata",
	"S_ESCALATION_RULE_WAS_NOT_UPDATED"=>	"La regola di escalation non � stata aggiornata",
	"S_ESCALATION_RULE_DELETED"=>		"La regola di escalation � stata rimossa",
	"S_ESCALATION_RULE_WAS_NOT_DELETED"=>	"La regola di escalation non � stata rimossa",
	"S_ESCALATION_UPDATED"=>		"Escalation aggiornata",
	"S_ESCALATION_WAS_NOT_UPDATED"=>	"L'escalation non � stata aggiornata",
	"S_ESCALATION_DELETED"=>		"Escalation cancellata",
	"S_ESCALATION_WAS_NOT_DELETED"=>	"L'escalation non � stata rimossa",
	"S_ESCALATION_RULE"=>			"Regola di escalation",
	"S_DO"=>				"Vai!",
	"S_DEFAULT"=>				"Default",
	"S_IS_DEFAULT"=>			"Setta come default",
	"S_LEVEL"=>				"Livello",
	"S_DELAY_BEFORE_ACTION"=>		"Ritardo prima dell'azione",
	"S_IMAGES"=>				"Immagini",
	"S_IMAGE"=>				"Immagine",
	"S_IMAGES_BIG"=>			"IMMAGINI",
	"S_NO_IMAGES_DEFINED"=>			"Nessuna immagine definita",
	"S_BACKGROUND"=>			"Sfondo",
	"S_UPLOAD"=>				"Carica",
	"S_IMAGE_ADDED"=>			"Immagine aggiunta",
	"S_CANNOT_ADD_IMAGE"=>			"Non posso aggiongere l'immagine",
	"S_IMAGE_DELETED"=>			"Immagine rimossa",
	"S_CANNOT_DELETE_IMAGE"=>		"Non posso rimuovere l'immagine",
	"S_IMAGE_UPDATED"=>			"Image updated",
	"S_CANNOT_UPDATE_IMAGE"=>		"Cannot update image",
	"S_UPDATE_SELECTED_IMAGE"=>		"Update selected image?",
	"S_AUTODISCOVERY"=>			"Ricerca automatica",

//	Latest values
	"S_LATEST_VALUES"=>			"Ultimi valori",
	"S_NO_PERMISSIONS"=>			"Accesso negato!",
	"S_LATEST_DATA"=>			"ULTIMI VALORI",
	"S_ALL_SMALL"=>				"tutti",
	"S_DESCRIPTION_LARGE"=>			"DESCRIZIONE",
	"S_DESCRIPTION_SMALL"=>			"Descrizione",
	"S_GRAPH"=>				"Graph",
	"S_TREND"=>				"Trend",
	"S_COMPARE"=>				"Compara",

//	Footer
	"S_ZABBIX_VER"=>			"ZABBIX 1.1beta4",
	"S_COPYRIGHT_BY"=>			"Copyright 2001-2005 by ",
	"S_CONNECTED_AS"=>			"Connesso come utente: ",
	"S_SIA_ZABBIX"=>			"SIA Zabbix",

//	graph.php
	"S_CONFIGURATION_OF_GRAPH"=>		"Configurazione grafici",
	"S_CONFIGURATION_OF_GRAPH_BIG"=>	"CONFIGURAZIONE GRAFICI",
	"S_ITEM_ADDED"=>			"Elemento agguinto!",
	"S_ITEM_UPDATED"=>			"Elemento aggiornato!",
	"S_SORT_ORDER_UPDATED"=>		"Ordinamento aggiornato!",
	"S_CANNOT_UPDATE_SORT_ORDER"=>		"Non posso aggiornare l'ordinamento",
	"S_DISPLAYED_PARAMETERS_BIG"=>		"ELEMENTI VISUALIZZATI NEL GRAFICO",
	"S_SORT_ORDER"=>			"Ordine di visualizz.",
	"S_PARAMETER"=>				"Parametro",
	"S_COLOR"=>				"Colore",
	"S_UP"=>				"S�",
	"S_DOWN"=>				"Gi�",
	"S_NEW_ITEM_FOR_THE_GRAPH"=>		"Aggiungi il seguente elemento",
	"S_SORT_ORDER_1_100"=>			"Posizione (0->100)",

//	graphs.php
	"S_CONFIGURATION_OF_GRAPHS"=>		"Configurazione grafici",
	"S_CONFIGURATION_OF_GRAPHS_BIG"=>	"CONFIGURAZIONE GRAFICI",
	"S_GRAPH_ADDED"=>			"Grafico aggiunto!",
	"S_GRAPH_UPDATED"=>			"Grafico aggiornato!",
	"S_CANNOT_UPDATE_GRAPH"=>		"Non posso aggiornare il grafico",
	"S_GRAPH_DELETED"=>			"Grafico rimosso!",
	"S_CANNOT_DELETE_GRAPH"=>		"Non posso rimuovere il grafico",
	"S_CANNOT_ADD_GRAPH"=>			"Non posso aggiungere il grafico",
	"S_ID"=>				"Id",
	"S_NO_GRAPHS_DEFINED"=>			"Nessun grafico definito",
	"S_DELETE_GRAPH_Q"=>			"Rimuovere il grafico?",
	"S_YAXIS_TYPE"=>			"Tipo asse Y",
	"S_YAXIS_MIN_VALUE"=>			"Y minimo",
	"S_YAXIS_MAX_VALUE"=>			"Y massimo",
	"S_CALCULATED"=>			"Automatico",
	"S_FIXED"=>				"Fisso",

//	history.php
	"S_LAST_HOUR_GRAPH"=>			"Grafico dell'ultima ora",
	"S_LAST_HOUR_GRAPH_DIFF"=>		"Grafico dell'ultima ora (diff)",
	"S_VALUES_OF_LAST_HOUR"=>		"Valori dell'ultima ora",
	"S_VALUES_OF_SPECIFIED_PERIOD"=>	"Valori del periodo selezionato",
	"S_VALUES_IN_PLAIN_TEXT_FORMAT"=>	"Valori in formato testo",
	"S_TIMESTAMP"=>				"Data e ora",

//	hosts.php
	"S_HOSTS"=>				"Dispositivi",
	"S_ITEMS"=>				"Parametri",
	"S_TRIGGERS"=>				"Inneschi",
	"S_GRAPHS"=>				"Grafici",
	"S_HOST_ADDED"=>			"Dispositivo aggiunto!",
	"S_CANNOT_ADD_HOST"=>			"Non posso aggiungere il dispositivo",
	"S_ITEMS_ADDED"=>			"Parametri aggiunti!",
	"S_CANNOT_ADD_ITEMS"=>			"Non posso aggiungere i parametri",
	"S_HOST_UPDATED"=>			"Dispositivo aggiornato!",
	"S_CANNOT_UPDATE_HOST"=>		"Non posso aggiornare il dispositivo",
	"S_HOST_STATUS_UPDATED"=>		"Stato del dispositivo aggiornato!",
	"S_CANNOT_UPDATE_HOST_STATUS"=>		"Non posso aggiornare lo stato del dispositivo",
	"S_HOST_DELETED"=>			"Dispositivo rimosso!",
	"S_CANNOT_DELETE_HOST"=>		"Non posso rimuovere il dispositivo",
	"S_TEMPLATE_LINKAGE_ADDED"=>		"Collegamento al modello agguinto!",
	"S_CANNOT_ADD_TEMPLATE_LINKAGE"=>	"Non posso collegare al modello",
	"S_TEMPLATE_LINKAGE_UPDATED"=>		"Collegamento al modello aggiornato!",
	"S_CANNOT_UPDATE_TEMPLATE_LINKAGE"=>	"Non posso aggionare il collegamento al modello",
	"S_TEMPLATE_LINKAGE_DELETED"=>		"Collegamento al modello rimosso!",
	"S_CANNOT_DELETE_TEMPLATE_LINKAGE"=>	"Non posso rimuovere il collegamento al modello",
	"S_CONFIGURATION_OF_HOSTS_AND_HOST_GROUPS"=>"CONFIGURAZIONE DISPOSITIVI E GRUPPI DI DISPOSITIVI",
	"S_HOST_GROUPS_BIG"=>			"GRUPPI DI DISPOSITIVI",
	"S_NO_HOST_GROUPS_DEFINED"=>		"Nessun gruppo di dispositivi definito",
	"S_NO_LINKAGES_DEFINED"=>		"Nessun collegamento definito",
	"S_NO_HOSTS_DEFINED"=>			"Nessun dispositivo definito",
	"S_HOSTS_BIG"=>				"DISPOSITIVI",
	"S_HOST"=>				"Dispositivo",
	"S_IP"=>				"IP",
	"S_PORT"=>				"Porta",
	"S_MONITORED"=>				"Abilitato",
	"S_NOT_MONITORED"=>			"Disabilitato",
	"S_UNREACHABLE"=>			"Irraggiungibile",
	"S_TEMPLATE"=>				"Modello",
	"S_DELETED"=>				"Rimosso",
	"S_UNKNOWN"=>				"Non rilevabile",
	"S_GROUPS"=>				"Gruppi",
	"S_NEW_GROUP"=>				"Nuovo gruppo",
	"S_USE_IP_ADDRESS"=>			"Usa l'indirizzo IP",
	"S_IP_ADDRESS"=>			"Indirizzo IP",
//	"S_USE_THE_HOST_AS_A_TEMPLATE"=>		"Usa il dispositivo come modello",
	"S_USE_TEMPLATES_OF_THIS_HOST"=>	"Utilizza i parametri del seguente dispositivo",
	"S_DELETE_SELECTED_HOST_Q"=>		"Rimuovi il dispositivo selezionato?",
	"S_GROUP_NAME"=>			"Nome del gruppo",
	"S_HOST_GROUP"=>			"Gruppo del dispositivo",
	"S_HOST_GROUPS"=>			"Gruppi del dispositivo",
	"S_UPDATE"=>				"Aggiorna",
	"S_AVAILABILITY"=>			"Disponibilit�",
	"S_AVAILABLE"=>				"Disponibile",
	"S_NOT_AVAILABLE"=>			"Errore!",

//	items.php
	"S_CONFIGURATION_OF_ITEMS"=>		"Configurazione parametri",
	"S_CONFIGURATION_OF_ITEMS_BIG"=>	"CONFIGURAZIONE PARAMETRI",
	"S_CANNOT_UPDATE_ITEM"=>		"Non posso aggiornare il parametro",
	"S_STATUS_UPDATED"=>			"Stato aggiornato!",
	"S_CANNOT_UPDATE_STATUS"=>		"Non posso aggiornare lo stato!",
	"S_CANNOT_ADD_ITEM"=>			"Non posso aggiungere il parametro",
	"S_ITEM_DELETED"=>			"Parametro rimosso!",
	"S_CANNOT_DELETE_ITEM"=>		"Non posso rimuovere il parametro",
	"S_ITEMS_DELETED"=>			"Parametri rimossi!",
	"S_CANNOT_DELETE_ITEMS"=>		"Non posso rimuovere i parametri",
	"S_ITEMS_ACTIVATED"=>			"Parametro attivato",
	"S_CANNOT_ACTIVATE_ITEMS"=>		"Non posso attivare il parametro",
	"S_ITEMS_DISABLED"=>			"Parametri disabilitati!",
	"S_SERVERNAME"=>			"Server Name",
	"S_KEY"=>				"Chiave",
	"S_DESCRIPTION"=>			"Descrizione",
	"S_UPDATE_INTERVAL"=>			"Aggiorna ogni (in sec)",
	"S_HISTORY"=>				"Storico",
	"S_TRENDS"=>				"Trends (in gg)",
	"S_SHORT_NAME"=>			"Abbreviazione",
	"S_ZABBIX_AGENT"=>			"Modulo ZABBIX (PASSIVO)",
	"S_ZABBIX_AGENT_ACTIVE"=>		"Modulo ZABBIX (ATTIVO)",
	"S_SNMPV1_AGENT"=>			"Modulo SNMPv1",
	"S_ZABBIX_TRAPPER"=>			"Trapper ZABBIX",
	"S_SIMPLE_CHECK"=>			"Controlli base",
	"S_SNMPV2_AGENT"=>			"Modulo SNMPv2",
	"S_SNMPV3_AGENT"=>			"Modulo SNMPv3",
	"S_ZABBIX_INTERNAL"=>			"ZABBIX interno",
	"S_ZABBIX_UNKNOWN"=>			"Sconosciuto",
	"S_ACTIVE"=>				"Attivo",
	"S_NOT_ACTIVE"=>			"Non attivo",
	"S_NOT_SUPPORTED"=>			"Non supportato",
	"S_ACTIVATE_SELECTED_ITEMS_Q"=>		"Attivare i parametri selezionati?",
	"S_DISABLE_SELECTED_ITEMS_Q"=>		"Disattivare i parametri selezionati?",
	"S_DELETE_SELECTED_ITEMS_Q"=>		"Rimuovere i parametri selezionati?",
	"S_EMAIL"=>				"Email",
	"S_SCRIPT"=>				"Script",
	"S_UNITS"=>				"Unit�",
	"S_MULTIPLIER"=>			"Moltiplicatore",
	"S_UPDATE_INTERVAL_IN_SEC"=>		"Intervallo di aggiornameto (in sec)",
	"S_KEEP_HISTORY_IN_DAYS"=>		"Storico da mantenere (in gg)",
	"S_KEEP_TRENDS_IN_DAYS"=>		"Trend da mantenere (in gg)",
	"S_TYPE_OF_INFORMATION"=>		"Tipo di dato",
	"S_STORE_VALUE"=>			"Memorizza il valore",
	"S_NUMERIC"=>				"Numerico",
	"S_CHARACTER"=>				"Alfabetico",
	"S_LOG"=>				"Log",
	"S_AS_IS"=>				"Cos� com'�",
	"S_DELTA_SPEED_PER_SECOND"=>		"Come velocit� (delta nell'intervallo di tempo)",
	"S_DELTA_SIMPLE_CHANGE"=>		"Come differenza semplice tra i due ultimi valori",
	"S_ITEM"=>				"Parametro",
	"S_SNMP_COMMUNITY"=>			"SNMP community",
	"S_SNMP_OID"=>				"SNMP OID",
	"S_SNMP_PORT"=>				"SNMP port",
	"S_ALLOWED_HOSTS"=>			"Dispositivi concessi",
	"S_SNMPV3_SECURITY_NAME"=>		"SNMPv3 security name",
	"S_SNMPV3_SECURITY_LEVEL"=>		"SNMPv3 security level",
	"S_SNMPV3_AUTH_PASSPHRASE"=>		"SNMPv3 auth passphrase",
	"S_SNMPV3_PRIV_PASSPHRASE"=>		"SNMPv3 priv passphrase",
	"S_CUSTOM_MULTIPLIER"=>			"Moltiplicatore variabile",
	"S_DO_NOT_USE"=>			"Non usare",
	"S_USE_MULTIPLIER"=>			"Usa il moltiplicatore",
	"S_SELECT_HOST_DOT_DOT_DOT"=>		"Seleziona dispositivo...",

//	latestalarms.php
	"S_LATEST_EVENTS"=>			"Ultimi eventi",
	"S_HISTORY_OF_EVENTS_BIG"=>		"STORICO EVENTI",

//	latest.php
	"S_LAST_CHECK"=>			"Ultimo aggiornamento",
	"S_LAST_CHECK_BIG"=>			"ULTIMO AGGIORNAMENTO",
	"S_LAST_VALUE"=>			"Ultimo dato",

//	sysmap.php
	"S_LABEL"=>				"Etichetta",
	"S_X"=>					"X",
	"S_Y"=>					"Y",
	"S_ICON"=>				"Icona",
	"S_HOST_1"=>				"Dispositivo 1",
	"S_HOST_2"=>				"Dispositivo 2",
	"S_LINK_STATUS_INDICATOR"=>		"Indicatore dello stato del collegamento",

//	map.php
	"S_OK_BIG"=>				"OK",
	"S_PROBLEMS_SMALL"=>			"Problemi...",
	"S_ZABBIX_URL"=>			"http://www.zabbix.com",

//	maps.php
	"S_NETWORK_MAPS"=>			"Mappe di rete",
	"S_NETWORK_MAPS_BIG"=>			"MAPPE DI RETE",
	"S_NO_MAPS_TO_DISPLAY"=>		"Nessuna mappa da visualizzare",
	"S_SELECT_MAP_TO_DISPLAY"=>		"Seleziona la mappa da visualizzare",
	"S_SELECT_MAP_DOT_DOT_DOT"=>		"Seleziona la mappa...",
	"S_BACKGROUND_IMAGE"=>			"Immagine di sfondo",
	"S_ICON_LABEL_TYPE"=>			"Tipo etichetta dell'icona",
	"S_HOST_LABEL"=>			"Etichetta del dispositivo",
	"S_HOST_NAME"=>				"Nome del dispositivo",
	"S_STATUS_ONLY"=>			"Solo lo stato",
	"S_NOTHING"=>				"Niente",

//	media.php
	"S_MEDIA"=>				"Mezzi",
	"S_MEDIA_BIG"=>				"MEZZI",
	"S_MEDIA_ACTIVATED"=>			"Mezzo attivato!",
	"S_CANNOT_ACTIVATE_MEDIA"=>		"Non posso attivare il mezzo",
	"S_MEDIA_DISABLED"=>			"Mezzo disattivato!",
	"S_CANNOT_DISABLE_MEDIA"=>		"Non posso disattivare il mezzo",
	"S_MEDIA_ADDED"=>			"Mezzo aggiunto!",
	"S_CANNOT_ADD_MEDIA"=>			"Non posso aggiungere il mezzo",
	"S_MEDIA_UPDATED"=>			"Mezzo aggiornato!",
	"S_CANNOT_UPDATE_MEDIA"=>		"Non posso aggiornare il mezzo",
	"S_MEDIA_DELETED"=>			"Mezzo rimosso!",
	"S_CANNOT_DELETE_MEDIA"=>		"Non posso rimuovere il mezzo",
	"S_SEND_TO"=>				"Spedisci a",
	"S_WHEN_ACTIVE"=>			"Quando � attivo",
	"S_NO_MEDIA_DEFINED"=>			"Nessun mezzo definito",
	"S_NEW_MEDIA"=>				"Nuovo mezzo",
	"S_USE_IF_SEVERITY"=>			"Usa se la severit� �",
	"S_DELETE_SELECTED_MEDIA_Q"=>		"Cancella il mezzo selezionato?",

//	Menu
	"S_MENU_LATEST_VALUES"=>		"ULTIMI VALORI INSERITI",
	"S_MENU_TRIGGERS"=>			"INNESCHI",
	"S_MENU_QUEUE"=>			"IN CODA",
	"S_MENU_ALARMS"=>			"ALLARMI",
	"S_MENU_ALERTS"=>			"ALERTS",
	"S_MENU_NETWORK_MAPS"=>			"MAPPE DI RETE",
	"S_MENU_GRAPHS"=>			"GRAFICI",
	"S_MENU_SCREENS"=>			"SCHERMATE",
	"S_MENU_IT_SERVICES"=>			"SERVIZI IT",
	"S_MENU_HOME"=>				"HOME",
	"S_MENU_ABOUT"=>			"INFO",
	"S_MENU_STATUS_OF_ZABBIX"=>		"STATO",
	"S_MENU_AVAILABILITY_REPORT"=>		"RAPPORTO DI STATO",
	"S_MENU_CONFIG"=>			"CONFIGURAZIONE",
	"S_MENU_USERS"=>			"UTENTI",
	"S_MENU_HOSTS"=>			"DISPOSITIVI",
	"S_MENU_ITEMS"=>			"PARAMETRI",
	"S_MENU_AUDIT"=>			"AUDIT",

//	overview.php
	"S_SELECT_GROUP_DOT_DOT_DOT"=>		"Seleziona...",
	"S_OVERVIEW"=>				"Panoramica",
	"S_OVERVIEW_BIG"=>			"PANORAMICA",
	"S_EXCL"=>				"!",
	"S_DATA"=>				"Dati",

//	queue.php
	"S_QUEUE_BIG"=>				"CODA",
	"S_QUEUE_OF_ITEMS_TO_BE_UPDATED_BIG"=>	"CODA DEI PARAMETRI DA AGGIORNARE",
	"S_NEXT_CHECK"=>			"Prossimo controllo",
	"S_THE_QUEUE_IS_EMPTY"=>		"La coda � vuota",
	"S_TOTAL"=>				"Totale",
	"S_COUNT"=>				"Quanti?",
	"S_5_SECONDS"=>				"5 secondi",
	"S_10_SECONDS"=>			"10 secondi",
	"S_30_SECONDS"=>			"30 secondi",
	"S_1_MINUTE"=>				"1 minuto",
	"S_5_MINUTES"=>				"5 minuti",
	"S_MORE_THAN_5_MINUTES"=>		"Pi� di 5 minuti",

//	report1.php
	"S_STATUS_OF_ZABBIX"=>			"Stato del server",
	"S_STATUS_OF_ZABBIX_BIG"=>		"STATO DEL SERVER",
	"S_VALUE"=>				"Valore",
	"S_ZABBIX_SERVER_IS_RUNNING"=>		"Il server � attivo?",
	"S_NUMBER_OF_VALUES_STORED"=>		"Numero di dati memorizzati",
	"S_NUMBER_OF_TRENDS_STORED"=>		"Numero di trend memorizzati",
	"S_NUMBER_OF_ALARMS"=>			"Numero di allarmi",
	"S_NUMBER_OF_ALERTS"=>			"Numero di azioni intraprese",
	"S_NUMBER_OF_TRIGGERS_ENABLED_DISABLED"=>"Numero di inneschi (abilitati/disabilitati)",
	"S_NUMBER_OF_ITEMS_ACTIVE_TRAPPER"=>	"Numero di parametri (attivi/trapper/non attivi/non supportati)",
	"S_NUMBER_OF_USERS"=>			"Numero di utenti",
	"S_NUMBER_OF_HOSTS_MONITORED"=>		"Numero di dispositivi (abilitati/disabilitati/modelli/deleted)",
	"S_YES"=>				"S�",
	"S_NO"=>				"No",

//	report2.php
	"S_AVAILABILITY_REPORT"=>		"Rapporto di stato",
	"S_AVAILABILITY_REPORT_BIG"=>		"RAPPORTO DI STATO",
	"S_SHOW"=>				"Mostra...",
	"S_TRUE"=>				"Vero",
	"S_FALSE"=>				"Falso",

//	report3.php
	"S_IT_SERVICES_AVAILABILITY_REPORT_BIG"=>	"RAPPORTO SERVIZI IT",
	"S_FROM"=>				"Da",
	"S_TILL"=>				"Fino a",
	"S_OK"=>				"Ok",
	"S_PROBLEMS"=>				"Qualche problema",
	"S_PERCENTAGE"=>			"Percentuale",
	"S_SLA"=>				"SLA",
	"S_DAY"=>				"Giorno",
	"S_MONTH"=>				"Mese",
	"S_YEAR"=>				"Anno",
	"S_DAILY"=>				"Quotidianamente",
	"S_WEEKLY"=>				"Settimanalmente",
	"S_MONTHLY"=>				"Mensilmente",
	"S_YEARLY"=>				"Annuariamente",

//	screenconf.php
	"S_SCREENS"=>				"Schermate",
	"S_SCREEN"=>				"Nuova schermata",
	"S_CONFIGURATION_OF_SCREENS_BIG"=>	"CONFIGURAZIONE DELLE SCHERMATE",
	"S_SCREEN_ADDED"=>			"Schermata aggiunta!",
	"S_CANNOT_ADD_SCREEN"=>			"Non posso aggiungere la schermata",
	"S_SCREEN_UPDATED"=>			"Schermata aggiornata!",
	"S_CANNOT_UPDATE_SCREEN"=>		"Non posso aggiornare la schermata",
	"S_SCREEN_DELETED"=>			"Schermata rimossa!",
	"S_CANNOT_DELETE_SCREEN"=>		"Non posso rimuovere la schermata",
	"S_COLUMNS"=>				"Colonne",
	"S_ROWS"=>				"Righe",
	"S_NO_SCREENS_DEFINED"=>		"Nessuna schermata definita",
	"S_DELETE_SCREEN_Q"=>			"Rimuovo la schermata?",
	"S_CONFIGURATION_OF_SCREEN_BIG"=>	"CONFIGURAZIONE DELLA SCHERMATA",
	"S_SCREEN_CELL_CONFIGURATION"=>		"Configurazione della cella",
	"S_RESOURCE"=>				"Risorsa",
	"S_SIMPLE_GRAPH"=>			"Grafico semplice",
	"S_GRAPH_NAME"=>			"Nome del grafico",
	"S_WIDTH"=>				"Larghezza pixels",
	"S_HEIGHT"=>				"Altezza pixels",
	"S_EMPTY"=>				"Vuoto",

//	screenedit.php
	"S_MAP"=>				"Mappa",
	"S_PLAIN_TEXT"=>			"In formato testo",
	"S_COLUMN_SPAN"=>			"Espandi su X colonne",
	"S_ROW_SPAN"=>				"Espandi su X righe",

//	screens.php
	"S_CUSTOM_SCREENS"=>			"Schermate definite",
	"S_SCREENS_BIG"=>			"SCHERMATE DEFINITE",
	"S_NO_SCREENS_TO_DISPLAY"=>		"Nessuna schermata da visualizzare",
	"S_SELECT_SCREEN_TO_DISPLAY"=>		"Seleziona la schermata da visualizzare",
	"S_SELECT_SCREEN_DOT_DOT_DOT"=>		"Seleziona la schermata ...",

//	services.php
	"S_IT_SERVICES"=>			"Servizi IT",
	"S_SERVICE_UPDATED"=>			"Servizio aggiornato!",
	"S_CANNOT_UPDATE_SERVICE"=>		"Non posso aggiornare il servizio",
	"S_SERVICE_ADDED"=>			"Servizio aggiunto!",
	"S_CANNOT_ADD_SERVICE"=>		"Non posso aggiungere il servizio",
	"S_LINK_ADDED"=>			"Collegamento aggiunto!",
	"S_CANNOT_ADD_LINK"=>			"Non posso aggiungere il collegamento",
	"S_SERVICE_DELETED"=>			"Servizio rimosso!",
	"S_CANNOT_DELETE_SERVICE"=>		"Non posso rimuovere il servizio",
	"S_LINK_DELETED"=>			"Collegamento rimosso!",
	"S_CANNOT_DELETE_LINK"=>		"Non posso rimuovere il collegamento",
	"S_STATUS_CALCULATION"=>		"Calcolo dello stato",
	"S_STATUS_CALCULATION_ALGORITHM"=>	"Algoritmo di calcolo dello stato",
	"S_NONE"=>				"Assente",
	"S_MAX_OF_CHILDS"=>			"MASSIMO numero di sottoelementi",
	"S_MIN_OF_CHILDS"=>			"MINIMO numero di sottoelementi",
	"S_SERVICE_1"=>				"Servizio 'padre'",
	"S_SERVICE_2"=>				"Sottoservizio",
	"S_SOFT_HARD_LINK"=>			"Collegamento soft/hard",
	"S_SOFT"=>				"Soft",
	"S_HARD"=>				"Hard",
	"S_DO_NOT_CALCULATE"=>			"Nessun calcolo",
	"S_MAX_BIG"=>				"il valore MAX",
	"S_MIN_BIG"=>				"il valore MIN",
	"S_SHOW_SLA"=>				"Mostra lo SLA",
	"S_ACCEPTABLE_SLA_IN_PERCENT"=>		"Percentuale accettabile di SLA",
	"S_LINK_TO_TRIGGER_Q"=>			"Collegato all'innesco?",
	"S_SORT_ORDER_0_999"=>			"Priorit� (0->999)",
	"S_DELETE_SERVICE_Q"=>			"Rimuovi la coda servizi",
	"S_LINK_TO"=>				"Aggiungi collegamento",
	"S_SOFT_LINK_Q"=>			"Collegamento soft?",
	"S_ADD_SERVER_DETAILS"=>		"Aggiungi i dettagli del seguente dispositivo",
	"S_TRIGGER"=>				"Specifica l'innesco collegato",
	"S_SERVER"=>				"Dispositivo",
	"S_DELETE"=>				"Rimuovi",

//	srv_status.php
	"S_IT_SERVICES_BIG"=>			"SERVIZI IT",
	"S_SERVICE"=>				"Servizio",
	"S_REASON"=>				"Causa",
	"S_SLA_LAST_7_DAYS"=>			"SLA (ultimi 7 gg)",
	"S_PLANNED_CURRENT_SLA"=>		"SLA desiderato / SLA attuale",
	"S_TRIGGER_BIG"=>			"INNESCO",

//	triggers.php
	"S_CONFIGURATION_OF_TRIGGERS"=>		"Configurazione inneschi",
	"S_CONFIGURATION_OF_TRIGGERS_BIG"=>	"CONFIGURAZIONE INNESCHI",
	"S_DEPENDENCY_ADDED"=>			"Dipendenza aggiunta!",
	"S_CANNOT_ADD_DEPENDENCY"=>		"Non posso aggiungere la dipendenza",
	"S_TRIGGERS_UPDATED"=>			"Innesco aggiornato!",
	"S_CANNOT_UPDATE_TRIGGERS"=>		"Non posso aggiornare l'innesco",
	"S_TRIGGERS_DISABLED"=>			"Innesco disabilitato",
	"S_CANNOT_DISABLE_TRIGGERS"=>		"Non posso disabilitare l'innesco",
	"S_TRIGGERS_DELETED"=>			"Inneschi rimossi!",
	"S_CANNOT_DELETE_TRIGGERS"=>		"Non posso rimuovere gli innesschi",
	"S_TRIGGER_DELETED"=>			"Innesco rimosso!",
	"S_CANNOT_DELETE_TRIGGER"=>		"Non posso rimuovere l'innesco",
	"S_INVALID_TRIGGER_EXPRESSION"=>	"Formula non valida per il calcolo dell'innesco",
	"S_TRIGGER_ADDED"=>			"Innesco aggiunto!",
	"S_CANNOT_ADD_TRIGGER"=>		"Non posso aggiungere l'innesco",
	"S_SEVERITY"=>				"Livello",
	"S_EXPRESSION"=>			"Formula di calcolo",
	"S_DISABLED"=>				"Disabilitato",
	"S_ENABLED"=>				"Abilitato",
	"S_ENABLE_SELECTED_TRIGGERS_Q"=>	"Abilita gli inneschi specificati?",
	"S_DISABLE_SELECTED_TRIGGERS_Q"=>	"Disabilita gli inneschi specificati?",
	"S_CHANGE"=>				"Differenza",
	"S_TRIGGER_UPDATED"=>			"Innesco aggiornato!",
	"S_CANNOT_UPDATE_TRIGGER"=>		"Non posso aggiornare l'innesco",
	"S_DEPENDS_ON"=>			"Dipende da",

//	tr_comments.php
	"S_TRIGGER_COMMENTS"=>			"Note sull'innesco",
	"S_TRIGGER_COMMENTS_BIG"=>		"NOTE SULL'INNESCO",
	"S_COMMENT_UPDATED"=>			"Commento aggiornato!",
	"S_CANNOT_UPDATE_COMMENT"=>		"Non posso aggiornare il commento",
	"S_ADD"=>				"Aggiungi",

//	tr_status.php
	"S_STATUS_OF_TRIGGERS"=>		"Stato degli inneschi",
	"S_STATUS_OF_TRIGGERS_BIG"=>		"STATO DEGLI INNESCHI",
	"S_SHOW_ONLY_TRUE"=>			"Mostra solo quelli a stato 'VERO'",
	"S_HIDE_ACTIONS"=>			"Nascondi le azioni",
	"S_SHOW_ACTIONS"=>			"Mostra le azioni",
	"S_SHOW_ALL_TRIGGERS"=>			"Mostra tutti gli inneschi",
	"S_HIDE_DETAILS"=>			"Nascondi i dettagli",
	"S_SHOW_DETAILS"=>			"Mostra i dettagli",
	"S_SELECT"=>				"Mostra barra di selezione",
	"S_HIDE_SELECT"=>			"Nascondi barra di selezione",
	"S_TRIGGERS_BIG"=>			"INNESCHI",
	"S_DESCRIPTION_BIG"=>			"DESCRIZIONE",
	"S_SEVERITY_BIG"=>			"LIVELLO DI ALLARME",
	"S_LAST_CHANGE_BIG"=>			"ULTIMO INNESCO IL",
	"S_LAST_CHANGE"=>			"Ultimo innesco il",
	"S_COMMENTS"=>				"Commenti",

//	users.php
	"S_USERS"=>				"Utenti",
	"S_USER_ADDED"=>			"Utente aggiunto",
	"S_CANNOT_ADD_USER"=>			"Non posso aggiungere l'utente",
	"S_CANNOT_ADD_USER_BOTH_PASSWORDS_MUST"=>"Attenzione! Le due password devono essere uguali.",
	"S_USER_DELETED"=>			"Utente rimosso",
	"S_CANNOT_DELETE_USER"=>		"Non posso rimuovere l'utente",
	"S_PERMISSION_DELETED"=>		"Permesso rimosso",
	"S_CANNOT_DELETE_PERMISSION"=>		"Non posso rimuovere il permesso",
	"S_PERMISSION_ADDED"=>			"Permesso aggiunto",
	"S_CANNOT_ADD_PERMISSION"=>		"Non posso aggiungere il permesso",
	"S_USER_UPDATED"=>			"Aggiornamento eseguito",
	"S_CANNOT_UPDATE_USER"=>		"Non posso eseguire l'aggiornamento",
	"S_CANNOT_UPDATE_USER_BOTH_PASSWORDS"=>	"Attenzione! Le due password devono essere uguali.",
	"S_GROUP_ADDED"=>			"Gruppo aggiunto",
	"S_CANNOT_ADD_GROUP"=>			"Non posso aggiungere il gruppo",
	"S_GROUP_UPDATED"=>			"Gruppo aggiornato",
	"S_CANNOT_UPDATE_GROUP"=>		"Non posso aggiornare il gruppo",
	"S_GROUP_DELETED"=>			"Gruppo rimosso",
	"S_CANNOT_DELETE_GROUP"=>		"Non posso rimuovere il gruppo",
	"S_CONFIGURATION_OF_USERS_AND_USER_GROUPS"=>"CONFIGURAZIONE UTENTI E GRUPPI",
	"S_USER_GROUPS_BIG"=>			"GRUPPI",
	"S_USERS_BIG"=>				"UTENTI",
	"S_USER_GROUPS"=>			"Gruppi utenti",
	"S_MEMBERS"=>				"Membri",
	"S_TEMPLATES"=>				"Modelli",
	"S_HOSTS_TEMPLATES_LINKAGE"=>		"Collegamento dispositivi/modelli",
	"S_CONFIGURATION_OF_TEMPLATES_LINKAGE"=>"CONFIGURAZIONE DEI COLLEGAMENTI CON I MODELLI",
	"S_LINKED_TEMPLATES_BIG"=>		"MODELLI COLLEGATI",
	"S_NO_USER_GROUPS_DEFINED"=>		"Nessun gruppo utenti definito",
	"S_ALIAS"=>				"Alias",
	"S_NAME"=>				"Nome",
	"S_SURNAME"=>				"Cognome",
	"S_IS_ONLINE_Q"=>			"E' collegato?",
	"S_NO_USERS_DEFINED"=>			"Nessun utente definito",
	"S_PERMISSION"=>			"Permessi",
	"S_RIGHT"=>				"Diritto",
	"S_RESOURCE_NAME"=>			"Nome della risorsa",
	"S_READ_ONLY"=>				"Sola lettura",
	"S_READ_WRITE"=>			"Lettura-scrittura",
	"S_HIDE"=>				"Nascondi",
	"S_PASSWORD"=>				"Password",
	"S_PASSWORD_ONCE_AGAIN"=>		"Password (ripeti)",
	"S_URL_AFTER_LOGIN"=>			"URL (dopo il login)",
	"S_AUTO_LOGOUT_IN_SEC"=>		"Auto-logout (in secondi, 0=disabilitato)",
	"S_SCREEN_REFRESH"=>                    "Refresh (in seconds)",

//	audit.php
	"S_AUDIT_LOG"=>				"Audit log",
	"S_AUDIT_LOG_BIG"=>			"AUDIT LOG",
	"S_ACTION"=>				"Action",
	"S_DETAILS"=>				"Details",
	"S_UNKNOWN_ACTION"=>			"Unknown action",
	"S_ADDED"=>				"Added",
	"S_UPDATED"=>				"Updated",
	"S_LOGGED_IN"=>				"Logged in",
	"S_LOGGED_OUT"=>			"Logged out",
	"S_MEDIA_TYPE"=>			"Media type",
	"S_GRAPH_ELEMENT"=>			"Graph element",

//	profile.php
	"S_USER_PROFILE_BIG"=>			"PROFILO UTENTE",
	"S_USER_PROFILE"=>			"Profilo utente",
	"S_LANGUAGE"=>				"Lingua",
	"S_ENGLISH_GB"=>			"Inglese (GB)",
	"S_FRENCH_FR"=>				"Francese (FR)",
	"S_GERMAN_DE"=>				"Tedesco (DE)",
	"S_LATVIAN_LV"=>			"Lituano (LV)",
	"S_RUSSIAN_RU"=>			"Russo (RU)",
	"S_ITALIAN_IT"=>			"Italiano (IT)",

//	index.php
	"S_ZABBIX_BIG"=>			"ZABBIX",

//	bulkloader.php
	"S_MENU_BULKLOADER"=>			"Bulkloader",
	"S_BULKLOADER_MAIN"=>			"Bulkloader: Main Page",
	"S_BULKLOADER_HOSTS"=>			"Bulkloader: Hosts",
	"S_BULKLOADER_ITEMS"=>			"Bulkloader: Items",
	"S_BULKLOADER_USERS"=>			"Bulkloader: Users",
	"S_BULKLOADER_TRIGGERS"=>		"Bulkloader: Triggers",
	"S_BULKLOADER_ACTIONS"=>		"Bulkloader: Actions",
	"S_BULKLOADER_ITSERVICES"=>		"Bulkloader: IT Services",

	"S_BULKLOADER_IMPORT_HOSTS"=>		"Import Hosts",
	"S_BULKLOADER_IMPORT_ITEMS"=>		"Import Items",
	"S_BULKLOADER_IMPORT_USERS"=>		"Import Users",
	"S_BULKLOADER_IMPORT_TRIGGERS"=>	"Import Triggers",
	"S_BULKLOADER_IMPORT_ACTIONS"=>		"Import Actions",
	"S_BULKLOADER_IMPORT_ITSERVICES"=>	"Import IT Services",

//	Menu

	"S_HELP"=>				"Aiuto",
	"S_PROFILE"=>				"Profilo",
	);
?>
