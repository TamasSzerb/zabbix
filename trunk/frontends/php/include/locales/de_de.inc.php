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
**
**
** Translation zabbix version: 1.1alpha12
** Translation version       : 0.1
** Translation date          : 15.08.2005
** Translated by             : J. Pawlowski <jp@ProPenguin.net>
**
**/
?>
<?php
	global $TRANSLATION;

	$de_de=array(

	"S_DATE_FORMAT_YMDHMS"=>		"d M H:i:s",
	"S_DATE_FORMAT_YMD"=>			"d M Y",
	"S_HTML_CHARSET"=>			"iso-8859-1",

//	about.php
	"S_ABOUT_ZABBIX"=>			"&Uuml;ber ZABBIX",
	"S_INFORMATION_ABOUT_ZABBIX"=>		"Informationen &uuml;ber ZABBIX (v1.1beta3)",
	"S_HOMEPAGE_OF_ZABBIX"=>		"Homepage von ZABBIX",
	"S_HOMEPAGE_OF_ZABBIX_DETAILS"=>	"Dies ist die offizielle Webseite von ZABBIX.",
	"S_LATEST_ZABBIX_MANUAL"=>		"Aktuelles ZABBIX Handbuch",
	"S_LATEST_ZABBIX_MANUAL_DETAILS"=>	"Aktuellste Version des ZABBIX Benutzerhandbuches.",
	"S_DOWNLOADS"=>				"Downloads",
	"S_DOWNLOADS_DETAILS"=>			"Aktuellste Versionen von ZABBIX k&ouml;nnen hier heruntergeladen werden.",
	"S_FEATURE_REQUESTS"=>			"Feature Anfragen",
	"S_FEATURE_REQUESTS_DETAILS"=>		"Falls Sie Vorschl&auml;ge zu neuen Funktionen haben, klicken Sie hier.",
	"S_FORUMS"=>				"Forum",
	"S_FORUMS_DETAILS"=>			"Diskussionen Rund um ZABBIX.",
	"S_BUG_REPORTS"=>			"Bug melden",
	"S_BUG_REPORTS_DETAILS"=>		"Haben Sie einen Fehler in ZABBIX gefunden? Bitte melden Sie diesen hier.",
	"S_MAILING_LISTS"=>			"Mailinglisten",
	"S_MAILING_LISTS_DETAILS"=>		"Offizielle ZABBIX Mailing Listen.",
	
//	actions.php
	"S_ACTIONS"=>				"Aktionen",
	"S_ACTION_ADDED"=>			"Aktion erfolgreich hinzugef&uuml;gt",
	"S_CANNOT_ADD_ACTION"=>			"Aktion konnte nicht hinzugef&uuml;gt werden",
	"S_ACTION_UPDATED"=>			"Aktion aktualisiert",
	"S_CANNOT_UPDATE_ACTION"=>		"Aktion konnte nicht aktualisiert werden",
	"S_ACTION_DELETED"=>			"Aktion gel&ouml;scht",
	"S_CANNOT_DELETE_ACTION"=>		"Aktion konnte nicht gel&ouml;scht werden",
	"S_SCOPE"=>				"Bereich",
	"S_SEND_MESSAGE_TO"=>			"Nachricht senden an",
	"S_WHEN_TRIGGER"=>			"wenn Ausl&ouml;ser",
	"S_DELAY"=>				"Verz&ouml;gerung",
	"S_SUBJECT"=>				"Betreff",
	"S_ON"=>				"AN",
	"S_OFF"=>				"AUS",
	"S_NO_ACTIONS_DEFINED"=>		"Keine Aktion definiert",
	"S_NEW_ACTION"=>			"Neue Aktion",
	"S_SINGLE_USER"=>			"Einzelner Benutzer",
	"S_USER_GROUP"=>			"Benutzergruppe",
	"S_GROUP"=>				"Gruppe",
	"S_USER"=>				"Benutzer",
	"S_WHEN_TRIGGER_BECOMES"=>		"wenn Ausl&ouml;ser",
	"S_ON_OR_OFF"=>				"AN oder AUS",
	"S_DELAY_BETWEEN_MESSAGES_IN_SEC"=>	"Verz&ouml;gerung zwischen Nachrichten (in Sek.)",
	"S_MESSAGE"=>				"Nachricht",
	"S_THIS_TRIGGER_ONLY"=>			"nur dieser Ausl&ouml;ser",
	"S_ALL_TRIGGERS_OF_THIS_HOST"=>		"Alle Ausl&ouml;ser dieses Systems",
	"S_ALL_TRIGGERS"=>			"Alle Ausl&ouml;ser",
	"S_USE_IF_TRIGGER_SEVERITY"=>		"Aktivieren, wenn Gewichtigkeit gleich oder gr&ouml;&szlig;er als",
	"S_NOT_CLASSIFIED"=>			"nicht spezifiziert",
	"S_INFORMATION"=>			"Information",
	"S_WARNING"=>				"Warnung",
	"S_AVERAGE"=>				"Durchschnitt",
	"S_HIGH"=>				"Hoch",
	"S_DISASTER"=>				"Desaster",

//	alarms.php
	"S_ALARMS"=>				"Alarme",
	"S_ALARMS_SMALL"=>			"Alarme",
	"S_ALARMS_BIG"=>			"ALARME",
	"S_SHOW_ONLY_LAST_100"=>		"Letzte 100 Alarme",
	"S_SHOW_ALL"=>				"Alle Alarme",
	"S_TIME"=>				"Zeit",
	"S_STATUS"=>				"&Uuml;berwachung",
	"S_DURATION"=>				"Dauer",
	"S_SUM"=>				"Summe",
	"S_TRUE_BIG"=>				"WAHR",
	"S_FALSE_BIG"=>				"FALSCH",
	"S_DISABLED_BIG"=>			"DEAKTIVIERT",
	"S_UNKNOWN_BIG"=>			"UNBEKANNT",

//	alerts.php
	"S_ALERT_HISTORY_SMALL"=>		"Benachrichtigungsverlauf",
	"S_ALERT_HISTORY_BIG"=>			"BENACHRICHTIGUNGSVERLAUF",
	"S_ALERTS_BIG"=>			"BENACHRICHTIGUNGEN",
	"S_TYPE"=>				"Typ",
	"S_RECIPIENTS"=>			"Empf&auml;nger",
	"S_ERROR"=>				"Fehler",
	"S_SENT"=>				"gesendet",
	"S_NOT_SENT"=>				"nicht gesendet",
	"S_NO_ALERTS"=>				"Keine Events",
	"S_SHOW_NEXT_100"=>			"n&auml;chste 100 Events",
	"S_SHOW_PREVIOUS_100"=>			"vorherige 100 Events",

//	charts.php
	"S_CUSTOM_GRAPHS"=>			"Benutzerdefinierte Graphen",
	"S_GRAPHS_BIG"=>			"GRAPHEN",
	"S_NO_GRAPHS_TO_DISPLAY"=>		"Keine Graphen definiert",
	"S_SELECT_GRAPH_TO_DISPLAY"=>		"Graph anzeigen",
	"S_PERIOD"=>				"Zeitspanne",
	"S_1H"=>				"1h",
	"S_2H"=>				"2h",
	"S_4H"=>				"4h",
	"S_8H"=>				"8h",
	"S_12H"=>				"12h",
	"S_24H"=>				"24h",
	"S_WEEK_SMALL"=>			"Woche",
	"S_MONTH_SMALL"=>			"Monat",
	"S_YEAR_SMALL"=>			"Jahr",
	"S_KEEP_PERIOD"=>			"Zeitspanne beibehalten",
	"S_ON_C"=>				"an",
	"S_OFF_C"=>				"aus",
	"S_MOVE"=>				"verschieben",
	"S_SELECT_GRAPH_DOT_DOT_DOT"=>		"... Graph w&auml;hlen ...",

// Colors
	"S_BLACK"=>				"Schwarz",
	"S_BLUE"=>				"Blau",
	"S_CYAN"=>				"T&uuml;rkis",
	"S_DARK_BLUE"=>				"Dunkelblau",
	"S_DARK_GREEN"=>			"Dunkelgr&uuml;n",
	"S_DARK_RED"=>				"Dunkelrot",
	"S_DARK_YELLOW"=>			"Dunkelgelb",
	"S_GREEN"=>				"Gr&uuml;n",
	"S_RED"=>				"Rot",
	"S_WHITE"=>				"Wei&szlig;",
	"S_YELLOW"=>				"Gelb",

//	config.php
	"S_CONFIGURATION_OF_ZABBIX"=>		"Konfiguration von ZABBIX",
	"S_CONFIGURATION_OF_ZABBIX_BIG"=>	"KONFIGURATION VON ZABBIX",
	"S_CONFIGURATION_UPDATED"=>		"Konfiguration aktualisiert",
	"S_CONFIGURATION_WAS_NOT_UPDATED"=>	"Konfiguration nicht aktualisiert",
	"S_ADDED_NEW_MEDIA_TYPE"=>		"Neuen Medientyp hinzugef&uuml;gt",
	"S_NEW_MEDIA_TYPE_WAS_NOT_ADDED"=>	"Neuer Medientyp konnte nicht hinzugef&uuml;gt werden",
	"S_MEDIA_TYPE_UPDATED"=>		"Medientyp aktualisiert",
	"S_MEDIA_TYPE_WAS_NOT_UPDATED"=>	"Medientyp konnte nicht aktualisiert werden",
	"S_MEDIA_TYPE_DELETED"=>		"Medientyp gel&ouml;scht",
	"S_MEDIA_TYPE_WAS_NOT_DELETED"=>	"Medientyp konnte nicht gel&ouml;scht werden",
	"S_CONFIGURATION"=>			"Konfiguration",
	"S_DO_NOT_KEEP_ACTIONS_OLDER_THAN"=>	"Aktionen speichern f&uuml;r Zeitraum von (in Tagen)",
	"S_DO_NOT_KEEP_EVENTS_OLDER_THAN"=>	"Events speichern f&uuml;r Zeitraum von (in Tagen)",
	"S_MEDIA_TYPES_BIG"=>			"MEDIENTYPEN",
	"S_NO_MEDIA_TYPES_DEFINED"=>		"Keine Medientypen spezifiziert",
	"S_SMTP_SERVER"=>			"SMTP Server",
	"S_SMTP_HELO"=>				"SMTP helo Kommando",
	"S_SMTP_EMAIL"=>			"SMTP Absender",
	"S_SCRIPT_NAME"=>			"Scriptpfad",
	"S_DELETE_SELECTED_MEDIA"=>		"Ausgew&auml;hlte Medien wirklich l&ouml;schen?",
	"S_DELETE_SELECTED_IMAGE"=>		"Ausgew&auml;hlte Grafiken wirklich l&ouml;schen?",
	"S_HOUSEKEEPER"=>			"Datenhaltung",
	"S_MEDIA_TYPES"=>			"Medientypen",
	"S_ESCALATION_RULES"=>			"Eskalationsregeln",
	"S_ESCALATION"=>			"Eskalation",
	"S_ESCALATION_RULES_BIG"=>		"ESKALATIONSREGELN",
	"S_NO_ESCALATION_RULES_DEFINED"=>	"Keine Eskalationsregeln definiert",
	"S_NO_ESCALATION_DETAILS"=>		"Keine Eskalationsdetails",
	"S_ESCALATION_DETAILS_BIG"=>		"ESKALATIONSDETAILS",
	"S_ESCALATION_ADDED"=>			"Eskalation hinzugef&uuml;gt",
	"S_ESCALATION_WAS_NOT_ADDED"=>		"Eskalation konnte nicht hinzugef&uuml;gt werden",
	"S_ESCALATION_RULE_ADDED"=>		"Eskalationsregel hinzugef&uuml;gt",
	"S_ESCALATION_RULE_WAS_NOT_ADDED"=>	"Eskalationsregel konnte nicht hinzugef&uuml;gt werden",
	"S_ESCALATION_RULE_UPDATED"=>		"Eskalationsregel aktualisiert",
	"S_ESCALATION_RULE_WAS_NOT_UPDATED"=>	"Eskalationsregel konnte nicht aktualisiert werden",
	"S_ESCALATION_RULE_DELETED"=>		"Eskalationsregel gel&ouml;scht",
	"S_ESCALATION_RULE_WAS_NOT_DELETED"=>	"Eskalationsregel konnte nicht gel&ouml;scht werden",
	"S_ESCALATION_UPDATED"=>		"Eskalation aktualisiert",
	"S_ESCALATION_WAS_NOT_UPDATED"=>	"Eskalation konnte nicht aktualisiert werden",
	"S_ESCALATION_DELETED"=>		"Eskalation gel&ouml;scht",
	"S_ESCALATION_WAS_NOT_DELETED"=>	"Eskalation konnte nicht gel&ouml;scht werden",
	"S_ESCALATION_RULE"=>			"Eskalationsregel",
	"S_DO"=>				"dann",
	"S_DEFAULT"=>				"Standard",
	"S_IS_DEFAULT"=>			"ist Standard",
	"S_LEVEL"=>				"Stufe",
	"S_DELAY_BEFORE_ACTION"=>		"Verz&ouml;gerung",
	"S_IMAGES"=>				"Grafiken",
	"S_IMAGE"=>				"Grafik",
	"S_IMAGES_BIG"=>			"GRAFIKEN",
	"S_NO_IMAGES_DEFINED"=>			"Keine Grafiken hinterlegt",
	"S_BACKGROUND"=>			"Hintergrund",
	"S_UPLOAD"=>				"Upload",
	"S_IMAGE_ADDED"=>			"Grafik gespeichert",
	"S_CANNOT_ADD_IMAGE"=>			"Grafik konnte nicht gespeichert werden",
	"S_IMAGE_DELETED"=>			"Grafik gel&ouml;scht",
	"S_CANNOT_DELETE_IMAGE"=>		"Grafik konnte nicht gel&ouml;scht werden",
	"S_IMAGE_UPDATED"=>			"Grafik updated",
	"S_CANNOT_UPDATE_IMAGE"=>		"Grafik konnte nicht update",
	"S_UPDATE_SELECTED_IMAGE"=>		"Update selected image?",
	"S_AUTODISCOVERY"=>			"Automatische Auswahl",

//	Latest values
	"S_LATEST_VALUES"=>			"Aktueller Wert",
	"S_NO_PERMISSIONS"=>			"Keine Berechtigung !",
	"S_LATEST_DATA"=>			"AKTUELLE DATEN",
	"S_ALL_SMALL"=>				"alle",
	"S_DESCRIPTION_LARGE"=>			"BESCHREIBUNG",
	"S_DESCRIPTION_SMALL"=>			"Beschreibung",
	"S_GRAPH"=>				"Graph",
	"S_TREND"=>				"Tendenz",
	"S_COMPARE"=>				"vergleichen",

//	Footer
	"S_ZABBIX_VER"=>			"ZABBIX 1.1beta3",
	"S_COPYRIGHT_BY"=>			"Copyright 2001-2005 by ",
	"S_CONNECTED_AS"=>			"Verbunden als",
	"S_SIA_ZABBIX"=>			"SIA Zabbix",

//	graph.php
	"S_CONFIGURATION_OF_GRAPH"=>		"Einistellungen zu Graphen",
	"S_CONFIGURATION_OF_GRAPH_BIG"=>	"EINSTELLUNGEN VON GRAPH",
	"S_ITEM_ADDED"=>			"Element hinzugef&uuml;gt",
	"S_ITEM_UPDATED"=>			"Element aktualisiert",
	"S_SORT_ORDER_UPDATED"=>		"Sortierreihenfolge aktualisiert",
	"S_CANNOT_UPDATE_SORT_ORDER"=>		"Sortierreihenfolge konnte nicht aktualisiert werden",
	"S_DISPLAYED_PARAMETERS_BIG"=>		"ANZEIGEPARAMETER",
	"S_SORT_ORDER"=>			"Sortierreihenfolge",
	"S_PARAMETER"=>				"Parameter",
	"S_COLOR"=>				"Farbe",
	"S_UP"=>				"aufsteigend",
	"S_DOWN"=>				"absteigend",
	"S_NEW_ITEM_FOR_THE_GRAPH"=>		"Neues Element f&uuml;r Graph",
	"S_SORT_ORDER_1_100"=>			"Sortierreihenfolge (0->100)",

//	graphs.php
	"S_CONFIGURATION_OF_GRAPHS"=>		"Konfiguration von Graphen",
	"S_CONFIGURATION_OF_GRAPHS_BIG"=>	"KONFIGURATION VON GRAPHEN",
	"S_GRAPH_ADDED"=>			"Graph hinzugef&uuml;gt",
	"S_GRAPH_UPDATED"=>			"Graph aktualisiert",
	"S_CANNOT_UPDATE_GRAPH"=>		"Graph konnte nicht aktualisiert werden",
	"S_GRAPH_DELETED"=>			"Graph gel&ouml;scht",
	"S_CANNOT_DELETE_GRAPH"=>		"Graph konnte nicht gel&ouml;scht werden",
	"S_CANNOT_ADD_GRAPH"=>			"Graph konnte nicht hinzugef&uuml;gt werden",
	"S_ID"=>				"Id",
	"S_NO_GRAPHS_DEFINED"=>			"Keine Graphen definiert",
	"S_DELETE_GRAPH_Q"=>			"Soll dieser Graph wirklich gel&ouml;scht werden?",
	"S_YAXIS_TYPE"=>			"Y-Achsen Typ",
	"S_YAXIS_MIN_VALUE"=>			"Mindestwert Y-Achse",
	"S_YAXIS_MAX_VALUE"=>			"Maximalwert Y-Achse",
	"S_CALCULATED"=>			"automatisch",
	"S_FIXED"=>				"fest",

//	history.php
	"S_LAST_HOUR_GRAPH"=>			"Graph f&uuml;r die letzte Stunde",
	"S_LAST_HOUR_GRAPH_DIFF"=>		"Graph f&uuml;r die letzte Stunde (diff)",
	"S_VALUES_OF_LAST_HOUR"=>		"Werte der letzten Stunde",
	"S_VALUES_OF_SPECIFIED_PERIOD"=>	"Werte innerhalb des spezifizierten Zeitraumes",
	"S_VALUES_IN_PLAIN_TEXT_FORMAT"=>	"Werte im Textformat",
	"S_TIMESTAMP"=>				"Zeitstempel",

//	hosts.php
	"S_HOSTS"=>				"Systeme",
	"S_ITEMS"=>				"Elemente",
	"S_TRIGGERS"=>				"Ausl&ouml;ser",
	"S_GRAPHS"=>				"Graphen",
	"S_HOST_ADDED"=>			"System hinzugef&uuml;gt",
	"S_CANNOT_ADD_HOST"=>			"System konnte nicht hinzugef&uuml;gt werden",
	"S_ITEMS_ADDED"=>			"&Uuml;berwachungselemente hinzugef&uuml;gt",
	"S_CANNOT_ADD_ITEMS"=>			"&Uuml;berwachungselemente k&ouml;nnten nicht hinzugef&uuml;gt werden",
	"S_HOST_UPDATED"=>			"System aktualisiert",
	"S_CANNOT_UPDATE_HOST"=>		"System konnte nicht aktualisiert werden",
	"S_HOST_STATUS_UPDATED"=>		"Status des Systems aktualisiert",
	"S_CANNOT_UPDATE_HOST_STATUS"=>		"Status des Systems konnte nicht aktualisiert werden",
	"S_HOST_DELETED"=>			"System gel&ouml;scht",
	"S_CANNOT_DELETE_HOST"=>		"System konnte nicht gel&ouml;scht werden",
	"S_TEMPLATE_LINKAGE_ADDED"=>		"Verkn&uuml;pfung zur Vorlage hinzugef&uuml;gt",
	"S_CANNOT_ADD_TEMPLATE_LINKAGE"=>	"Verkn&uuml;pfung zum Vorlage konnte nicht hinzugef&uuml;gt werden",
	"S_TEMPLATE_LINKAGE_UPDATED"=>		"Verkn&uuml;pfung zum Vorlage aktualisiert",
	"S_CANNOT_UPDATE_TEMPLATE_LINKAGE"=>	"Verkn&uuml;pfung zum Vorlage konnte nicht aktualisiert werden",
	"S_TEMPLATE_LINKAGE_DELETED"=>		"Verkn&uuml;pfung zum Vorlage entfernt",
	"S_CANNOT_DELETE_TEMPLATE_LINKAGE"=>	"Verkn&uuml;pfung zum Vorlage konnte nicht entfernt werden",
	"S_CONFIGURATION_OF_HOSTS_AND_HOST_GROUPS"=>"KONFIGURATION VON SYSTEMEN UND SYSTEMGRUPPEN",
	"S_HOST_GROUPS_BIG"=>			"SYSTEMGRUPPEN",
	"S_NO_HOST_GROUPS_DEFINED"=>		"Keine Systemgruppen definiert",
	"S_NO_LINKAGES_DEFINED"=>		"Keine Verlinkungen hinterlegt",
	"S_NO_HOSTS_DEFINED"=>			"Keine Systeme definiert",
	"S_HOSTS_BIG"=>				"SYSTEME",
	"S_HOST"=>				"System",
	"S_IP"=>				"IP",
	"S_PORT"=>				"Port",
	"S_MONITORED"=>				"aktiv",
	"S_NOT_MONITORED"=>			"deaktiviert",
	"S_UNREACHABLE"=>			"unerreichbar",
	"S_TEMPLATE"=>				"Vorlage",
	"S_DELETED"=>				"gel&ouml;scht",
	"S_UNKNOWN"=>				"unbekannt",
	"S_GROUPS"=>				"Gruppen",
	"S_NEW_GROUP"=>				"Neue Gruppe",
	"S_USE_IP_ADDRESS"=>			"Benutze IP-Adresse",
	"S_IP_ADDRESS"=>			"IP-Adresse",
//	"S_USE_THE_HOST_AS_A_TEMPLATE"=>		"Dieses System als Vorlage definieren",
	"S_USE_TEMPLATES_OF_THIS_HOST"=>	"Vorlage",
	"S_DELETE_SELECTED_HOST_Q"=>		"Sollen die ausgew&auml;hlten Systeme wirklich gel&ouml;scht werden?",
	"S_GROUP_NAME"=>			"Gruppenname",
	"S_HOST_GROUP"=>			"Systemgruppe",
	"S_HOST_GROUPS"=>			"Systemgruppen",
	"S_UPDATE"=>				"Update",
	"S_AVAILABILITY"=>			"Verf&uuml;gbarkeit",
	"S_AVAILABLE"=>				"Ok",
	"S_NOT_AVAILABLE"=>			"fehlerhaft",

//	items.php
	"S_CONFIGURATION_OF_ITEMS"=>		"Konfiguration von Elementen",
	"S_CONFIGURATION_OF_ITEMS_BIG"=>	"KONFIGURATION VON ELEMENTEN",
	"S_CANNOT_UPDATE_ITEM"=>		"Element konnte nicht aktualisiert werden",
	"S_STATUS_UPDATED"=>			"Status aktualisiert",
	"S_CANNOT_UPDATE_STATUS"=>		"Status konnte nicht aktualisiert werden",
	"S_CANNOT_ADD_ITEM"=>			"Element konnte nicht hinzugef&uuml;gt werden",
	"S_ITEM_DELETED"=>			"Element gel&ouml;scht",
	"S_CANNOT_DELETE_ITEM"=>		"Element konnte nicht gel&ouml;scht werden",
	"S_ITEMS_DELETED"=>			"Elemente gel&ouml;scht",
	"S_CANNOT_DELETE_ITEMS"=>		"Elemente konnten nicht gel&ouml;scht werden",
	"S_ITEMS_ACTIVATED"=>			"Elemente aktiviert",
	"S_CANNOT_ACTIVATE_ITEMS"=>		"Elemente konnten nicht aktiviert werden",
	"S_ITEMS_DISABLED"=>			"Elemente deaktiviert",
	"S_SERVERNAME"=>			"Server Name",
	"S_KEY"=>				"Schl&uuml;ssel",
	"S_DESCRIPTION"=>			"Beschreibung",
	"S_UPDATE_INTERVAL"=>			"Update Interval",
	"S_HISTORY"=>				"Verlauf",
	"S_TRENDS"=>				"Tendenzen",
	"S_SHORT_NAME"=>			"Kurzer Name",
	"S_ZABBIX_AGENT"=>			"ZABBIX agent",
	"S_ZABBIX_AGENT_ACTIVE"=>		"ZABBIX agent (aktiv)",
	"S_SNMPV1_AGENT"=>			"SNMPv1 agent",
	"S_ZABBIX_TRAPPER"=>			"ZABBIX trapper",
	"S_SIMPLE_CHECK"=>			"Einfache Pr&uuml;fung",
	"S_SNMPV2_AGENT"=>			"SNMPv2 Agent",
	"S_SNMPV3_AGENT"=>			"SNMPv3 Agent",
	"S_ZABBIX_INTERNAL"=>			"ZABBIX intern",
	"S_ZABBIX_UNKNOWN"=>			"unbekannt",
	"S_ACTIVE"=>				"aktiviert",
	"S_NOT_ACTIVE"=>			"deaktiviert",
	"S_NOT_SUPPORTED"=>			"nicht unterst&uuml;tzt",
	"S_ACTIVATE_SELECTED_ITEMS_Q"=>		"Sollen die gew&auml;hlten Elemente aktiviert werden?",
	"S_DISABLE_SELECTED_ITEMS_Q"=>		"Sollen die gew&auml;hlten Elemente deaktiviert werden?",
	"S_DELETE_SELECTED_ITEMS_Q"=>		"Sollen die gew&auml;hlten Elemente gel&ouml;scht werden?",
	"S_EMAIL"=>				"eMail",
	"S_SCRIPT"=>				"Script",
	"S_UNITS"=>				"Einheit",
	"S_MULTIPLIER"=>			"Multiplikator",
	"S_UPDATE_INTERVAL_IN_SEC"=>		"Update Interval (in Sek.)",
	"S_KEEP_HISTORY_IN_DAYS"=>		"Verlauf speichern (in Tagen)",
	"S_KEEP_TRENDS_IN_DAYS"=>		"Tendenz speichern (in Tagen)",
	"S_TYPE_OF_INFORMATION"=>		"Wertetyp",
	"S_STORE_VALUE"=>			"Speichertyp",
	"S_NUMERIC"=>				"nummerisch",
	"S_CHARACTER"=>				"alphanummerisch",
	"S_LOG"=>				"Log",
	"S_AS_IS"=>				"unver&auml;ndert",
	"S_DELTA_SPEED_PER_SECOND"=>		"Delta (Geschwindigkeit pro Sekunde)",
	"S_DELTA_SIMPLE_CHANGE"=>		"Delta (Ver&auml;nderung)",
	"S_ITEM"=>				"Element",
	"S_SNMP_COMMUNITY"=>			"SNMP community",
	"S_SNMP_OID"=>				"SNMP OID",
	"S_SNMP_PORT"=>				"SNMP port",
	"S_ALLOWED_HOSTS"=>			"Berechtigte Systeme",
	"S_SNMPV3_SECURITY_NAME"=>		"SNMPv3 Sicherheitsname",
	"S_SNMPV3_SECURITY_LEVEL"=>		"SNMPv3 Sicherheitsstufe",
	"S_SNMPV3_AUTH_PASSPHRASE"=>		"SNMPv3 Authentisierungs-Passphrase",
	"S_SNMPV3_PRIV_PASSPHRASE"=>		"SNMPv3 Private Passphrase",
	"S_CUSTOM_MULTIPLIER"=>			"Benutzerdefinierter Multiplikator",
	"S_DO_NOT_USE"=>			"-",
	"S_USE_MULTIPLIER"=>			"Multiplikator",
	"S_SELECT_HOST_DOT_DOT_DOT"=>		"... System w&auml;hlen ...",

//	latestalarms.php
	"S_LATEST_EVENTS"=>			"Aktuelle Events",
	"S_HISTORY_OF_EVENTS_BIG"=>		"EVENTVERLAUF",

//	latest.php
	"S_LAST_CHECK"=>			"Letzte &Uuml;berpr&uuml;fung",
	"S_LAST_CHECK_BIG"=>			"LETZTE &Uuml;BERPR&Uuml;FUNG",
	"S_LAST_VALUE"=>			"Aktueller Wert",

//	sysmap.php
	"S_LABEL"=>				"Beschriftung",
	"S_X"=>					"X",
	"S_Y"=>					"Y",
	"S_ICON"=>				"Grafik",
	"S_HOST_1"=>				"System 1",
	"S_HOST_2"=>				"System 2",
	"S_LINK_STATUS_INDICATOR"=>		"Status Indikator",

//	map.php
	"S_OK_BIG"=>				"Ok",
	"S_PROBLEMS_SMALL"=>			"Fehler",
	"S_ZABBIX_URL"=>			"http://www.zabbix.com",

//	maps.php
	"S_NETWORK_MAPS"=>			"Netzwerkpl&auml;ne",
	"S_NETWORK_MAPS_BIG"=>			"NETZWERKPL&Auml;NE",
	"S_NO_MAPS_TO_DISPLAY"=>		"Keine Pl&auml;ne vorhanden",
	"S_SELECT_MAP_TO_DISPLAY"=>		"Plan ausw&auml;hlen",
	"S_SELECT_MAP_DOT_DOT_DOT"=>		"... Plan w&auml;hlen ...",
	"S_BACKGROUND_IMAGE"=>			"Hintergrundbild",
	"S_ICON_LABEL_TYPE"=>			"Beschriftungstyp",
	"S_HOST_LABEL"=>			"benutzerdefiniert",
	"S_HOST_NAME"=>				"System",
	"S_STATUS_ONLY"=>			"nur Status",
	"S_NOTHING"=>				" ",

//	media.php
	"S_MEDIA"=>				"Medium",
	"S_MEDIA_BIG"=>				"MEDIUM",
	"S_MEDIA_ACTIVATED"=>			"Medium aktiviert",
	"S_CANNOT_ACTIVATE_MEDIA"=>		"Medium konnte nicht aktiviert werden",
	"S_MEDIA_DISABLED"=>			"Medium deaktiviert",
	"S_CANNOT_DISABLE_MEDIA"=>		"Medium konnte nicht deaktiviert werden",
	"S_MEDIA_ADDED"=>			"Medium hinzugef&uuml;gt",
	"S_CANNOT_ADD_MEDIA"=>			"Medium konnte nicht hinzugef&uuml;gt werden",
	"S_MEDIA_UPDATED"=>			"Medium aktualisiert",
	"S_CANNOT_UPDATE_MEDIA"=>		"Medium konnte nicht aktualisiert werden",
	"S_MEDIA_DELETED"=>			"Medium entfernt",
	"S_CANNOT_DELETE_MEDIA"=>		"Medium konnte nicht entfernt werden",
	"S_SEND_TO"=>				"Empf&auml;nger",
	"S_WHEN_ACTIVE"=>			"G&uuml;ltigkeitszeitraum",
	"S_NO_MEDIA_DEFINED"=>			"Kein Medium definiert",
	"S_NEW_MEDIA"=>				"Neues Medium",
	"S_USE_IF_SEVERITY"=>			"Benutzen bei Gewichtung",
	"S_DELETE_SELECTED_MEDIA_Q"=>		"Dieses Medium wirklich entfernen?",

//	Menu
	"S_MENU_LATEST_VALUES"=>		"AKTUELLE WERTE",
	"S_MENU_TRIGGERS"=>			"AUSL&OUml;SER",
	"S_MENU_QUEUE"=>			"WARTESCHLANGE",
	"S_MENU_ALARMS"=>			"ALARME",
	"S_MENU_ALERTS"=>			"BENACHRICHTIGUNGEN",
	"S_MENU_NETWORK_MAPS"=>			"NETZWERKPL&Auml;NE",
	"S_MENU_GRAPHS"=>			"GRAPHEN",
	"S_MENU_SCREENS"=>			"&Uuml;BERSICHTSPL&Auml;NE",
	"S_MENU_IT_SERVICES"=>			"IT DIENSTE",
	"S_MENU_SERVERS"=>			"Zabbix Servers",
	"S_MENU_HOME"=>				"START",
	"S_MENU_ABOUT"=>			"&Uuml;BER",
	"S_MENU_STATUS_OF_ZABBIX"=>		"STATUS VON ZABBIX",
	"S_MENU_AVAILABILITY_REPORT"=>		"VERF&Uuml;GBARKEITSANALYSE",
	"S_MENU_CONFIG"=>			"KONFIGURATION",
	"S_MENU_USERS"=>			"BENUTZER",
	"S_MENU_HOSTS"=>			"SYSTEME",
	"S_MENU_ITEMS"=>			"ELEMENTE",
	"S_MENU_AUDIT"=>			"AKTIVIT&Auml;TEN",

//	overview.php
	"S_SELECT_GROUP_DOT_DOT_DOT"=>		"Gruppe w&auml;hlen ...",
	"S_OVERVIEW"=>				"&Uuml;bersicht",
	"S_OVERVIEW_BIG"=>			"&Uuml;BERSICHT",
	"S_EXCL"=>				"!",
	"S_DATA"=>				"Wert",

//	queue.php
	"S_QUEUE_BIG"=>				"WARTESCHLANGE",
	"S_QUEUE_OF_ITEMS_TO_BE_UPDATED_BIG"=>	"WARTESCHLANGE F&Uuml;R UPDATE VON ELEMENTEN",
	"S_NEXT_CHECK"=>			"N&auml;chster Lauf",
	"S_THE_QUEUE_IS_EMPTY"=>		"Die Warteschlange ist leer",
	"S_TOTAL"=>				"Summe",
	"S_COUNT"=>				"Gesamt",
	"S_5_SECONDS"=>				"05 Sekunden",
	"S_10_SECONDS"=>			"10 Sekunden",
	"S_30_SECONDS"=>			"30 Sekunden",
	"S_1_MINUTE"=>				"1 Minute",
	"S_5_MINUTES"=>				"5 Minuten",
	"S_MORE_THAN_5_MINUTES"=>		"> 5 Minuten",

//	report1.php
	"S_STATUS_OF_ZABBIX"=>			"Status von ZABBIX",
	"S_STATUS_OF_ZABBIX_BIG"=>		"STATUS VON ZABBIX",
	"S_VALUE"=>				"Wert",
	"S_ZABBIX_SERVER_IS_RUNNING"=>		"ZABBIX Daemon Prozess aktiv",
	"S_NUMBER_OF_VALUES_STORED"=>		"Anzahl der gespeicherten Werte",
	"S_NUMBER_OF_TRENDS_STORED"=>		"Anzahl der gespeicherten Tendenzen",
	"S_NUMBER_OF_ALARMS"=>			"Anzahl der Alarme",
	"S_NUMBER_OF_ALERTS"=>			"Anzahl der Benachrichtigungen",
	"S_NUMBER_OF_TRIGGERS_ENABLED_DISABLED"=>"Anzahl der Ausl&ouml;ser (aktiviert/deaktiviert)",
	"S_NUMBER_OF_ITEMS_ACTIVE_TRAPPER"=>	"Anzahl der Elemente (aktiviert/Empf&auml;nger/deaktiviert/nicht unterst&uuml;tzt)",
	"S_NUMBER_OF_USERS"=>			"Anzahl der Benutzer",
	"S_NUMBER_OF_HOSTS_MONITORED"=>		"Anzahl der Systeme (&uuml;berwacht/nicht &uuml;berwacht/Vorlagen/deleted)",
	"S_YES"=>				"Ja",
	"S_NO"=>				"Nein",

//	report2.php
	"S_AVAILABILITY_REPORT"=>		"Verf&uuml;gbarkeitsanalyse",
	"S_AVAILABILITY_REPORT_BIG"=>		"VERF&Uuml;GBARKEITSANALYSE",
	"S_SHOW"=>				"anzeigen",
	"S_TRUE"=>				"wahr",
	"S_FALSE"=>				"falsch",

//	report3.php
	"S_IT_SERVICES_AVAILABILITY_REPORT_BIG"=>	"DIENSTE VERF&Uuml;GBARKEITSANALYSE",
	"S_FROM"=>				"Von",
	"S_TILL"=>				"Bis",
	"S_OK"=>				"Ok",
	"S_PROBLEMS"=>				"Probleme",
	"S_PERCENTAGE"=>			"prozentual",
	"S_SLA"=>				"SLA",
	"S_DAY"=>				"Tag",
	"S_MONTH"=>				"Monat",
	"S_YEAR"=>				"Jahr",
	"S_DAILY"=>				"t&auml;glich",
	"S_WEEKLY"=>				"w&ouml;chentlich",
	"S_MONTHLY"=>				"monatlich",
	"S_YEARLY"=>				"j&auml;hrlich",

//	screenconf.php
	"S_SCREENS"=>				"&Uuml;bersichtstafeln",
	"S_SCREEN"=>				"&Uuml;bersichtstafel",
	"S_CONFIGURATION_OF_SCREENS_BIG"=>	"KONFIGURATION VON &Uuml;BERSICHTSTAFELN",
	"S_SCREEN_ADDED"=>			"&Uuml;berisichtstafel hinzugef&uuml;gt",
	"S_CANNOT_ADD_SCREEN"=>			"&Uuml;berisichtstafel konnte nicht hinzugef&uuml;gt werden",
	"S_SCREEN_UPDATED"=>			"&Uuml;berisichtstafel aktualisiert",
	"S_CANNOT_UPDATE_SCREEN"=>		"&Uuml;berisichtstafel konnte nicht aktualisiert werden",
	"S_SCREEN_DELETED"=>			"&Uuml;berisichtstafel gel&ouml;scht",
	"S_CANNOT_DELETE_SCREEN"=>		"&Uuml;berisichtstafel konnte nicht gel&ouml;scht werden",
	"S_COLUMNS"=>				"Spalten",
	"S_ROWS"=>				"Zeilen",
	"S_NO_SCREENS_DEFINED"=>		"Keine &Uuml;bersichtstafeln definiert",
	"S_DELETE_SCREEN_Q"=>			"Soll diese &Uuml;bersichtstafel wirklich gel&ouml;scht werden?",
	"S_CONFIGURATION_OF_SCREEN_BIG"=>	"EINSTELLUNGEN ZU &Uuml;BERSICHTTAFEL",
	"S_SCREEN_CELL_CONFIGURATION"=>		"Zelleneinstellung",
	"S_RESOURCE"=>				"Bereich",
	"S_SIMPLE_GRAPH"=>			"Einfacher Graph",
	"S_GRAPH_NAME"=>			"definiter Graph",
	"S_WIDTH"=>				"Weite",
	"S_HEIGHT"=>				"H&ouml;he",
	"S_EMPTY"=>				"leer",

//	screenedit.php
	"S_MAP"=>				"Plan",
	"S_PLAIN_TEXT"=>			"Text",
	"S_COLUMN_SPAN"=>			"Spaltenbreite",
	"S_ROW_SPAN"=>				"Zeilenh&ouml;he",

//	screens.php
	"S_CUSTOM_SCREENS"=>			"Benutzerdefinierte &Uuml;bersichtstafeln",
	"S_SCREENS_BIG"=>			"&Uuml;BERSICHTSTAFELN",
	"S_NO_SCREENS_TO_DISPLAY"=>		"Keine Tafeln vorhanden",
	"S_SELECT_SCREEN_TO_DISPLAY"=>		"Tafel f&uuml;r Anzeige",
	"S_SELECT_SCREEN_DOT_DOT_DOT"=>		"... w&auml;hle Tafel ...",

//	services.php
	"S_IT_SERVICES"=>			"IT Dienste",
	"S_SERVICE_UPDATED"=>			"Dienst aktualisiert",
	"S_CANNOT_UPDATE_SERVICE"=>		"Dienst konnte nicht aktualisiert werden",
	"S_SERVICE_ADDED"=>			"Dienst hinzugef&uuml;gt",
	"S_CANNOT_ADD_SERVICE"=>		"Dienst konnte nicht hinzugef&uuml;gt werden",
	"S_LINK_ADDED"=>			"Verkn&uuml;pfung hinzugef&uuml;gt",
	"S_CANNOT_ADD_LINK"=>			"Verkn&uuml;pfung konnte nicht hinzugef&uuml;gt werden",
	"S_SERVICE_DELETED"=>			"Dienst gel&ouml;scht",
	"S_CANNOT_DELETE_SERVICE"=>		"Dienst konnte nicht gel&ouml;scht werden",
	"S_LINK_DELETED"=>			"Verkn&uuml;pfung gel&ouml;scht",
	"S_CANNOT_DELETE_LINK"=>		"Verkn&uuml;pfung konnte nicht gel&ouml;scht werden",
	"S_STATUS_CALCULATION"=>		"Statusberechnung",
	"S_STATUS_CALCULATION_ALGORITHM"=>	"Berechnungsalgorithmus",
	"S_NONE"=>				"keine",
	"S_MAX_OF_CHILDS"=>			"MAX der Abh&auml;ngigkeiten",
	"S_MIN_OF_CHILDS"=>			"MIN der Abh&auml;ngigkeiten",
	"S_SERVICE_1"=>				"Dienst 1",
	"S_SERVICE_2"=>				"Dienst 2",
	"S_SOFT_HARD_LINK"=>			"Verkn&uuml;pfungstyp",
	"S_SOFT"=>				"variabel",
	"S_HARD"=>				"fest",
	"S_DO_NOT_CALCULATE"=>			"-",
	"S_MAX_BIG"=>				"MAX",
	"S_MIN_BIG"=>				"MIN",
	"S_SHOW_SLA"=>				"Zeige SLA",
	"S_ACCEPTABLE_SLA_IN_PERCENT"=>		"SLA Toleranzwert (in Prozent)",
	"S_LINK_TO_TRIGGER_Q"=>			"Verkn&uuml;pfe Ausl&ouml;ser",
	"S_SORT_ORDER_0_999"=>			"Sortierreihenfolge (0->999)",
	"S_DELETE_SERVICE_Q"=>			"Soll der Dienst wirklich gel&ouml;scht werden?",
	"S_LINK_TO"=>				"Verkn&uuml;pfen mit",
	"S_SOFT_LINK_Q"=>			"Variable Verkn&uuml;pfung",
	"S_ADD_SERVER_DETAILS"=>		"Details hinzuf&uuml;gen",
	"S_TRIGGER"=>				"Ausl&ouml;ser",
	"S_SERVER"=>				"System",
	"S_DELETE"=>				"entfernen",

//	srv_status.php
	"S_IT_SERVICES_BIG"=>			"IT DIENSTE",
	"S_SERVICE"=>				"Dienst",
	"S_REASON"=>				"Grund",
	"S_SLA_LAST_7_DAYS"=>			"SLA (letzte 7 Tage)",
	"S_PLANNED_CURRENT_SLA"=>		"geplanter/aktueller SLA",
	"S_TRIGGER_BIG"=>			"AUSL&Ouml;SER",

//	triggers.php
	"S_CONFIGURATION_OF_TRIGGERS"=>		"Konfiguration von Ausl&ouml;sern",
	"S_CONFIGURATION_OF_TRIGGERS_BIG"=>	"KONFIGURATION VON Ausl&ouml;sern",
	"S_DEPENDENCY_ADDED"=>			"Abh&auml;ngigkeit hinzugef&uuml;gt",
	"S_CANNOT_ADD_DEPENDENCY"=>		"Abh&auml;ngigkeit konnte nicht hinzugef&uuml;gt werden",
	"S_TRIGGERS_UPDATED"=>			"Ausl&ouml;ser aktualisiert",
	"S_CANNOT_UPDATE_TRIGGERS"=>		"Ausl&ouml;ser konnte nicht aktualisiert werden",
	"S_TRIGGERS_DISABLED"=>			"Ausl&ouml;ser deaktiviert",
	"S_CANNOT_DISABLE_TRIGGERS"=>		"Ausl&ouml;ser konnte nicht deaktiviert werden",
	"S_TRIGGERS_DELETED"=>			"Ausl&ouml;ser wurden gel&ouml;scht",
	"S_CANNOT_DELETE_TRIGGERS"=>		"Ausl&ouml;ser konnten nicht gel&ouml;scht werden",
	"S_TRIGGER_DELETED"=>			"Ausl&ouml;ser wurde gel&ouml;scht",
	"S_CANNOT_DELETE_TRIGGER"=>		"Ausl&ouml;ser konnte nicht gel&ouml;scht werden",
	"S_INVALID_TRIGGER_EXPRESSION"=>	"Ung&uuml;ltiger Ausdruck f&uuml;r Ausl&ouml;ser",
	"S_TRIGGER_ADDED"=>			"Ausl&ouml;ser hinzugef&uuml;gt",
	"S_CANNOT_ADD_TRIGGER"=>		"Ausl&ouml;ser konnte nicht hinzugef&uuml;gt werden",
	"S_SEVERITY"=>				"Gewichtigkeit",
	"S_EXPRESSION"=>			"Ausdruck",
	"S_DISABLED"=>				"deaktiviert",
	"S_ENABLED"=>				"aktiviert",
	"S_ENABLE_SELECTED_TRIGGERS_Q"=>	"Ausgew&auml;hlte Ausl&ouml;ser aktivieren?",
	"S_DISABLE_SELECTED_TRIGGERS_Q"=>	"Ausgew&auml;hlte Ausl&ouml;ser deaktivieren?",
	"S_CHANGE"=>				"&auml;ndern",
	"S_TRIGGER_UPDATED"=>			"Ausl&ouml;ser aktualisiert",
	"S_CANNOT_UPDATE_TRIGGER"=>		"Ausl&ouml;ser konnte nicht aktualisiert werden",
	"S_DEPENDS_ON"=>			"Verkn&uuml;pfungen",

//	tr_comments.php
	"S_TRIGGER_COMMENTS"=>			"Ausl&ouml;ser Kommentare",
	"S_TRIGGER_COMMENTS_BIG"=>		"AUSL&Ouml;ser KOMMENTARE",
	"S_COMMENT_UPDATED"=>			"Kommentar aktualisiert",
	"S_CANNOT_UPDATE_COMMENT"=>		"Kommentar konnte nicht aktualisiert werden",
	"S_ADD"=>				"hinzuf&uuml;gen",

//	tr_status.php
	"S_STATUS_OF_TRIGGERS"=>		"Status des Ausl&ouml;sers",
	"S_STATUS_OF_TRIGGERS_BIG"=>		"STATUS DES AUSL&Ouml;SERS",
	"S_SHOW_ONLY_TRUE"=>			"aktive Ausl&ouml;ser anzeigen",
	"S_HIDE_ACTIONS"=>			"Aktionen ausblenden",
	"S_SHOW_ACTIONS"=>			"Aktionen einblenden",
	"S_SHOW_ALL_TRIGGERS"=>			"alle Ausl&ouml;ser anzeigen",
	"S_HIDE_DETAILS"=>			"Details ausblenden",
	"S_SHOW_DETAILS"=>			"Details einblenden",
	"S_SELECT"=>				"Auswahl definieren",
	"S_HIDE_SELECT"=>			"Auswahldefinition ausblenden",
	"S_TRIGGERS_BIG"=>			"AUSL&Ouml;SER",
	"S_DESCRIPTION_BIG"=>			"BESCHREIBUNG",
	"S_SEVERITY_BIG"=>			"GEWICHTIGKEIT",
	"S_LAST_CHANGE_BIG"=>			"LETZTE &Auml;NDERUNG",
	"S_LAST_CHANGE"=>			"Letzte &Auml;nderung",
	"S_COMMENTS"=>				"Kommentar",

//	users.php
	"S_USERS"=>				"Benutzer",
	"S_USER_ADDED"=>			"Benutzer hinzugef&uuml;gt",
	"S_CANNOT_ADD_USER"=>			"Benutzer konnte nicht hinzugef&uuml;gt werden",
	"S_CANNOT_ADD_USER_BOTH_PASSWORDS_MUST"=>"Benutzer konnte nicht hinzugef&uuml;gt werden, da die angegebenen Kennw&ouml;rter nicht &uuml;berein stimmen.",
	"S_USER_DELETED"=>			"Benutzer gel&ouml;scht",
	"S_CANNOT_DELETE_USER"=>		"Benutzer konnte nicht gel&ouml;scht werden",
	"S_PERMISSION_DELETED"=>		"Zugriff wurde entfernt",
	"S_CANNOT_DELETE_PERMISSION"=>		"Zugriff konnte nicht entfernt werden",
	"S_PERMISSION_ADDED"=>			"Zugriff wurde hinzugef&uuml;gt",
	"S_CANNOT_ADD_PERMISSION"=>		"Zugriff konnte nicht hinzugef&uuml;gt werden",
	"S_USER_UPDATED"=>			"Benutzer aktualisiert",
	"S_CANNOT_UPDATE_USER"=>		"Benutzer konnte nicht aktualisiert werden",
	"S_CANNOT_UPDATE_USER_BOTH_PASSWORDS"=>	"Benutzer konnte nicht aktualisiert werden, da die angegebenen Kennw&ouml;rter nicht &uuml;berein stimmen.",
	"S_GROUP_ADDED"=>			"Gruppe hinzugef&uuml;gt",
	"S_CANNOT_ADD_GROUP"=>			"Gruppe konnte nicht hinzugef&uuml;gt werden",
	"S_GROUP_UPDATED"=>			"Gruppe aktualisiert",
	"S_CANNOT_UPDATE_GROUP"=>		"Gruppe konnte nicht aktualisiert werden",
	"S_GROUP_DELETED"=>			"Gruppe gel&ouml;scht",
	"S_CANNOT_DELETE_GROUP"=>		"Gruppe konnte nicht gel&ouml;scht werden",
	"S_CONFIGURATION_OF_USERS_AND_USER_GROUPS"=>"KONFIGURATION VON BENUTZERN UND GRUPPEN",
	"S_USER_GROUPS_BIG"=>			"BENUTZERGRUPPEN",
	"S_USERS_BIG"=>				"BENUTZER",
	"S_USER_GROUPS"=>			"Benutzergruppen",
	"S_MEMBERS"=>				"Mitglieder",
	"S_TEMPLATES"=>				"Vorlagen",
	"S_HOSTS_TEMPLATES_LINKAGE"=>		"System/Vorlagen Verkn&uuml;pfungen",
	"S_CONFIGURATION_OF_TEMPLATES_LINKAGE"=>"KONFIGURATION VON VORLAGENVERKN&Uuml;PFUNGEN",
	"S_LINKED_TEMPLATES_BIG"=>		"VERKN&Uuml;PFTE VORLAGEN",
	"S_NO_USER_GROUPS_DEFINED"=>		"Keine Benutzergruppen definiert",
	"S_ALIAS"=>				"Alias",
	"S_NAME"=>				"Name",
	"S_SURNAME"=>				"Vorname",
	"S_IS_ONLINE_Q"=>			"Online",
	"S_NO_USERS_DEFINED"=>			"Keine Benutzer definiert",
	"S_PERMISSION"=>			"Berechtigung",
	"S_RIGHT"=>				"Recht",
	"S_RESOURCE_NAME"=>			"Bereich",
	"S_READ_ONLY"=>				"nur Lesen",
	"S_READ_WRITE"=>			"Lesen/Schreiben",
	"S_HIDE"=>				"ausblenden",
	"S_PASSWORD"=>				"Kennwort",
	"S_PASSWORD_ONCE_AGAIN"=>		"Kennwort (Wiederholung)",
	"S_URL_AFTER_LOGIN"=>			"URL-Umleitung nach Login",
	"S_AUTO_LOGOUT_IN_SEC"=>		"Inaktivit&auml;tslimit in Sekunden (0 = deaktiviert)",
	"S_SCREEN_REFRESH"=>			"Refresh (in seconds)",

//	audit.php
	"S_AUDIT_LOG"=>				"Aktivit&auml;tsprotokoll",
	"S_AUDIT_LOG_BIG"=>			"AKTIVIT&Auml;TSPROTOKOLL",
	"S_ACTION"=>				"Aktion",
	"S_DETAILS"=>				"Details",
	"S_UNKNOWN_ACTION"=>			"unbekannte Aktion",
	"S_ADDED"=>				"hinzugef&uuml;gt",
	"S_UPDATED"=>				"aktualisiert",
	"S_LOGGED_IN"=>				"login",
	"S_LOGGED_OUT"=>			"logout",
	"S_MEDIA_TYPE"=>			"Medientyp",
	"S_GRAPH_ELEMENT"=>			"Graph element",

//	profile.php
	"S_USER_PROFILE_BIG"=>			"BENUTZERPROFIL",
	"S_USER_PROFILE"=>			"Benutzerprofil",
	"S_LANGUAGE"=>				"Sprache",
	"S_ENGLISH_GB"=>			"Englisch (GB)",
	"S_FRENCH_FR"=>				"Franz&ouml;sisch (FR)",
	"S_GERMAN_DE"=>				"Deutsch (DE)",
	"S_LATVIAN_LV"=>			"L&auml;ttisch (LV)",
	"S_RUSSIAN_RU"=>			"Russisch (RU)",

//	index.php
	"S_ZABBIX_BIG"=>			"ZABBIX",

//	Menu

	"S_HELP"=>				"Hilfe",
	"S_PROFILE"=>				"Mein Profil",
	);

	$TRANSLATION=array_merge($TRANSLATION,$de_de);
?>
