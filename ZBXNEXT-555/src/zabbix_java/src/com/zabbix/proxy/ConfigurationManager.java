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

package com.zabbix.proxy;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

class ConfigurationManager
{
	private static final Logger logger = LoggerFactory.getLogger(ConfigurationManager.class);

	public static final String LISTEN_IP = "listenIP";
	public static final String LISTEN_PORT = "listenPort";
	public static final String START_POLLERS = "startPollers";

	private static ConfigurationParameter[] parameters =
	{
		new ConfigurationParameter(START_POLLERS, ConfigurationParameter.TYPE_INTEGER, 5, new IntegerValidator(1, 255)),
		new ConfigurationParameter(LISTEN_IP, ConfigurationParameter.TYPE_INETADDRESS, null, null),
		new ConfigurationParameter(LISTEN_PORT, ConfigurationParameter.TYPE_INTEGER, 10051, new IntegerValidator(1, 65535))
	};

	public static void parseConfiguration()
	{
		logger.debug("starting to parse configuration parameters");

		for (ConfigurationParameter parameter : parameters)
		{
			String property = System.getProperty("zabbix." + parameter.getName());

			if (null != property)
			{
				logger.debug("found {} configuration parameter with value '{}'", parameter.getName(), property);
				parameter.setValue(property);
			}
		}

		logger.debug("finished parsing configuration parameters");
	}

	public static ConfigurationParameter getParameter(String name)
	{
		for (ConfigurationParameter parameter : parameters)
			if (parameter.getName().equals(name))
				return parameter;

		throw new IllegalArgumentException("unknown configuration parameter: '" + name + "'");
	}

	public static int getIntegerParameterValue(String name)
	{
		return (Integer)getParameter(name).getValue();
	}

	public static String getPackage()
	{
		return "com.zabbix.proxy";
	}
}
