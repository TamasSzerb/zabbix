ALTER TABLE ONLY functions ALTER functionid DROP DEFAULT,
			   ALTER itemid DROP DEFAULT,
			   ALTER triggerid DROP DEFAULT,
			   DROP COLUMN lastvalue;
DELETE FROM functions WHERE NOT itemid IN (SELECT itemid FROM items);
DELETE FROM functions WHERE NOT triggerid IN (SELECT triggerid FROM triggers);
ALTER TABLE ONLY functions ADD CONSTRAINT c_functions_1 FOREIGN KEY (itemid) REFERENCES items (itemid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE ONLY functions ADD CONSTRAINT c_functions_2 FOREIGN KEY (triggerid) REFERENCES triggers (triggerid) ON UPDATE CASCADE ON DELETE CASCADE;
