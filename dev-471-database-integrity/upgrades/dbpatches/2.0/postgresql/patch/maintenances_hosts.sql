ALTER TABLE ONLY maintenances_hosts ALTER maintenance_hostid DROP DEFAULT,
				    ALTER maintenanceid DROP DEFAULT,
				    ALTER hostid DROP DEFAULT;
DROP INDEX maintenances_hosts_1;
DELETE FROM maintenances_hosts WHERE maintenanceid NOT IN (SELECT maintenanceid FROM maintenances);
DELETE FROM maintenances_hosts WHERE hostid NOT IN (SELECT hostid FROM hosts);
CREATE UNIQUE INDEX maintenances_hosts_1 ON maintenances_hosts (maintenanceid,hostid);
ALTER TABLE ONLY maintenances_hosts ADD CONSTRAINT c_maintenances_hosts_1 FOREIGN KEY (maintenanceid) REFERENCES maintenances (maintenanceid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE ONLY maintenances_hosts ADD CONSTRAINT c_maintenances_hosts_2 FOREIGN KEY (hostid) REFERENCES hosts (hostid) ON UPDATE CASCADE ON DELETE CASCADE;
