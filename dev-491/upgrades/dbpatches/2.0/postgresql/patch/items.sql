ALTER TABLE ONLY items ALTER itemid DROP DEFAULT,
		       ALTER hostid DROP DEFAULT,
		       ALTER units TYPE varchar(255),
		       ALTER templateid DROP DEFAULT,
		       ALTER templateid DROP NOT NULL,
		       ALTER valuemapid DROP DEFAULT,
		       ALTER valuemapid DROP NOT NULL,
		       ADD lastns integer NULL,
			   ADD interfaceid bigint NULL;
UPDATE items SET templateid=NULL WHERE templateid=0;
UPDATE items SET templateid=NULL WHERE NOT templateid IS NULL AND NOT templateid IN (SELECT itemid FROM items);
UPDATE items SET valuemapid=NULL WHERE valuemapid=0;
UPDATE items SET valuemapid=NULL WHERE NOT valuemapid IS NULL AND NOT valuemapid IN (SELECT valuemapid from valuemaps);
UPDATE items SET units='Bps' WHERE type=9 AND units='bps';
DELETE FROM items WHERE NOT hostid IN (SELECT hostid FROM hosts);
ALTER TABLE ONLY items ADD CONSTRAINT c_items_1 FOREIGN KEY (hostid) REFERENCES hosts (hostid) ON DELETE CASCADE;
ALTER TABLE ONLY items ADD CONSTRAINT c_items_2 FOREIGN KEY (templateid) REFERENCES items (itemid) ON DELETE CASCADE;
ALTER TABLE ONLY items ADD CONSTRAINT c_items_3 FOREIGN KEY (valuemapid) REFERENCES valuemaps (valuemapid);
ALTER TABLE ONLY items ADD CONSTRAINT c_items_4 FOREIGN KEY (interfaceid) REFERENCES interface (interfaceid);
UPDATE items SET interfaceid=(SELECT interfaceid FROM interface WHERE hostid=items.hostid AND itemtype=0 AND main=1) WHERE type<>12;
UPDATE items SET interfaceid=(SELECT interfaceid FROM interface WHERE hostid=items.hostid AND itemtype=12 AND main=1) WHERE type=12;