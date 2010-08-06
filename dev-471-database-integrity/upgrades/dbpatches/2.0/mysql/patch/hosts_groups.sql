DROP INDEX hosts_groups_1 ON hosts_groups;
ALTER TABLE hosts_groups MODIFY hostid bigint unsigned NOT NULL;
ALTER TABLE hosts_groups MODIFY groupid bigint unsigned NOT NULL;
DELETE FROM hosts_groups WHERE NOT hostid IN (SELECT hostid FROM hosts);
DELETE FROM hosts_groups WHERE NOT groupid IN (SELECT groupid FROM groups);
CREATE UNIQUE INDEX hosts_groups_1 ON hosts_groups (hostid,groupid);
ALTER TABLE hosts_groups ADD CONSTRAINT c_hosts_groups_1 FOREIGN KEY (hostid) REFERENCES hosts (hostid) ON DELETE CASCADE;
ALTER TABLE hosts_groups ADD CONSTRAINT c_hosts_groups_2 FOREIGN KEY (groupid) REFERENCES groups (groupid) ON DELETE CASCADE;
