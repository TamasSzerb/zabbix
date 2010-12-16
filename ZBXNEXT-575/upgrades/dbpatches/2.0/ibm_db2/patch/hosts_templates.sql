ALTER TABLE hosts_templates ALTER COLUMN hosttemplateid SET WITH DEFAULT NULL
/
REORG TABLE hosts_templates
/
ALTER TABLE hosts_templates ALTER COLUMN hostid SET WITH DEFAULT NULL
/
REORG TABLE hosts_templates
/
ALTER TABLE hosts_templates ALTER COLUMN templateid SET WITH DEFAULT NULL
/
REORG TABLE hosts_templates
/
DELETE FROM hosts_templates WHERE NOT hostid IN (SELECT hostid FROM hosts)
/
DELETE FROM hosts_templates WHERE NOT templateid IN (SELECT hostid FROM hosts)
/
ALTER TABLE hosts_templates ADD CONSTRAINT c_hosts_templates_1 FOREIGN KEY (hostid) REFERENCES hosts (hostid) ON DELETE CASCADE
/
REORG TABLE hosts_templates
/
ALTER TABLE hosts_templates ADD CONSTRAINT c_hosts_templates_2 FOREIGN KEY (templateid) REFERENCES hosts (hostid) ON DELETE CASCADE
/
REORG TABLE hosts_templates
/
