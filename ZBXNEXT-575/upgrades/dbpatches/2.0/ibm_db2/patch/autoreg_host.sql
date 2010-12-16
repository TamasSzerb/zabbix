ALTER TABLE autoreg_host ALTER COLUMN autoreg_hostid SET WITH DEFAULT NULL
/
ALTER TABLE autoreg_host ALTER COLUMN proxy_hostid SET WITH DEFAULT NULL
/
ALTER TABLE autoreg_host ALTER COLUMN proxy_hostid DROP NOT NULL
/
ALTER TABLE autoreg_host ADD listen_ip varchar(39) WITH DEFAULT '' NOT NULL
/
ALTER TABLE autoreg_host ADD listen_port integer WITH DEFAULT '0' NOT NULL
/
REORG TABLE autoreg_host
/
UPDATE autoreg_host SET proxy_hostid=NULL WHERE proxy_hostid=0
/
DELETE FROM autoreg_host WHERE NOT proxy_hostid IS NULL AND NOT proxy_hostid IN (SELECT hostid FROM hosts)
/
ALTER TABLE autoreg_host ADD CONSTRAINT c_autoreg_host_1 FOREIGN KEY (proxy_hostid) REFERENCES hosts (hostid) ON DELETE CASCADE
/
REORG TABLE autoreg_host
/
