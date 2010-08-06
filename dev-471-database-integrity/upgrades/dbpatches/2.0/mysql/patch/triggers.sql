ALTER TABLE triggers MODIFY templateid bigint unsigned NULL;
UPDATE triggers SET templateid=NULL WHERE templateid=0;
--UPDATE triggers SET templateid=NULL WHERE NOT templateid IS NULL AND NOT templateid IN (SELECT triggerid FROM triggers);
ALTER TABLE triggers ADD CONSTRAINT c_triggers_1 FOREIGN KEY (templateid) REFERENCES triggers (triggerid) ON DELETE CASCADE;
