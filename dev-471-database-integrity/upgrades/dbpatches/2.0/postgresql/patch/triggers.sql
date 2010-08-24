ALTER TABLE ONLY triggers ALTER triggerid DROP DEFAULT,
			  ALTER templateid DROP DEFAULT,
			  ALTER templateid DROP NOT NULL;
UPDATE triggers SET templateid=NULL WHERE templateid=0;
UPDATE triggers SET templateid=NULL WHERE NOT templateid IS NULL AND NOT templateid IN (SELECT triggerid FROM triggers);
ALTER TABLE ONLY triggers ADD CONSTRAINT c_triggers_1 FOREIGN KEY (templateid) REFERENCES triggers (triggerid) ON UPDATE CASCADE ON DELETE CASCADE;
