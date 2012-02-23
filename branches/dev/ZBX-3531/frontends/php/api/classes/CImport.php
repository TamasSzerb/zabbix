<?php
/*
** Zabbix
** Copyright (C) 2000-2012 Zabbix SIA
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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CImport extends CZBXAPI {

	public function export(array $params) {
		$export = new CConfigurationExport($params);
		$export->setBuilder(new CConfigurationExportBuilder());
		$export->setWriter(CExportWriterFactory::getWriter(CExportWriterFactory::JSON));

		return $export->export();
	}

	public function import($params) {
		$importReader = CImportReaderFactory::getReader($params['format']);
		$configurationImport = new CConfigurationImport($params['source'], $params['rules']);
		$configurationImport->setReader($importReader);
		return $configurationImport->import();
	}
}
