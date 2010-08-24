ALTER TABLE ONLY alerts ALTER alertid DROP DEFAULT,
			ALTER actionid DROP DEFAULT,
			ALTER eventid DROP DEFAULT,
			ALTER userid DROP DEFAULT,
			ALTER userid DROP NOT NULL,
			ALTER mediatypeid DROP DEFAULT,
			ALTER mediatypeid DROP NOT NULL;
UPDATE alerts SET userid=NULL WHERE userid=0;
UPDATE alerts SET mediatypeid=NULL WHERE mediatypeid=0;
DELETE FROM alerts WHERE NOT actionid IN (SELECT actionid FROM actions);
DELETE FROM alerts WHERE NOT eventid IN (SELECT eventid FROM events);
DELETE FROM alerts WHERE NOT userid IN (SELECT userid FROM users);
DELETE FROM alerts WHERE NOT mediatypeid IN (SELECT mediatypeid FROM media_type);
ALTER TABLE ONLY alerts ADD CONSTRAINT c_alerts_1 FOREIGN KEY (actionid) REFERENCES actions (actionid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE ONLY alerts ADD CONSTRAINT c_alerts_2 FOREIGN KEY (eventid) REFERENCES events (eventid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE ONLY alerts ADD CONSTRAINT c_alerts_3 FOREIGN KEY (userid) REFERENCES users (userid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE ONLY alerts ADD CONSTRAINT c_alerts_4 FOREIGN KEY (mediatypeid) REFERENCES media_type (mediatypeid) ON UPDATE CASCADE ON DELETE CASCADE;
