# This is a configuration file for Zabbix Java Proxy.
# It is sourced by startup.sh and shutdown.sh scripts.

### Option: zabbix.listenIP
#	IP address to listen on.
#
# Mandatory: no
# Default:
# LISTEN_IP="192.168.3.14"

### Option: zabbix.listenPort
#	Port to listen on.
#
# Mandatory: no
# Range: 1024-32767
# Default:
# LISTEN_PORT=10051

### Option: zabbix.pidFile
#	Name of PID file.
#	If omitted, Zabbix Java Proxy is started as a console application.
#
# Mandatory: no
# Default:
# PID_FILE=

PID_FILE="/tmp/zabbix_java.pid"

### Option: zabbix.startPollers
#	Number of worker threads to start.
#
# Mandatory: no
# Range: 1-255
# Default:
# START_POLLERS=5
