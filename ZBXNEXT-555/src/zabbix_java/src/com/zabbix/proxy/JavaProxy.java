/*
** ZABBIX
** Copyright (C) 2000-2011 SIA Zabbix
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

import java.net.InetAddress;
import java.net.ServerSocket;
import java.util.concurrent.*;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

public class JavaProxy
{
	private static final Logger logger = LoggerFactory.getLogger(JavaProxy.class);

	public static void main(String[] args)
	{
		logger.info("Zabbix Java Proxy has started");

		try
		{
			ConfigurationManager.parseConfiguration();

			InetAddress listenIP = (InetAddress)ConfigurationManager.getParameter(ConfigurationManager.LISTEN_IP).getValue();
			int listenPort = ConfigurationManager.getIntegerParameterValue(ConfigurationManager.LISTEN_PORT);

			ServerSocket socket = new ServerSocket(listenPort, 0, listenIP);
			logger.info("listening on {}:{}", socket.getInetAddress(), socket.getLocalPort());

			int startPollers = ConfigurationManager.getIntegerParameterValue(ConfigurationManager.START_POLLERS);
			logger.debug("creating a thread pool of {} pollers", startPollers);
			ExecutorService threadPool = new ThreadPoolExecutor(
					startPollers,
					startPollers,
					30L, TimeUnit.SECONDS,
					new ArrayBlockingQueue<Runnable>(startPollers),
					new ThreadPoolExecutor.CallerRunsPolicy());

			while (true)
				threadPool.execute(new SocketProcessor(socket.accept()));
		}
		catch (Exception e)
		{
			logger.error("caught fatal exception", e);
		}

		logger.info("Zabbix Java Proxy has stopped");
	}
}
