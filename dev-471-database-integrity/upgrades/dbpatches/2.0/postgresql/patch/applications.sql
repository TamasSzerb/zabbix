ALTER TABLE ONLY applications ALTER applicationid DROP DEFAULT,
			      ALTER hostid DROP DEFAULT,
			      ALTER templateid DROP DEFAULT,
			      ALTER templateid DROP NOT NULL;
DELETE FROM applications WHERE NOT hostid IN (SELECT hostid FROM hosts);
UPDATE applications SET templateid=NULL WHERE templateid=0;
UPDATE applications SET templateid=NULL WHERE NOT templateid IS NULL AND NOT templateid IN (SELECT applicationid FROM applications);
ALTER TABLE ONLY applications ADD CONSTRAINT c_applications_1 FOREIGN KEY (hostid) REFERENCES hosts (hostid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE ONLY applications ADD CONSTRAINT c_applications_2 FOREIGN KEY (templateid) REFERENCES applications (applicationid) ON UPDATE CASCADE ON DELETE CASCADE;
