#ifndef ZABBIX_FUNCTIONS_H
#define ZABBIX_FUNCTIONS_H

#include "db.h"

void    update_triggers (int suckers, int flag,int sucker_num,int lastclock);
int	get_lastvalue(float *Result,char *host,char *key,char *function,char *parameter);
int	process_data(char *server,char *key, char *value);
void	process_new_value(DB_ITEM *item,char *value);

#endif
