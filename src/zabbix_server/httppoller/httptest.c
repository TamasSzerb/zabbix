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

#include "config.h"

#include "cfg.h"
#include "pid.h"
#include "db.h"
#include "log.h"
#include "zlog.h"

#include "common.h"
#include "../functions.h"
#include "httpmacro.h"
#include "httptest.h"

S_ZBX_HTTPPAGE	page;

/******************************************************************************
 *                                                                            *
 * Function: process_value                                                    *
 *                                                                            *
 * Purpose: process new item value                                            *
 *                                                                            *
 * Parameters: key - item key                                                 *
 *             host - host name                                               *
 *             value - new value of the item                                  *
 *                                                                            *
 * Return value: SUCCEED - new value sucesfully processed                     *
 *               FAIL - otherwise                                             *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: can be done in process_data()                                    *
 *                                                                            *
 ******************************************************************************/
static int process_value(zbx_uint64_t itemid, AGENT_RESULT *value)
{
	DB_RESULT	result;
	DB_ROW		row;
	DB_ITEM		item;

	zabbix_log( LOG_LEVEL_DEBUG, "In process_value(itemid:" ZBX_FS_UI64 ")", itemid);

	result = DBselect("select %s where h.status=%d and h.hostid=i.hostid and i.status=%d and i.type=%d and i.itemid=" ZBX_FS_UI64 " and " ZBX_COND_NODEID, ZBX_SQL_ITEM_SELECT, HOST_STATUS_MONITORED, ITEM_STATUS_ACTIVE, ITEM_TYPE_HTTPTEST, itemid, LOCAL_NODE("h.hostid"));
	row=DBfetch(result);

	if(!row)
	{
		DBfree_result(result);
		return  FAIL;
	}

	DBget_item_from_db(&item,row);

	DBbegin();
	process_new_value(&item,value);
	update_triggers(item.itemid);
	DBcommit();
 
	DBfree_result(result);

	return SUCCEED;
}

static size_t WRITEFUNCTION2( void *ptr, size_t size, size_t nmemb, void *stream)
{
/*	size_t s = size*nmemb + 1;
	char *str_dat = calloc(1, s);

	zbx_snprintf(str_dat,s,ptr);
	ZBX_LIM_PRINT("WRITEFUNCTION", s, str_dat, 65535);
	zabbix_log(LOG_LEVEL_WARNING, "In WRITEFUNCTION");
*/

	/* First piece of data */
	if(page.data == NULL)
	{
		page.allocated=8096;
		page.offset=0;
		page.data=malloc(page.allocated);
	}

	zbx_snprintf_alloc(&page.data, &page.allocated, &page.offset, 8096, ptr);

	return size*nmemb;
}

static size_t HEADERFUNCTION2( void *ptr, size_t size, size_t nmemb, void *stream)
{
/*	
	ZBX_LIM_PRINT("HEADERFUNCTION", size*nmemb, ptr, 300);
	zabbix_log(LOG_LEVEL_WARNING, "In HEADERFUNCTION");
*/

	return size*nmemb;
}

static void	process_test_data(DB_HTTPTEST *httptest, S_ZBX_HTTPSTAT *stat)
{
#ifdef	HAVE_LIBCURL
	DB_RESULT	result;
	DB_ROW		row;
	DB_HTTPTESTITEM	httptestitem;

	AGENT_RESULT    value;

	zabbix_log(LOG_LEVEL_DEBUG, "     TEST [%s]: Time %f Last step %d",
		 httptest->name, stat->test_total_time, stat->test_last_step);

	result = DBselect("select httptestitemid,httptestid,itemid,type from httptestitem where httptestid=" ZBX_FS_UI64,
		httptest->httptestid);

	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(httptestitem.httptestitemid, row[0]);
		ZBX_STR2UINT64(httptestitem.httptestid, row[1]);
		ZBX_STR2UINT64(httptestitem.itemid, row[2]);
		httptestitem.type=atoi(row[3]);

		switch (httptestitem.type) {
			case ZBX_HTTPITEM_TYPE_TIME:
				init_result(&value);
				SET_DBL_RESULT(&value, stat->test_total_time);
				process_value(httptestitem.itemid,&value);
				free_result(&value);
				break;
			case ZBX_HTTPITEM_TYPE_LASTSTEP:
				init_result(&value);
				SET_UI64_RESULT(&value, stat->test_last_step);
				process_value(httptestitem.itemid,&value);
				free_result(&value);
				break;
			default:
				break;
		}
	}
	
	DBfree_result(result);
#endif
}


static void	process_step_data(DB_HTTPTEST *httptest, DB_HTTPSTEP *httpstep, S_ZBX_HTTPSTAT *stat)
{
#ifdef	HAVE_LIBCURL
	DB_RESULT	result;
	DB_ROW		row;
	DB_HTTPSTEPITEM	httpstepitem;

	AGENT_RESULT    value;

	zabbix_log(LOG_LEVEL_DEBUG, "     Step [%s] [%s]: Rsp %d Time %f Speed %f",
		 httpstep->name, httpstep->url, stat->rspcode, stat->total_time, stat->speed_download);

	result = DBselect("select httpstepitemid,httpstepid,itemid,type from httpstepitem where httpstepid=" ZBX_FS_UI64,
		httpstep->httpstepid);

	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(httpstepitem.httpstepitemid, row[0]);
		ZBX_STR2UINT64(httpstepitem.httpstepid, row[1]);
		ZBX_STR2UINT64(httpstepitem.itemid, row[2]);
		httpstepitem.type=atoi(row[3]);

		switch (httpstepitem.type) {
			case ZBX_HTTPITEM_TYPE_RSPCODE:
				init_result(&value);
				SET_UI64_RESULT(&value, stat->rspcode);
				process_value(httpstepitem.itemid,&value);
				free_result(&value);
				break;
			case ZBX_HTTPITEM_TYPE_TIME:
				init_result(&value);
				SET_DBL_RESULT(&value, stat->total_time);
				process_value(httpstepitem.itemid,&value);
				free_result(&value);
				break;
			case ZBX_HTTPITEM_TYPE_SPEED:
				init_result(&value);
				SET_DBL_RESULT(&value, stat->speed_download);
				process_value(httpstepitem.itemid,&value);
				free_result(&value);
				break;
			default:
				break;
		}
	}
	
	DBfree_result(result);

/*	DB_RESULT	result;
	DB_ROW	row;
	char	server_esc[MAX_STRING_LEN];
	char	key_esc[MAX_STRING_LEN];

	zabbix_log(LOG_LEVEL_WARNING, "In process_httptest(httptestid:" ZBX_FS_UI64 ")", stat->httptestid);

	DBescape_string(server, server_esc, MAX_STRING_LEN);
	DBescape_string(key, key_esc, MAX_STRING_LEN);

	result = DBselect("select %s where h.status=%d and h.hostid=i.hostid and h.host='%s' and i.key_='%s' and i.status=%d and i.type in (%d,%d) and" ZBX_COND_NODEID, ZBX_SQL_ITEM_SELECT, HOST_STATUS_MONITORED, server_esc, key_esc, ITEM_STATUS_ACTIVE, ITEM_TYPE_TRAPPER, ITEM_TYPE_ZABBIX_ACTIVE, LOCAL_NODE("h.hostid"));

	row=DBfetch(result);
	DBget_item_from_db(&item,row);

	if(set_result_type(&agent, item.value_type, value) == SUCCEED)
	{
		process_new_value(&item,&agent);
		update_triggers(item.itemid);
	}
	else
	{
		zabbix_log( LOG_LEVEL_WARNING, "Type of received value [%s] is not suitable for [%s@%s]", value, item.key, item.host );
		zabbix_syslog("Type of received value [%s] is not suitable for [%s@%s]", value, item.key, item.host );
	}
 
	DBfree_result(result);*/
#endif
}

/******************************************************************************
 *                                                                            *
 * Function: process_httptest                                                 *
 *                                                                            *
 * Purpose: process single scenario of http test                              *
 *                                                                            *
 * Parameters: httptestid - ID of http test                                   *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: SUCCEED or FAIL                                                  *
 *                                                                            *
 ******************************************************************************/
static int	process_httptest(DB_HTTPTEST *httptest)
{
#ifdef HAVE_LIBCURL
	DB_RESULT	result;
	DB_ROW		row;
	DB_HTTPSTEP	httpstep;
	int		ret = SUCCEED;
	int		err;
	int		now;
	int		lastfailedstep;

	S_ZBX_HTTPSTAT	stat;

	CURL            *easyhandle = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In process_httptest(httptestid:" ZBX_FS_UI64 ")", httptest->httptestid);
	zabbix_log(LOG_LEVEL_DEBUG, "Test [%s]", httptest->name);

	DBexecute("update httptest set lastcheck=%d where httptestid=" ZBX_FS_UI64,
		now,
		httptest->httptestid);

	easyhandle = curl_easy_init();
	if(easyhandle == NULL)
	{
		zabbix_log(LOG_LEVEL_ERR, "Cannot init CURL");

		return FAIL;
	}
	if(CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_COOKIEFILE, "")))
	{
		zabbix_log(LOG_LEVEL_ERR, "Cannot set CURLOPT_COOKIEFILE [%s]", curl_easy_strerror(err));
		return FAIL;
	}
	if(CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_USERAGENT, httptest->agent)))
	{
		zabbix_log(LOG_LEVEL_ERR, "Cannot set CURLOPT_USERAGENT [%s]", curl_easy_strerror(err));
		return FAIL;
	}
	if(CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_FOLLOWLOCATION, 1)))
	{
		zabbix_log(LOG_LEVEL_ERR, "Cannot set CURLOPT_FOLLOWLOCATION [%s]", curl_easy_strerror(err));
		return FAIL;
	}
	if(CURLE_OK != (err = curl_easy_setopt(easyhandle,CURLOPT_WRITEFUNCTION ,WRITEFUNCTION2)))
	{
		zabbix_log(LOG_LEVEL_ERR, "Error doing curl_easy_perform [%s]", curl_easy_strerror(err));
		return FAIL;
	}
	if(CURLE_OK != (err = curl_easy_setopt(easyhandle,CURLOPT_HEADERFUNCTION ,HEADERFUNCTION2)))
	{
		zabbix_log(LOG_LEVEL_ERR, "Error doing curl_easy_perform [%s]", curl_easy_strerror(err));
		return FAIL;
	}

	lastfailedstep=0;
	httptest->time = 0;
	result = DBselect("select httpstepid,httptestid,no,name,url,timeout,posts,required from httpstep where httptestid=" ZBX_FS_UI64 " order by no",
				httptest->httptestid);
	now=time(NULL);
	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(httpstep.httpstepid, row[0]);
		ZBX_STR2UINT64(httpstep.httptestid, row[1]);
		httpstep.no=atoi(row[2]);
		httpstep.name=row[3];
		strscpy(httpstep.url,row[4]);
		httpstep.timeout=atoi(row[5]);
		strscpy(httpstep.posts,row[6]);
		strscpy(httpstep.required,row[7]);


		DBexecute("update httptest set curstep=%d,curstate=%d where httptestid=" ZBX_FS_UI64,
			httpstep.no,
			HTTPTEST_STATE_BUSY,
			httptest->httptestid);

		memset(&stat,0,sizeof(stat));

		/* Substitute macros */
		http_substitute_macros(httptest,httpstep.url, sizeof(httpstep.url));
		/* zabbix_log(LOG_LEVEL_WARNING, "URL [%s]", httpstep.url); */
		http_substitute_macros(httptest,httpstep.posts, sizeof(httpstep.posts));
		/* zabbix_log(LOG_LEVEL_WARNING, "POSTS [%s]", httpstep.posts); */

		if(httpstep.posts[0] != 0)
		{
			if(CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_POSTFIELDS, httpstep.posts)))
			{
				zabbix_log(LOG_LEVEL_ERR, "Cannot set POST vars [%s]", curl_easy_strerror(err));
				ret = FAIL;
				break;
			}
		}
		if(CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_URL, httpstep.url)))
		{
			zabbix_log(LOG_LEVEL_ERR, "Cannot set URL [%s]", curl_easy_strerror(err));
			ret = FAIL;
			break;
		}
/*		if(CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_TIMEOUT, httpstep.timeout)))
		{
			zabbix_log(LOG_LEVEL_ERR, "Cannot set URL [%s]", curl_easy_strerror(err));
			ret = FAIL;
			break;
		}*/

		memset(&page, 0, sizeof(page));
		if(CURLE_OK != (err = curl_easy_perform(easyhandle)))
		{
			zabbix_log(LOG_LEVEL_ERR, "Error doing curl_easy_perform [%s]", curl_easy_strerror(err));
			ret = FAIL;
			break;
		}

		if(zbx_regexp_match(page.data,httpstep.required,NULL) == NULL)
		{
zabbix_log(LOG_LEVEL_DEBUG, "Page didn't match [%s]", httpstep.required);
zabbix_log(LOG_LEVEL_DEBUG, "[%s]", page.data);
			lastfailedstep = httpstep.no;
		}

		free(page.data);


		if(CURLE_OK != (err = curl_easy_getinfo(easyhandle,CURLINFO_RESPONSE_CODE ,&stat.rspcode)))
		{
			zabbix_log(LOG_LEVEL_ERR, "Error doing curl_easy_perform [%s]", curl_easy_strerror(err));
			ret = FAIL;
			break;
		}
		if(CURLE_OK != (err = curl_easy_getinfo(easyhandle,CURLINFO_TOTAL_TIME ,&stat.total_time)))
		{
			zabbix_log(LOG_LEVEL_ERR, "Error doing curl_easy_perform [%s]", curl_easy_strerror(err));
			ret = FAIL;
			break;
		}
		if(CURLE_OK != (err = curl_easy_getinfo(easyhandle,CURLINFO_SPEED_DOWNLOAD ,&stat.speed_download)))
		{
			zabbix_log(LOG_LEVEL_ERR, "Error doing curl_easy_perform [%s]", curl_easy_strerror(err));
			ret = FAIL;
			break;
		}

		process_step_data(httptest, &httpstep, &stat);

		httptest->time+=stat.total_time;

		if(lastfailedstep > 0)	break;
	}
	DBfree_result(result);

	(void)curl_easy_cleanup(easyhandle);

	DBexecute("update httptest set curstep=0,curstate=%d,nextcheck=%d+delay,lastfailedstep=%d,time=%f where httptestid=" ZBX_FS_UI64,
		HTTPTEST_STATE_IDLE,
		now,
		lastfailedstep,
		httptest->time,
		httptest->httptestid);

	stat.test_total_time =  httptest->time;
	stat.test_last_step = lastfailedstep;

	process_test_data(httptest, &stat);

	zabbix_log(LOG_LEVEL_DEBUG, "TOTAL: Time %f", httptest->time);

	return ret;
#endif /* HAVE_LIBCURL */
}

/******************************************************************************
 *                                                                            *
 * Function: process_httptests                                                *
 *                                                                            *
 * Purpose: process httptests                                                 *
 *                                                                            *
 * Parameters: now - current timestamp                                        *
 *                                                                            *
 * Return value:                                                              *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 * Comments: always SUCCEED                                                   *
 *                                                                            *
 ******************************************************************************/
void process_httptests(int now)
{
#ifdef HAVE_LIBCURL
	DB_RESULT	result;
	DB_ROW		row;

	DB_HTTPTEST	httptest;

	zabbix_log(LOG_LEVEL_DEBUG, "In process_httptests");

	result = DBselect("select httptestid,name,applicationid,nextcheck,status,delay,macros,agent from httptest where status=%d and nextcheck<=%d and " ZBX_SQL_MOD(httptestid,%d) "=%d and " ZBX_COND_NODEID, HTTPTEST_STATUS_MONITORED, now, CONFIG_HTTPPOLLER_FORKS, httppoller_num-1, LOCAL_NODE("httptestid"));
	while((row=DBfetch(result)))
	{
		ZBX_STR2UINT64(httptest.httptestid, row[0]);
		httptest.name=row[1];
		ZBX_STR2UINT64(httptest.applicationid, row[2]);
		httptest.nextcheck=atoi(row[3]);
		httptest.status=atoi(row[4]);
		httptest.delay=atoi(row[5]);
		httptest.macros=row[6];
		httptest.agent=row[7];

		process_httptest(&httptest);
	}
	DBfree_result(result);
#endif /* HAVE_LIBCURL */
}
