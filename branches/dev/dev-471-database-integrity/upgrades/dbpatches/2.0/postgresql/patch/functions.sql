ALTER TABLE ONLY functions ALTER itemid DROP DEFAULT;
ALTER TABLE ONLY functions ALTER triggerid DROP DEFAULT;
ALTER TABLE ONLY functions DROP COLUMN lastvalue;
DELETE FROM functions WHERE NOT itemid IN (SELECT itemid FROM items);
DELETE FROM functions WHERE NOT triggerid IN (SELECT triggerid FROM triggers);
ALTER TABLE ONLY functions ADD CONSTRAINT c_functions_1 FOREIGN KEY (itemid) REFERENCES items (itemid) ON DELETE CASCADE;
ALTER TABLE ONLY functions ADD CONSTRAINT c_functions_2 FOREIGN KEY (triggerid) REFERENCES triggers (triggerid) ON DELETE CASCADE;
