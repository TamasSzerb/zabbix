ALTER TABLE ONLY hostmacro ALTER hostmacroid DROP DEFAULT,
			   ALTER hostid DROP DEFAULT;
DROP INDEX hostmacro_1;
DELETE FROM hostmacro WHERE NOT EXISTS (SELECT 1 FROM hosts WHERE hosts.hostid=hostmacro.hostid);
CREATE UNIQUE INDEX hostmacro_1 ON hostmacro (hostid,macro);
ALTER TABLE ONLY hostmacro ADD CONSTRAINT c_hostmacro_1 FOREIGN KEY (hostid) REFERENCES hosts (hostid) ON DELETE CASCADE;
