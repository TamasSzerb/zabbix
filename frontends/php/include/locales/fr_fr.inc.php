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
?>
<?php
	global $TRANSLATION;

	$TRANSLATION=array(

	"S_DATE_FORMAT_YMDHMS"=>		"d M H:i:s",
	"S_DATE_FORMAT_YMD"=>			"d M Y",
	"S_HTML_CHARSET"=>			    "iso-8859-1",

//	about.php
	"S_ABOUT_ZABBIX"=>			        "A propos de ZABBIX",
	"S_INFORMATION_ABOUT_ZABBIX"=>		"Informations � propos de ZABBIX (v1.1beta4)",
	"S_HOMEPAGE_OF_ZABBIX"=>			"Page d'accueil de ZABBIX",
	"S_HOMEPAGE_OF_ZABBIX_DETAILS"=>	"Ceci est la page d'accueil de ZABBIX.",
	"S_LATEST_ZABBIX_MANUAL"=>			"Manuel de ZABBIX le plus r�cent",
	"S_LATEST_ZABBIX_MANUAL_DETAILS"=>	"Version la plus r�cente du guide.",
	"S_DOWNLOADS"=>						"T�l�chargements",
	"S_DOWNLOADS_DETAILS"=>				"La version la plus r�cente de ZABBIX est disponible ici.",
	"S_FEATURE_REQUESTS"=>				"Demandes de fonctionnalit�s",
	"S_FEATURE_REQUESTS_DETAILS"=>		"Si vous avez besoin de fonctionnalit�s suppl�mentaires=> cliquez ici.",
	"S_FORUMS"=>						"Forums",
	"S_FORUMS_DETAILS"=>				"Discussion � propos de ZABBIX.",
	"S_BUG_REPORTS"=>					"Rapports de probl�mes",
	"S_BUG_REPORTS_DETAILS"=>			"Un probl�me dans ZABBIX? Svp => rapportez-le.",
	"S_MAILING_LISTS"=>					"Listes de distribution",
	"S_MAILING_LISTS_DETAILS"=>			"Listes de distribution sur ZABBIX.",

//	actions.php
	"S_ACTIONS"=>						"Actions",
	"S_ACTION_ADDED"=>					"Action ajout�e",
	"S_CANNOT_ADD_ACTION"=>				"Impossible d'ajouter l'action",
	"S_ACTION_UPDATED"=>				"Action mise � jour",
	"S_CANNOT_UPDATE_ACTION"=>			"Impossible de mettre � jour l'action",
	"S_ACTION_DELETED"=>				"Action supprim�e",
	"S_CANNOT_DELETE_ACTION"=>			"Impossible de supprimer l'action",
	"S_SCOPE"=>							"�tendue",
	"S_SEND_MESSAGE_TO"=>				"Envoyer le message �",
	"S_WHEN_TRIGGER"=>					"Lorsque d�clencheur",
	"S_DELAY"=>							"D�lai",
	"S_SUBJECT"=>						"Sujet",
	"S_ON"=>							"OUVERT",
	"S_OFF"=>							"FERM�",
	"S_NO_ACTIONS_DEFINED"=>			"Aucune action de d�finie",
	"S_NEW_ACTION"=>					"Nouvelle action",
	"S_SINGLE_USER"=>					"Simple utilisateur",
	"S_USER_GROUP"=>					"Groupe d'utilisateurs",
	"S_GROUP"=>							"Groupe",
	"S_USER"=>							"Utilisateur",
	"S_WHEN_TRIGGER_BECOMES"=>			"Lorsque le d�clencheur devient",
	"S_ON_OR_OFF"=>						"OUVERT ou FERM�",
	"S_DELAY_BETWEEN_MESSAGES_IN_SEC"=>	"D�lai entre messages (en sec)",
	"S_MESSAGE"=>						"Message",
	"S_THIS_TRIGGER_ONLY"=>				"Ce d�clencheur seulement",
	"S_ALL_TRIGGERS_OF_THIS_HOST"=>		"Tous les d�clencheurs pour cet h�te",
	"S_ALL_TRIGGERS"=>					"Tous les d�clencheurs",
	"S_USE_IF_TRIGGER_SEVERITY"=>		"Utiliser si s�v�rit� du d�clencheur est sup�rieur ou �gal �",
	"S_NOT_CLASSIFIED"=>				"Non class�",
	"S_INFORMATION"=>					"Information",
	"S_WARNING"=>						"Avertissement",
	"S_AVERAGE"=>						"Moyen",
	"S_HIGH"=>							"Haut",
	"S_DISASTER"=>						"D�sastre",

//	alarms.php
	"S_ALARMS"=>						"Alertes",
	"S_ALARMS_SMALL"=>					"Alertes",
	"S_ALARMS_BIG"=>					"ALERTES",
	"S_SHOW_ONLY_LAST_100"=>			"Afficher seulement les 100 derniers",
	"S_SHOW_ALL"=>						"Afficher tout",
	"S_TIME"=>							"Heure",
	"S_STATUS"=>						"Statut",
	"S_DURATION"=>						"Dur�e",
	"S_SUM"=>							"Somme",
	"S_TRUE_BIG"=>						"VRAI",
	"S_FALSE_BIG"=>						"FAUX",
	"S_DISABLED_BIG"=>					"D�SACTIV�",
	"S_UNKNOWN_BIG"=>					"INCONNU",

//	alerts.php
	"S_ALERT_HISTORY_SMALL"=>			"Historique des alertes",
	"S_ALERT_HISTORY_BIG"=>				"HISTORIQUE DES ALERTES",
	"S_ALERTS_BIG"=>					"ALERTES",
	"S_TYPE"=>							"Type",
	"S_RECIPIENTS"=>					"Destinataire(s)",
	"S_ERROR"=>							"Erreur",
	"S_SENT"=>							"envoy�",
	"S_NOT_SENT"=>						"pas envoy�",
	"S_NO_ALERTS"=>						"Aucunes alertes",
	"S_SHOW_NEXT_100"=>					"Afficher les 100 prochains",
	"S_SHOW_PREVIOUS_100"=>				"Afficher les 100 pr�c�dents",

//	charts.php
	"S_CUSTOM_GRAPHS"=>					"Graphiques personnalis�s",
	"S_GRAPHS_BIG"=>					"GRAPHIQUES",
	"S_NO_GRAPHS_TO_DISPLAY"=>			"Aucun graphique n'est affichable",
	"S_SELECT_GRAPH_TO_DISPLAY"=>		"S�lectionnez le graphique � afficher",
	"S_PERIOD"=>						"P�riode",
	"S_1H"=>							"1h",
	"S_2H"=>							"2h",
	"S_4H"=>							"4h",
	"S_8H"=>							"8h",
	"S_12H"=>							"12h",
	"S_24H"=>							"24h",
	"S_WEEK_SMALL"=>					"semaine",
	"S_MONTH_SMALL"=>					"mois",
	"S_YEAR_SMALL"=>					"ann�e",
	"S_KEEP_PERIOD"=>					"Conserver la p�riode",
	"S_ON_C"=>							"Ouvert",
	"S_OFF_C"=>							"Ferm�",
	"S_MOVE"=>							"D�placer",
	"S_SELECT_GRAPH_DOT_DOT_DOT"=>		"S�lectionnez le graphique...",

// Colors
	"S_BLACK"=>							"Noir",
	"S_BLUE"=>							"Bleu",
	"S_CYAN"=>							"Cyan",
	"S_DARK_BLUE"=>						"Bleu fonc�",
	"S_DARK_GREEN"=>					"Vert fonc�",
	"S_DARK_RED"=>						"Rouge fonc�",
	"S_DARK_YELLOW"=>					"Jaune fonc�",
	"S_GREEN"=>							"Vert",
	"S_RED"=>							"Rouge",
	"S_WHITE"=>							"Blanc",
	"S_YELLOW"=>						"Jaune",

//	config.php
	"S_CONFIGURATION_OF_ZABBIX"=>		"Configuration de ZABBIX",
	"S_CONFIGURATION_OF_ZABBIX_BIG"=>	"CONFIGURATION DE ZABBIX",
	"S_CONFIGURATION_UPDATED"=>			"Configuration actualis�e",
	"S_CONFIGURATION_WAS_NOT_UPDATED"=>	"Configuration pas actualis�e",
	"S_ADDED_NEW_MEDIA_TYPE"=>			"Nouveau type de m�dia ajout�",
	"S_NEW_MEDIA_TYPE_WAS_NOT_ADDED"=>	"Nouveau type de m�dia pas ajout�",
	"S_MEDIA_TYPE_UPDATED"=>			"Type de m�dia actualis�e",
	"S_MEDIA_TYPE_WAS_NOT_UPDATED"=>	"Type m�dia n'a pas �t� actualis�",
	"S_MEDIA_TYPE_DELETED"=>			"Type m�dia supprim�",
	"S_MEDIA_TYPE_WAS_NOT_DELETED"=>	"Type m�dia n'a pas �t� supprim�",
	"S_CONFIGURATION"=>					"Configuration",
	"S_DO_NOT_KEEP_ACTIONS_OLDER_THAN"=> "Ne pas conserver actions plus anciennes que (en jours)",
	"S_DO_NOT_KEEP_EVENTS_OLDER_THAN"=>	 "Ne pas conserver �v�nements plus anciens que (en jours)",
	"S_MEDIA_TYPES_BIG"=>				"TYPES MEDIA ",
	"S_NO_MEDIA_TYPES_DEFINED"=>		"Aucun type m�dia d�fini",
	"S_SMTP_SERVER"=>					"serveur SMTP",
	"S_SMTP_HELO"=>						"SMTP HELO",
	"S_SMTP_EMAIL"=>					"Courriel SMTP",
	"S_SCRIPT_NAME"=>					"Nom script",
	"S_DELETE_SELECTED_MEDIA"=>			"Supprimer m�dia s�lectionn�?",
	"S_DELETE_SELECTED_IMAGE"=>			"Supprimer image s�lectionn�e?",
	"S_HOUSEKEEPER"=>					"M�nag�re",
	"S_MEDIA_TYPES"=>					"Types m�dia",
	"S_ESCALATION_RULES"=>				"R�gles d'escalade",
	"S_ESCALATION"=>					"Escalade",
	"S_ESCALATION_RULES_BIG"=>			"R�GLES D'ESCALADE",
	"S_NO_ESCALATION_RULES_DEFINED"=>	"Aucune r�gle d'escalade d�finie",
	"S_NO_ESCALATION_DETAILS"=>			"Pas de d�tails pour escalade",
	"S_ESCALATION_DETAILS_BIG"=>		"D�TAILS ESCALADE",
	"S_ESCALATION_ADDED"=>				"Escalade ajout�e",
	"S_ESCALATION_WAS_NOT_ADDED"=>		"Impossible d'ajouter l'escalade",
	"S_ESCALATION_RULE_ADDED"=>			"R�gle d'escalade ajout�e",
	"S_ESCALATION_RULE_WAS_NOT_ADDED"=>	"R�gle d'escalade pas ajout�e",
	"S_ESCALATION_RULE_UPDATED"=>		"R�gle d'escalade actualis�e",
	"S_ESCALATION_RULE_WAS_NOT_UPDATED"=>	"R�gle d'escalade pas actualis�e",
	"S_ESCALATION_RULE_DELETED"=>		"R�gle d'escalade supprim�e",
	"S_ESCALATION_RULE_WAS_NOT_DELETED"=>	"R�gle d'escalade pas supprim�e",
	"S_ESCALATION_UPDATED"=>			"Escalade actualis�e",
	"S_ESCALATION_WAS_NOT_UPDATED"=>	"Escalade pas actualis�e",
	"S_ESCALATION_DELETED"=>			"Escalade supprim�e",
	"S_ESCALATION_WAS_NOT_DELETED"=>	"Escalade pas supprim�e",
	"S_ESCALATION_RULE"=>				"R�gle d'escalade",
	"S_DO"=>							"Faire",
	"S_DEFAULT"=>						"D�faut",
	"S_IS_DEFAULT"=>					"Est d�faut",
	"S_LEVEL"=>							"Niveau",
	"S_DELAY_BEFORE_ACTION"=>			"D�lai avant action",
	"S_IMAGES"=>						"Images",
	"S_IMAGE"=>							"Image",
	"S_IMAGES_BIG"=>					"IMAGES",
	"S_NO_IMAGES_DEFINED"=>				"Aucune image d�finie",
	"S_BACKGROUND"=>					"Fond",
	"S_UPLOAD"=>						"T�l�charger",
	"S_IMAGE_ADDED"=>					"Image ajout�e",
	"S_CANNOT_ADD_IMAGE"=>				"Impossible d'ajouter l'image",
	"S_IMAGE_DELETED"=>					"Image supprim�e",
	"S_CANNOT_DELETE_IMAGE"=>			"Impossible de supprimer l'image",
	"S_IMAGE_UPDATED"=>			"Image updated",
	"S_CANNOT_UPDATE_IMAGE"=>		"Cannot update image",
	"S_UPDATE_SELECTED_IMAGE"=>		"Update selected image?",
	"S_AUTODISCOVERY"=>					"Autod�couverte",

//	Latest values
	"S_LATEST_VALUES"=>					"Derni�res donn�es",
	"S_NO_PERMISSIONS"=>				"Pas de permissions !",
	"S_LATEST_DATA"=>					"DERNI�RES DONN�ES",
	"S_ALL_SMALL"=>						"tout",
	"S_DESCRIPTION_LARGE"=>				"DESCRIPTION",
	"S_DESCRIPTION_SMALL"=>				"Description",
	"S_GRAPH"=>							"Graphique",
	"S_TREND"=>							"Tendance",
	"S_COMPARE"=>						"Compare",

//	Footer
	"S_ZABBIX_VER"=>					"ZABBIX 1.1beta4",
	"S_COPYRIGHT_BY"=>					"Copyright 2001-2005 par ",
	"S_CONNECTED_AS"=>					"Connect� au nom de",
	"S_SIA_ZABBIX"=>					"SIA Zabbix",

//	graph.php
	"S_CONFIGURATION_OF_GRAPH"=>		"Configuration of graphique",
	"S_CONFIGURATION_OF_GRAPH_BIG"=>	"CONFIGURATION DU GRAPHIQUE",
	"S_ITEM_ADDED"=>					"Item ajout�",
	"S_ITEM_UPDATED"=>					"Item actualis�",
	"S_SORT_ORDER_UPDATED"=>			"Ordre du tri actualis�",
	"S_CANNOT_UPDATE_SORT_ORDER"=>		"Impossible d'actualiser ordre du tri",
	"S_DISPLAYED_PARAMETERS_BIG"=>		"PARAM�TRES AFFICH�S",
	"S_SORT_ORDER"=>					"Ordre de tri",
	"S_PARAMETER"=>						"Param�tre",
	"S_COLOR"=>							"Couleur",
	"S_UP"=>							"Haut",
	"S_DOWN"=>							"Bas",
	"S_NEW_ITEM_FOR_THE_GRAPH"=>		"Nouvel item pour le graphique",
	"S_SORT_ORDER_1_100"=>				"Ordre du tri (0->100)",

//	graphs.php
	"S_CONFIGURATION_OF_GRAPHS"=>		"Configuration des graphiques",
	"S_CONFIGURATION_OF_GRAPHS_BIG"=>	"CONFIGURATION DES GRAPHIQUES",
	"S_GRAPH_ADDED"=>					"Graphique ajout�",
	"S_GRAPH_UPDATED"=>					"Graphique actualis�",
	"S_CANNOT_UPDATE_GRAPH"=>			"Impossible d'actualiser le graphique",
	"S_GRAPH_DELETED"=>					"Graphique supprim�",
	"S_CANNOT_DELETE_GRAPH"=>			"Impossible de supprimer graphique",
	"S_CANNOT_ADD_GRAPH"=>				"Impossible d'ajouter graphique",
	"S_ID"=>							"Id",
	"S_NO_GRAPHS_DEFINED"=>				"Aucun graphique d�fini",
	"S_DELETE_GRAPH_Q"=>				"Supprimer graphique?",
	"S_YAXIS_TYPE"=>					"Type axe Y",
	"S_YAXIS_MIN_VALUE"=>				"Valeur MIN axe Y",
	"S_YAXIS_MAX_VALUE"=>				"Valeur MAX axe Y",
	"S_CALCULATED"=>					"Calcul�",
	"S_FIXED"=>							"Fixe",

//	history.php
	"S_LAST_HOUR_GRAPH"=>				"Graphique derni�re heure",
	"S_LAST_HOUR_GRAPH_DIFF"=>			"Graphique derni�re heure (delta)",
	"S_VALUES_OF_LAST_HOUR"=>			"Valeurs derni�re heure",
	"S_VALUES_OF_SPECIFIED_PERIOD"=>	"Valeurs p�riode sp�cifique",
	"S_VALUES_IN_PLAIN_TEXT_FORMAT"=>	"Valeurs en format texte brut",
	"S_TIMESTAMP"=>						"Horodateur",

//	hosts.php
	"S_HOSTS"=>								"H�tes",
	"S_ITEMS"=>								"Items",
	"S_TRIGGERS"=>							"D�clencheurs",
	"S_GRAPHS"=>							"Graphiques",
	"S_HOST_ADDED"=>						"H�te ajout�",
	"S_CANNOT_ADD_HOST"=>					"Impossible d'ajouter l'h�te",
	"S_ITEMS_ADDED"=>						"Items ajout�s",
	"S_CANNOT_ADD_ITEMS"=>					"Impossible d'ajouter items",
	"S_HOST_UPDATED"=>						"H�te actualis�",
	"S_CANNOT_UPDATE_HOST"=>				"Impossible d'actualiser l'h�te",
	"S_HOST_STATUS_UPDATED"=>				"Statut h�te actualis�",
	"S_CANNOT_UPDATE_HOST_STATUS"=>			"Impossible d'actualiser statut h�te",
	"S_HOST_DELETED"=>						"H�te supprim�",
	"S_CANNOT_DELETE_HOST"=>				"Impossible de supprimer h�te",
	"S_TEMPLATE_LINKAGE_ADDED"=>			"Lien avec mod�le ajout�",
	"S_CANNOT_ADD_TEMPLATE_LINKAGE"=>		"Impossible d'ajouter lien avec mod�le",
	"S_TEMPLATE_LINKAGE_UPDATED"=>			"Lien avec mod�le actualis�",
	"S_CANNOT_UPDATE_TEMPLATE_LINKAGE"=>	"Impossible d'actualiser lien avec mod�le",
	"S_TEMPLATE_LINKAGE_DELETED"=>		    "Lien avec mod�le supprim�",
	"S_CANNOT_DELETE_TEMPLATE_LINKAGE"=>	"Impossible de supprimer lien avec mod�le",
	"S_CONFIGURATION_OF_HOSTS_AND_HOST_GROUPS"=>"CONFIGURATION DES H�TES ET GROUPES D'H�TES",
	"S_HOST_GROUPS_BIG"=>					"GROUPES H�TES",
	"S_NO_HOST_GROUPS_DEFINED"=>			"Aucun groupe d'h�tes de d�fini",
	"S_NO_LINKAGES_DEFINED"=>				"Aucun lien d�fini",
	"S_NO_HOSTS_DEFINED"=>					"Aucun h�te d�fini",
	"S_HOSTS_BIG"=>							"H�TES",
	"S_HOST"=>								"H�te",
	"S_IP"=>								"IP",
	"S_PORT"=>								"Port",
	"S_MONITORED"=>							"Surveill�",
	"S_NOT_MONITORED"=>						"Pas surveill�",
	"S_UNREACHABLE"=>						"Non rejoignable",
	"S_TEMPLATE"=>							"Mod�le",
	"S_DELETED"=>							"Supprim�",
	"S_UNKNOWN"=>							"Inconnu",
	"S_GROUPS"=>							"Groupes",
	"S_NEW_GROUP"=>							"Nouveau groupe",
	"S_USE_IP_ADDRESS"=>					"Utiliser adresse IP",
	"S_IP_ADDRESS"=>						"addresse IP ",
//	"S_USE_THE_HOST_AS_A_TEMPLATE"=>		"Utiliser h�te comme mod�le",
	"S_USE_TEMPLATES_OF_THIS_HOST"=>		"Utiliser mod�les de cet h�te",
	"S_DELETE_SELECTED_HOST_Q"=>			"Supprimer h�te s�lectionn�?",
	"S_GROUP_NAME"=>						"Nom groupe",
	"S_HOST_GROUP"=>						"Groupe d'h�te",
	"S_HOST_GROUPS"=>						"Groupes d'h�tes",
	"S_UPDATE"=>							"Actualiser",
	"S_AVAILABILITY"=>						"Disponibilit�",
	"S_AVAILABLE"=>							"Disponible",
	"S_NOT_AVAILABLE"=>						"Pas disponible",

//	items.php
	"S_CONFIGURATION_OF_ITEMS"=>		"Configuration des items",
	"S_CONFIGURATION_OF_ITEMS_BIG"=>	"CONFIGURATION DES ITEMS",
	"S_CANNOT_UPDATE_ITEM"=>			"Impossible d'actualiser l'item",
	"S_STATUS_UPDATED"=>				"Statut actualis�",
	"S_CANNOT_UPDATE_STATUS"=>			"Impossible d'actualiser le statut",
	"S_CANNOT_ADD_ITEM"=>				"Impossible d'ajouter l'item",
	"S_ITEM_DELETED"=>					"Item supprim�",
	"S_CANNOT_DELETE_ITEM"=>			"Impossible de supprimer l'item",
	"S_ITEMS_DELETED"=>					"Items supprim�s",
	"S_CANNOT_DELETE_ITEMS"=>			"Impossible de supprimer les items",
	"S_ITEMS_ACTIVATED"=>				"Items activ�s",
	"S_CANNOT_ACTIVATE_ITEMS"=>			"Impossible d'activer les items",
	"S_ITEMS_DISABLED"=>				"Items d�sactiv�s",
	"S_SERVERNAME"=>			"Server Name",
	"S_KEY"=>							"Cl�",
	"S_DESCRIPTION"=>					"Description",
	"S_UPDATE_INTERVAL"=>				"Invervale d'actualisation",
	"S_HISTORY"=>						"Historique",
	"S_TRENDS"=>						"Tendances",
	"S_SHORT_NAME"=>					"Nom court",
	"S_ZABBIX_AGENT"=>					"agent ZABBIX ",
	"S_ZABBIX_AGENT_ACTIVE"=>			"agent ZABBIX  (actif)",
	"S_SNMPV1_AGENT"=>					"agent SNMPv1 ",
	"S_ZABBIX_TRAPPER"=>				"ZABBIX trapper",
	"S_SIMPLE_CHECK"=>					"V�rification simple",
	"S_SNMPV2_AGENT"=>					"agent SNMPv2 ",
	"S_SNMPV3_AGENT"=>					"agent SNMPv3 ",
	"S_ZABBIX_INTERNAL"=>				"ZABBIX interne",
	"S_ZABBIX_UNKNOWN"=>				"Inconnu",
	"S_ACTIVE"=>						"Actif",
	"S_NOT_ACTIVE"=>					"Inactif",
	"S_NOT_SUPPORTED"=>					"Non support�",
	"S_ACTIVATE_SELECTED_ITEMS_Q"=>		"Activer items s�lectionn�s?",
	"S_DISABLE_SELECTED_ITEMS_Q"=>		"D�sactiver items s�lectionn�s?",
	"S_DELETE_SELECTED_ITEMS_Q"=>		"Supprimer items s�lectionn�s?",
	"S_EMAIL"=>							"Courriel",
	"S_SCRIPT"=>						"Script",
	"S_UNITS"=>							"Unit�s",
	"S_MULTIPLIER"=>					"Multiplicateur",
	"S_UPDATE_INTERVAL_IN_SEC"=>		"Intervale d'actualisation (en sec)",
	"S_KEEP_HISTORY_IN_DAYS"=>			"Conserver historique (en jours)",
	"S_KEEP_TRENDS_IN_DAYS"=>			"Conserver tendances (en jours)",
	"S_TYPE_OF_INFORMATION"=>			"Type d'information",
	"S_STORE_VALUE"=>					"Stocker valeur",
	"S_NUMERIC_FLOAT"=>			"Numerique (float)",
	"S_NUMERIC_UINT64"=>			"Numerique (integer 64bit)",
	"S_CHARACTER"=>						"Caract�re",
	"S_LOG"=>							"Journal",
	"S_AS_IS"=>							"Tel quel",
	"S_DELTA_SPEED_PER_SECOND"=>		"Delta (vitesse par seconde)",
	"S_DELTA_SIMPLE_CHANGE"=>			"Delta (changement simple)",
	"S_ITEM"=>							"Item",
	"S_SNMP_COMMUNITY"=>				"Communaut� SNMP",
	"S_SNMP_OID"=>						"OID SNMP ",
	"S_SNMP_PORT"=>						"port SNMP ",
	"S_ALLOWED_HOSTS"=>				"H�tes autoris�s",
	"S_SNMPV3_SECURITY_NAME"=>		"Nom s�curit� SNMPv3",
	"S_SNMPV3_SECURITY_LEVEL"=>		"Niveau s�curit� SNMPv3",
	"S_SNMPV3_AUTH_PASSPHRASE"=>		"SNMPv3 auth passphrase",
	"S_SNMPV3_PRIV_PASSPHRASE"=>		"SNMPv3 priv passphrase",
	"S_CUSTOM_MULTIPLIER"=>			"Multiplicateur personnalis�",
	"S_DO_NOT_USE"=>			"Ne pas utiliser",
	"S_USE_MULTIPLIER"=>			"Utiliser multiplicateur",
	"S_SELECT_HOST_DOT_DOT_DOT"=>		"Choisir h�te...",

//	latestalarms.php
	"S_LATEST_EVENTS"=>				"Derniers �v�nements",
	"S_HISTORY_OF_EVENTS_BIG"=>		"HISTORIQUE DES �V�NEMENTS",

//	latest.php
	"S_LAST_CHECK"=>			"Derni�re v�rification",
	"S_LAST_CHECK_BIG"=>			"DERNI�RE V�RIFICATION",
	"S_LAST_VALUE"=>			"Derni�re valeur",

//	sysmap.php
	"S_LABEL"=>					"�tiquette",
	"S_X"=>						"X",
	"S_Y"=>						"Y",
	"S_ICON"=>					"Ic�ne",
	"S_HOST_1"=>				"H�te 1",
	"S_HOST_2"=>				"H�te 2",
	"S_LINK_STATUS_INDICATOR"=>	"Indicateur de statut de lien",

//	map.php
	"S_OK_BIG"=>				"OK",
	"S_PROBLEMS_SMALL"=>		"probl�mes",
	"S_ZABBIX_URL"=>			"http://www.zabbix.com",

//	maps.php
	"S_NETWORK_MAPS"=>				"Cartes r�seau",
	"S_NETWORK_MAPS_BIG"=>			"CARTES R�SEAU",
	"S_NO_MAPS_TO_DISPLAY"=>		"Aucune carte � afficher",
	"S_SELECT_MAP_TO_DISPLAY"=>		"Choisissez carte � afficher",
	"S_SELECT_MAP_DOT_DOT_DOT"=>	"Choisissez carte...",
	"S_BACKGROUND_IMAGE"=>			"Image de fond",
	"S_ICON_LABEL_TYPE"=>			"Type �tiquette ic�ne",
	"S_HOST_LABEL"=>				"�tiquette h�te",
	"S_HOST_NAME"=>					"Nom h�te",
	"S_STATUS_ONLY"=>				"Statut seulement",
	"S_NOTHING"=>					"Rien",

//	media.php
	"S_MEDIA"=>				"M�dia",
	"S_MEDIA_BIG"=>				"M�DIA",
	"S_MEDIA_ACTIVATED"=>			"M�dia activ�",
	"S_CANNOT_ACTIVATE_MEDIA"=>		"Impossible d'activer le m�dia",
	"S_MEDIA_DISABLED"=>			"M�dia d�sactiv�",
	"S_CANNOT_DISABLE_MEDIA"=>		"Impossible de d�sactiver le m�dia",
	"S_MEDIA_ADDED"=>			"M�dia ajout�",
	"S_CANNOT_ADD_MEDIA"=>			"Impossible d'ajouter le m�dia",
	"S_MEDIA_UPDATED"=>			"M�dia mis � jour",
	"S_CANNOT_UPDATE_MEDIA"=>		"Impossible de mettre � jour le m�dia",
	"S_MEDIA_DELETED"=>			"M�dia supprim�",
	"S_CANNOT_DELETE_MEDIA"=>		"Impossible de supprimer m�dia",
	"S_SEND_TO"=>				"Envoyer �",
	"S_WHEN_ACTIVE"=>			"Lorsque actif",
	"S_NO_MEDIA_DEFINED"=>			"Aucun m�dia d�fini",
	"S_NEW_MEDIA"=>				"Nouveau m�dia",
	"S_USE_IF_SEVERITY"=>			"Utiliser si s�v�rit�",
	"S_DELETE_SELECTED_MEDIA_Q"=>		"Supprimer m�dia s�lectionn�?",

//	Menu
	"S_MENU_LATEST_VALUES"=>		"DERNI�RES VALEURS",
	"S_MENU_TRIGGERS"=>			"D�CLENCHEURS",
	"S_MENU_QUEUE"=>			"QUEUE",
	"S_MENU_ALARMS"=>			"ALARMES",
	"S_MENU_ALERTS"=>			"ALERTES",
	"S_MENU_NETWORK_MAPS"=>			"CARTES R�SEAU",
	"S_MENU_GRAPHS"=>			"GRAPHIQUES",
	"S_MENU_SCREENS"=>			"�CRANS",
	"S_MENU_IT_SERVICES"=>			"SERVICES TI",
	"S_MENU_HOME"=>				"ACCUEIL",
	"S_MENU_ABOUT"=>			"A PROPOS",
	"S_MENU_STATUS_OF_ZABBIX"=>		"STATUT OF ZABBIX",
	"S_MENU_AVAILABILITY_REPORT"=>		"RAPPORT DISPONIBILIT�",
	"S_MENU_CONFIG"=>			"CONFIGURATION",
	"S_MENU_USERS"=>			"UTILISATEURS",
	"S_MENU_HOSTS"=>			"H�TES",
	"S_MENU_ITEMS"=>			"ITEMS",
	"S_MENU_AUDIT"=>			"AUDIT",

//	overview.php
	"S_SELECT_GROUP_DOT_DOT_DOT"=>		"Choisir groupe...",
	"S_OVERVIEW"=>				"Aper�u",
	"S_OVERVIEW_BIG"=>			"APER�U",
	"S_EXCL"=>					"!",
	"S_DATA"=>					"Donn�e",

//	queue.php
	"S_QUEUE_BIG"=>				"QUEUE",
	"S_QUEUE_OF_ITEMS_TO_BE_UPDATED_BIG"=>	"QUEUE DES ITEMS A METTRE A JOUR",
	"S_NEXT_CHECK"=>				"Prochaine v�rification",
	"S_THE_QUEUE_IS_EMPTY"=>		"La queue est vide",
	"S_TOTAL"=>						"Total",
	"S_COUNT"=>						"Compte",
	"S_5_SECONDS"=>					"5 secondes",
	"S_10_SECONDS"=>				"10 secondes",
	"S_30_SECONDS"=>				"30 secondes",
	"S_1_MINUTE"=>					"1 minute",
	"S_5_MINUTES"=>				    "5 minutes",
	"S_MORE_THAN_5_MINUTES"=>		"Plus de 5 minutes",

//	report1.php
	"S_STATUS_OF_ZABBIX"=>					"Statut de ZABBIX",
	"S_STATUS_OF_ZABBIX_BIG"=>				"STATUT DE ZABBIX",
	"S_VALUE"=>								"Valeur",
	"S_ZABBIX_SERVER_IS_RUNNING"=>			"Serveur ZABBIX en fonction",
	"S_NUMBER_OF_VALUES_STORED"=>			"Nombre de valeurs enregistr�es",
	"S_NUMBER_OF_TRENDS_STORED"=>			"Nombre de tendances enregistr�es",
	"S_NUMBER_OF_ALARMS"=>					"Nombre d'alarmes",
	"S_NUMBER_OF_ALERTS"=>					"Nombre d'alertes",
	"S_NUMBER_OF_TRIGGERS_ENABLED_DISABLED"=>"Nombre de d�clencheurs (activ�s/d�sactiv�s)",
	"S_NUMBER_OF_ITEMS_ACTIVE_TRAPPER"=>	"Nombre d'items (actifs/collecteurs/inactifs/non support�s)",
	"S_NUMBER_OF_USERS"=>					"Nombre d'utilisateurs",
	"S_NUMBER_OF_HOSTS_MONITORED"=>			"Nombre d'h�tes (surveill�s/non surveill�s/mod�les/deleted)",
	"S_YES"=>								"Oui",
	"S_NO"=>								"Non",

//	report2.php
	"S_AVAILABILITY_REPORT"=>		"Rapport de disponibilit�",
	"S_AVAILABILITY_REPORT_BIG"=>	"RAPPORT DE DISPONIBILIT�",
	"S_SHOW"=>						"Afficher",
	"S_TRUE"=>						"Vrai",
	"S_FALSE"=>						"Faux",

//	report3.php
	"S_IT_SERVICES_AVAILABILITY_REPORT_BIG"=>	"RAPPORT DE DISPONIBILIT� DES SERVICES TI",
	"S_FROM"=>									"De",
	"S_TILL"=>									"A",
	"S_OK"=>									"Ok",
	"S_PROBLEMS"=>								"Probl�mes",
	"S_PERCENTAGE"=>							"Pourcentage",
	"S_SLA"=>									"Disponibilit�",
	"S_DAY"=>									"Jour",
	"S_MONTH"=>									"Mois",
	"S_YEAR"=>									"Ann�e",
	"S_DAILY"=>									"Journalier",
	"S_WEEKLY"=>								"Hebdomadaire",
	"S_MONTHLY"=>								"Mensuel",
	"S_YEARLY"=>								"Annuel",

//	screenconf.php
	"S_SCREENS"=>						"�crans",
	"S_SCREEN"=>						"�cran",
	"S_CONFIGURATION_OF_SCREENS_BIG"=>	"CONFIGURATION DES �CRANS",
	"S_SCREEN_ADDED"=>					"�cran ajout�",
	"S_CANNOT_ADD_SCREEN"=>				"Impossible d'ajouter l'�cran",
	"S_SCREEN_UPDATED"=>				"�cran actualis�",
	"S_CANNOT_UPDATE_SCREEN"=>			"Impossible d'actualiser l'�cran",
	"S_SCREEN_DELETED"=>				"�cran supprim�",
	"S_CANNOT_DELETE_SCREEN"=>			"Impossible de supprimer �cran",
	"S_COLUMNS"=>						"Colonnes",
	"S_ROWS"=>							"Lignes",
	"S_NO_SCREENS_DEFINED"=>			"Aucuns �cran d�finis",
	"S_DELETE_SCREEN_Q"=>				"Supprimer �cran?",
	"S_CONFIGURATION_OF_SCREEN_BIG"=>	"CONFIGURATION DE L'�CRAN",
	"S_SCREEN_CELL_CONFIGURATION"=>		"Configuration cellule �cran",
	"S_RESOURCE"=>						"Ressource",
	"S_SIMPLE_GRAPH"=>					"Graphique simple",
	"S_GRAPH_NAME"=>					"Nom du graphique",
	"S_WIDTH"=>							"Largeur",
	"S_HEIGHT"=>						"Hauteur",
	"S_EMPTY"=>							"Vide",

//	screenedit.php
	"S_MAP"=>					"Carte",
	"S_PLAIN_TEXT"=>			"Texte brut",
	"S_COLUMN_SPAN"=>			"�tendue de la colonne",
	"S_ROW_SPAN"=>				"�tendue de la ligne",

//	screens.php
	"S_CUSTOM_SCREENS"=>			"�crans personnalis�s",
	"S_SCREENS_BIG"=>				"�CRANS",
	"S_NO_SCREENS_TO_DISPLAY"=>		"Aucun �cran � afficher",
	"S_SELECT_SCREEN_TO_DISPLAY"=>	"S�lectionnez l'�cran � afficher",
	"S_SELECT_SCREEN_DOT_DOT_DOT"=>	"S�lectionnez l'�cran ...",

//	services.php
	"S_IT_SERVICES"=>				"Services TI",
	"S_SERVICE_UPDATED"=>			"Service actualis�",
	"S_CANNOT_UPDATE_SERVICE"=>		"Impossible d'actualiser le service",
	"S_SERVICE_ADDED"=>				"Service ajout�",
	"S_CANNOT_ADD_SERVICE"=>		"Impossible d'ajouter le service",
	"S_LINK_ADDED"=>				"Lien ajout�",
	"S_CANNOT_ADD_LINK"=>			"Impossible d'ajouter le lien",
	"S_SERVICE_DELETED"=>			"Service supprim�",
	"S_CANNOT_DELETE_SERVICE"=>		"Impossible de supprimer le service",
	"S_LINK_DELETED"=>				"Lien supprim�",
	"S_CANNOT_DELETE_LINK"=>		"Impossible de supprimer le lien",
	"S_STATUS_CALCULATION"=>		"Calcul du statut",
	"S_STATUS_CALCULATION_ALGORITHM"=>	"Algorithme de calcul du statut",
	"S_NONE"=>						"Aucun",
	"S_MAX_OF_CHILDS"=>				"MAX des enfants",
	"S_MIN_OF_CHILDS"=>				"MIN des enfants",
	"S_SERVICE_1"=>					"Service 1",
	"S_SERVICE_2"=>					"Service 2",
	"S_SOFT_HARD_LINK"=>			"Lien souple/rigide",
	"S_SOFT"=>						"Souple",
	"S_HARD"=>						"Rigide",
	"S_DO_NOT_CALCULATE"=>			"Ne pas calculer",
	"S_MAX_BIG"=>					"MAX",
	"S_MIN_BIG"=>					"MIN",
	"S_SHOW_SLA"=>					"Afficher disponibilit�",
	"S_ACCEPTABLE_SLA_IN_PERCENT"=>	"Disponibilit� acceptable (en %)",
	"S_LINK_TO_TRIGGER_Q"=>			"Lier au d�clencheur?",
	"S_SORT_ORDER_0_999"=>			"Ordre de tri (0->999)",
	"S_DELETE_SERVICE_Q"=>			"S_DELETE_SERVICE_Q",
	"S_LINK_TO"=>					"Lier �",
	"S_SOFT_LINK_Q"=>				"Lien souple?",
	"S_ADD_SERVER_DETAILS"=>		"Ajouter d�tails au serveur",
	"S_TRIGGER"=>					"D�clencheur",
	"S_SERVER"=>					"Serveur",
	"S_DELETE"=>					"Supprimer",

//	srv_status.php
	"S_IT_SERVICES_BIG"=>			"SERVICES TI",
	"S_SERVICE"=>					"Service",
	"S_REASON"=>					"Raison",
	"S_SLA_LAST_7_DAYS"=>			"DISPONIBILIT� (7 derniers jours)",
	"S_PLANNED_CURRENT_SLA"=>		"Disponibilit� planifi�e/actuelle",
	"S_TRIGGER_BIG"=>				"D�CLENCHEUR",

//	triggers.php
	"S_CONFIGURATION_OF_TRIGGERS"=>		"Configuration des d�clencheurs",
	"S_CONFIGURATION_OF_TRIGGERS_BIG"=>	"CONFIGURATION DES D�CLENCHEURS",
	"S_DEPENDENCY_ADDED"=>				"D�pendance ajout�e",
	"S_CANNOT_ADD_DEPENDENCY"=>			"Impossible d'ajouter la d�pendance",
	"S_TRIGGERS_UPDATED"=>				"D�clencheurs actualis�s",
	"S_CANNOT_UPDATE_TRIGGERS"=>		"Impossible d'actualiser les d�clencheurs",
	"S_TRIGGERS_DISABLED"=>				"D�clencheurs d�sactiv�s",
	"S_CANNOT_DISABLE_TRIGGERS"=>		"Impossible de d�sactiver les d�clencheurs",
	"S_TRIGGERS_DELETED"=>				"D�clencheurs supprim�s",
	"S_CANNOT_DELETE_TRIGGERS"=>		"Impossible de supprimer les d�clencheurs",
	"S_TRIGGER_DELETED"=>				"D�clencheur supprim�",
	"S_CANNOT_DELETE_TRIGGER"=>			"Impossible de supprimer le d�clencheur",
	"S_INVALID_TRIGGER_EXPRESSION"=>	"D�finition du d�clencheur non valide",
	"S_TRIGGER_ADDED"=>					"D�clencheur ajout�",
	"S_CANNOT_ADD_TRIGGER"=>			"Impossible d'ajouter le d�clencheur",
	"S_SEVERITY"=>						"S�v�rit�",
	"S_EXPRESSION"=>					"Expression",
	"S_DISABLED"=>						"D�sactiv�",
	"S_ENABLED"=>						"Activ�",
	"S_ENABLE_SELECTED_TRIGGERS_Q"=>	"Activer les d�clencheurs s�lectionn�s?",
	"S_DISABLE_SELECTED_TRIGGERS_Q"=>	"D�sactiver les d�clencheurs s�lectionn�s?",
	"S_CHANGE"=>						"Changer",
	"S_TRIGGER_UPDATED"=>				"D�clencheur mis � jour",
	"S_CANNOT_UPDATE_TRIGGER"=>			"Impossible d'actualiser le d�clencheur",
	"S_DEPENDS_ON"=>					"D�pend de",

//	tr_comments.php
	"S_TRIGGER_COMMENTS"=>			"Commentaires sur d�clencheur",
	"S_TRIGGER_COMMENTS_BIG"=>		"COMMENTAIRES SUR D�CLENCHEUR",
	"S_COMMENT_UPDATED"=>			"Commentaire actualis�",
	"S_CANNOT_UPDATE_COMMENT"=>		"Impossible d'actualiser le commentaire",
	"S_ADD"=>						"Ajouter",

//	tr_status.php
	"S_STATUS_OF_TRIGGERS"=>		"Statut des d�clencheurs",
	"S_STATUS_OF_TRIGGERS_BIG"=>	"STATUT DES D�CLENCHEURS",
	"S_SHOW_ONLY_TRUE"=>			"Afficher seulement si vrai",
	"S_HIDE_ACTIONS"=>				"Cacher les actions",
	"S_SHOW_ACTIONS"=>				"Afficher les actions",
	"S_SHOW_ALL_TRIGGERS"=>			"Afficher tous les d�clencheurs",
	"S_HIDE_DETAILS"=>				"Cacher les d�tails",
	"S_SHOW_DETAILS"=>				"Afficher les d�tails",
	"S_SELECT"=>					"S�lectionner",
	"S_HIDE_SELECT"=>				"Cacher la s�lection",
	"S_TRIGGERS_BIG"=>				"D�CLENCHEURS",
	"S_DESCRIPTION_BIG"=>			"DESCRIPTION",
	"S_SEVERITY_BIG"=>				"S�V�RIT�",
	"S_LAST_CHANGE_BIG"=>			"DERNIER CHANGEMENT",
	"S_LAST_CHANGE"=>				"Dernier changement",
	"S_COMMENTS"=>					"Commentaires",

//	users.php
	"S_USERS"=>						"Utilisateurs",
	"S_USER_ADDED"=>				"Utilisateur ajout�",
	"S_CANNOT_ADD_USER"=>			"Impossible d'ajouter l'utilisateur",
	"S_CANNOT_ADD_USER_BOTH_PASSWORDS_MUST"=>"Impossible d'ajouter l'utilisateur. Les mots de passe doivent �tre identiques.",
	"S_USER_DELETED"=>				"Utilisateur supprim�",
	"S_CANNOT_DELETE_USER"=>		"Impossible de supprimer l'utilisateur",
	"S_PERMISSION_DELETED"=>		"Permission retir�e",
	"S_CANNOT_DELETE_PERMISSION"=>	"Impossible de retirer la permission",
	"S_PERMISSION_ADDED"=>			"Permission ajout�e",
	"S_CANNOT_ADD_PERMISSION"=>		"Impossible d'ajouter la permission",
	"S_USER_UPDATED"=>				"Utilisateur actualis�",
	"S_CANNOT_UPDATE_USER"=>		"Impossible d'actualiser l'utilisateur",
	"S_CANNOT_UPDATE_USER_BOTH_PASSWORDS"=>	"Impossible d'actualiser l'utilisateur. Les mots de passe doivent �tre identiques.",
	"S_GROUP_ADDED"=>				"Groupe ajout�",
	"S_CANNOT_ADD_GROUP"=>			"Impossible d'ajouter le groupe",
	"S_GROUP_UPDATED"=>				"Groupe actualis�",
	"S_CANNOT_UPDATE_GROUP"=>		"Impossible d'actualiser le groupe",
	"S_GROUP_DELETED"=>				"Groupe supprim�",
	"S_CANNOT_DELETE_GROUP"=>		"Impossible de supprimer le groupe",
	"S_CONFIGURATION_OF_USERS_AND_USER_GROUPS"=>"CONFIGURATION DES UTILISATEURS ET DES GROUPES",
	"S_USER_GROUPS_BIG"=>			"GROUPES D'UTILISATEURS",
	"S_USERS_BIG"=>					"UTILISATEURS",
	"S_USER_GROUPS"=>				"Groupes d'utilisateurs",
	"S_MEMBERS"=>					"Membres",
	"S_TEMPLATES"=>					"Patrons",
	"S_HOSTS_TEMPLATES_LINKAGE"=>	"Liens h�tes/mod�les",
	"S_CONFIGURATION_OF_TEMPLATES_LINKAGE"=>"CONFIGURATION DES LIENS DE MOD�LES",
	"S_LINKED_TEMPLATES_BIG"=>		"MOD�LES LI�S",
	"S_NO_USER_GROUPS_DEFINED"=>	"Aucun groupe utilisateur de d�fini",
	"S_ALIAS"=>						"Alias",
	"S_NAME"=>						"Nom",
	"S_SURNAME"=>					"Pr�nom",
	"S_IS_ONLINE_Q"=>				"Est connect�?",
	"S_NO_USERS_DEFINED"=>			"Aucuns utilisateurs d�finis",
	"S_PERMISSION"=>				"Permission",
	"S_RIGHT"=>						"Droit",
	"S_RESOURCE_NAME"=>				"Nom de la ressource",
	"S_READ_ONLY"=>					"Lecture seule",
	"S_READ_WRITE"=>				"Lecture-�criture",
	"S_HIDE"=>						"Cacher",
	"S_PASSWORD"=>					"Mot de passe",
	"S_PASSWORD_ONCE_AGAIN"=>		"Mot de passe (une autre fois)",
	"S_URL_AFTER_LOGIN"=>			"URL (apr�s connexion)",
	"S_AUTO_LOGOUT_IN_SEC"=>		"D�connection automatique (dans =>0 secondes - d�sactiver)",
	"S_SCREEN_REFRESH"=>                    "Refresh (in seconds)",

//	audit.php
	"S_AUDIT_LOG"=>				"Journal de v�rification",
	"S_AUDIT_LOG_BIG"=>			"JOURNAL DE V�RIFICATION",
	"S_ACTION"=>				"Action",
	"S_DETAILS"=>				"D�tails",
	"S_UNKNOWN_ACTION"=>		"Action inconnue",
	"S_ADDED"=>					"Ajout�",
	"S_UPDATED"=>				"Actualis�",
	"S_LOGGED_IN"=>				"Connect�",
	"S_LOGGED_OUT"=>			"D�connect�",
	"S_MEDIA_TYPE"=>			"Type de m�dia",
	"S_GRAPH_ELEMENT"=>			"�l�ment graphique",

//	profile.php
	"S_USER_PROFILE_BIG"=>		"PROFIL UTILISATEUR",
	"S_USER_PROFILE"=>			"Profil utilisateur",
	"S_LANGUAGE"=>				"Langue",
	"S_ENGLISH_GB"=>			"Anglais (GB)",
	"S_FRENCH_FR"=>				"Fran�ais (FR)",
	"S_GERMAN_DE"=>				"Allemand (DE)",
	"S_ITALIAN_IT"=>			"Italien (IT)",
	"S_LATVIAN_LV"=>			"Letton (LV)",
	"S_RUSSIAN_RU"=>			"Russe (RU)",
	"S_SPANISH_SP"=>			"Espagnol (SP)",

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

	"S_HELP"=>					"Aide",
	"S_PROFILE"=>				"Profil",
	);
?>
