CREATE TABLE druleuniq (
	druleuniqid              bigint                                    	NOT NULL,
	druleid                  bigint                                    	NOT NULL,
	dcheckid                 bigint                                    	NOT NULL,
	PRIMARY KEY (druleuniqid)
) with OIDS;
CREATE UNIQUE INDEX druleuniq_1 on druleuniq (druleid);
ALTER TABLE ONLY druleuniq ADD CONSTRAINT c_druleuniq_1 FOREIGN KEY (druleid) REFERENCES drules (druleid) ON DELETE CASCADE;
ALTER TABLE ONLY druleuniq ADD CONSTRAINT c_druleuniq_2 FOREIGN KEY (dcheckid) REFERENCES dchecks (dcheckid) ON DELETE CASCADE;

INSERT INTO druleuniq (druleuniqid,druleid,dcheckid)
	SELECT druleid,druleid,unique_dcheckid FROM drules WHERE unique_dcheckid IN (SELECT dcheckid FROM dchecks);

ALTER TABLE ONLY drules ALTER druleid DROP DEFAULT,
			ALTER proxy_hostid DROP DEFAULT,
			ALTER proxy_hostid DROP NOT NULL,
			DROP unique_dcheckid;
UPDATE drules SET proxy_hostid=NULL WHERE NOT proxy_hostid IN (SELECT hostid FROM hosts);
ALTER TABLE ONLY drules ADD CONSTRAINT c_drules_1 FOREIGN KEY (proxy_hostid) REFERENCES hosts (hostid);
