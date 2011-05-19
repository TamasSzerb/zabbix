<?php
/*
** Zabbix
** Copyright (C) 2000-2011 Zabbix SIA
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

require_once(dirname(__FILE__).'/../../include/defines.inc.php');
require_once(dirname(__FILE__).'/../../conf/zabbix.conf.php');
require_once(dirname(__FILE__).'/../../include/copt.lib.php');
require_once(dirname(__FILE__).'/../../include/func.inc.php');
require_once(dirname(__FILE__).'/../../include/db.inc.php');

function error($error)
{
	echo "\nError reported: $error\n";
	return true;
}

/**
 * Returns database data suitable for PHPUnit data provider functions
 */
function DBdata($query)
{
	DBconnect($error);

	$objects=array();

	$result=DBselect($query);
	while($object=DBfetch($result))
	{
		$objects[]=array($object);
	}

	DBclose();
	return $objects;
}

/**
 * The function returns list of all referenced tables sorted by dependency level
 * For example: DBget_tables('users')
 * Result: array(users,alerts,acknowledges,auditlog,auditlog_details,opmessage_usr,media,profiles,sessions,users_groups)
 */
function DBget_tables(&$tables, $topTable)
{
	if(in_array($topTable, $tables))
		return;

	$schema = include(dirname(__FILE__).'/../../include/schema.inc.php');

	$tableData = $schema[$topTable];

	$fields = $tableData['fields'];
	foreach($fields as $field => $fieldData){
		if(isset($fieldData['ref_table'])){
			$refTable = $fieldData['ref_table'];
			if($refTable != $topTable)
				DBget_tables($tables, $refTable);
		}
	}

	if(!in_array($topTable, $tables))
		$tables[] = $topTable;

	foreach($schema as $table => $tableData)
	{
		$fields = $schema[$table]['fields'];
		$referenced = false;
		foreach($fields as $field => $fieldData){
			if(isset($fieldData['ref_table'])){
				$refTable = $fieldData['ref_table'];
				if($refTable == $topTable && $topTable != $table){
					DBget_tables($tables, $table);
				}
			}
		}
	}
}

/*
 * Saves data of the specified table and all dependent tables in temporary storage.
 * For example: DBsave_tables('users')
 */
function DBsave_tables($topTable)
{
	global $DB;

	$tables = array();

	DBget_tables($tables, $topTable);

	foreach($tables as $table)
	{
		switch($DB['TYPE']) {
		case ZBX_DB_MYSQL:
			DBexecute("drop table if exists ${table}_tmp");
			DBexecute("create table ${table}_tmp like $table");
			DBexecute("insert into ${table}_tmp select * from $table");
			break;
		case ZBX_DB_SQLITE3:
			DBexecute("drop table if exists ${table}_tmp");
			DBexecute("create table if not exists ${table}_tmp as select * from ${table}");
			break;
		default:
			DBexecute("drop table if exists ${table}_tmp");
			DBexecute("select * into temp table ${table}_tmp from $table");
		}
	}
}

/**
 * Restores data from temporary storage. DBsave_tables() must be called first.
 * For example: DBrestore_tables('users')
 */
function DBrestore_tables($topTable)
{
	global $DB;

	$tables = array();

	DBget_tables($tables, $topTable);

	$tables_reversed = array_reverse($tables);

	foreach($tables_reversed as $table)
	{
		DBexecute("delete from $table");
	}

	foreach($tables as $table)
	{
		DBexecute("insert into $table select * from ${table}_tmp");
		DBexecute("drop table ${table}_tmp");
	}
}

/**
 * Returns md5 hash sum of database result.
 */
function DBhash($sql)
{
	global $DB;

	$hash = '<empty hash>';

	$result=DBselect($sql);
	while($row = DBfetch($result))
	{
		foreach($row as $key => $value)
		{
			$hash = md5($hash.$value);
		}
	}

	return $hash;
}

/**
 * Returns number of records in database result.
 */
function DBcount($sql, $limit = null, $offset = null){
	$cnt = 0;

	if(isset($limit) && isset($offset)){
		$result = DBselect($sql, $limit, $offset);
	}
	else if(isset($limit)){
		$result = DBselect($sql,$limit);
	}
	else{
		$result = DBselect($sql);
	}

	while(DBfetch($result)){
		$cnt++;
	}

	return $cnt;
}

?>
