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

#include "common.h"

#include "db.h"
#include "zbxdb.h"
#include "log.h"
#include "zlog.h"
#include "daemon.h"
#include "zbxself.h"
#include "zbxalgo.h"

#include "../alerter/alerter.h"

#include "watchdog.h"

#define STR_REPLACE(str1, str2)	if (NULL == str1 || 0 != strcmp(str1, str2)) str1 = zbx_strdup(str1, str2)

#define ALERT_FREQUENCY		15 * SEC_PER_MIN
#define DB_PING_FREQUENCY	SEC_PER_MIN

typedef struct
{
	DB_ALERT	alert;
	DB_MEDIATYPE	mediatype;
}
ZBX_RECIPIENT;

static zbx_vector_ptr_t	recipients;
static int		lastsent = 0;

extern unsigned char	process_type;
extern int		CONFIG_CONFSYNCER_FREQUENCY;

/******************************************************************************
 *                                                                            *
 * Function: send_alerts                                                      *
 *                                                                            *
 * Purpose: send warning message to all interested                            *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: messages are sent only every ALERT_FREQUENCY seconds             *
 *                                                                            *
 ******************************************************************************/
static void	send_alerts()
{
	int	i, now;
	char	error[MAX_STRING_LEN];

	now = time(NULL);

	if (now > lastsent + ALERT_FREQUENCY)
	{
		for (i = 0; i < recipients.values_num; i++)
		{
			execute_action(&((ZBX_RECIPIENT *)recipients.values[i])->alert,
					&((ZBX_RECIPIENT *)recipients.values[i])->mediatype, error, sizeof(error));
		}

		lastsent = now;
	}
}

/******************************************************************************
 *                                                                            *
 * Function: sync_config                                                      *
 *                                                                            *
 * Purpose: sync list of medias to send notifications in case if DB is down   *
 *                                                                            *
 * Author: Alexei Vladishev, Rudolfs Kreicbergs                               *
 *                                                                            *
 ******************************************************************************/
static void	sync_config()
{
	const char	*__function_name = "sync_config";

	DB_RESULT	result;
	DB_ROW		row;
	ZBX_RECIPIENT	*recipient;
	int		count = 0, old_count;
	static int	no_recipients = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	result = DBselect_once("select mt.mediatypeid,mt.type,mt.description,"
				"mt.smtp_server,mt.smtp_helo,mt.smtp_email,"
				"mt.exec_path,mt.gsm_modem,"
				"mt.username,mt.passwd,m.sendto"
				" from media m,users_groups u,config c,media_type mt"
				" where m.userid=u.userid"
					" and u.usrgrpid=c.alert_usrgrpid"
					" and m.mediatypeid=mt.mediatypeid"
					" and m.active=%d",
				MEDIA_STATUS_ACTIVE);

	if (NULL == result || (DB_RESULT)ZBX_DB_DOWN == result)
	{
		zabbix_log(LOG_LEVEL_WARNING, "Watchdog: Database is down");
		send_alerts();
		goto exit;
	}

	old_count = recipients.values_num;

	while (NULL != (row = DBfetch(result)))
	{
		if (count >= recipients.values_num)
		{
			recipient = zbx_calloc(NULL, 1, sizeof(ZBX_RECIPIENT));
			zbx_vector_ptr_append(&recipients, recipient);
		}
		else
			recipient = recipients.values[count];

		ZBX_STR2UINT64(recipient->mediatype.mediatypeid, row[0]);
		recipient->mediatype.type = atoi(row[1]);

		STR_REPLACE(recipient->mediatype.description, strdup(row[2]));
		STR_REPLACE(recipient->mediatype.smtp_server, strdup(row[3]));
		STR_REPLACE(recipient->mediatype.smtp_helo, strdup(row[4]));
		STR_REPLACE(recipient->mediatype.smtp_email, strdup(row[5]));
		STR_REPLACE(recipient->mediatype.exec_path, strdup(row[6]));
		STR_REPLACE(recipient->mediatype.gsm_modem, strdup(row[7]));
		STR_REPLACE(recipient->mediatype.username, strdup(row[8]));
		STR_REPLACE(recipient->mediatype.passwd, strdup(row[9]));

		STR_REPLACE(recipient->alert.sendto, strdup(row[10]));

		if (NULL == recipient->alert.subject)
			recipient->alert.message = recipient->alert.subject = zbx_strdup(NULL, "Zabbix database is down.");

		count++;
	}
	DBfree_result(result);

	if (0 < old_count && 0 == count)
	{
		zabbix_log(LOG_LEVEL_WARNING, "Watchdog: no users will receive database down messages");
		no_recipients = 1;
	}
	else if (1 == no_recipients && 0 < count)
	{
		zabbix_log(LOG_LEVEL_INFORMATION, "Watchdog: %d user(s) will receive database down messages", count);
		no_recipients = 0;
	}

	recipients.values_num = count;

	while (count < old_count)
	{
		recipient = recipients.values[count++];

		zbx_free(recipient->mediatype.description);
		zbx_free(recipient->mediatype.smtp_server);
		zbx_free(recipient->mediatype.smtp_helo);
		zbx_free(recipient->mediatype.smtp_email);
		zbx_free(recipient->mediatype.exec_path);
		zbx_free(recipient->mediatype.gsm_modem);
		zbx_free(recipient->mediatype.username);
		zbx_free(recipient->mediatype.passwd);
		zbx_free(recipient->alert.sendto);
		zbx_free(recipient->alert.subject);

		zbx_free(recipient);
	}
exit:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s() values_num:%d", __function_name, recipients.values_num);
}

/******************************************************************************
 *                                                                            *
 * Function: main_watchdog_loop                                               *
 *                                                                            *
 * Purpose: check database availability every DB_PING_FREQUENCY seconds and   *
 *          alert admins if it is down                                        *
 *                                                                            *
 * Author: Alexei Vladishev, Rudolfs Kreicbergs                               *
 *                                                                            *
 ******************************************************************************/
void	main_watchdog_loop()
{
	int	now, nextsync = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In main_watchdog_loop()");

	/* disable writing to database in zabbix_syslog() */
	CONFIG_ENABLE_LOG = 0;

	zbx_vector_ptr_create(&recipients);

	for (;;)
	{
		zbx_setproctitle("%s [pinging database]", get_process_type_string(process_type));

		if (ZBX_DB_OK != DBconnect(ZBX_DB_CONNECT_ONCE))
		{
			zabbix_log(LOG_LEVEL_WARNING, "Watchdog: Database is down");
			send_alerts();
		}
		else if (nextsync <= (now = (int)time(NULL)))
		{
			zbx_setproctitle("%s [syncing configuration]", get_process_type_string(process_type));

			sync_config();

			nextsync = now + CONFIG_CONFSYNCER_FREQUENCY;
		}

		DBclose();

		zbx_sleep_loop(DB_PING_FREQUENCY);
	}
}
