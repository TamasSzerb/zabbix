ALTER TABLE ONLY graphs
	ALTER graphid DROP DEFAULT,
			ALTER templateid DROP DEFAULT,
			ALTER templateid DROP NOT NULL,
			ALTER ymin_itemid DROP DEFAULT,
			ALTER ymin_itemid DROP NOT NULL,
			ALTER ymax_itemid DROP DEFAULT,
			ALTER ymax_itemid DROP NOT NULL,
			ALTER show_legend SET DEFAULT 1,
			ADD flags integer DEFAULT '0' NOT NULL;
UPDATE graphs SET show_legend=1 WHERE graphtype=0 OR graphtype=1;
UPDATE graphs SET templateid=NULL WHERE templateid=0;
UPDATE graphs SET templateid=NULL WHERE NOT templateid IS NULL AND NOT templateid IN (SELECT graphid FROM graphs);
UPDATE graphs SET ymin_itemid=NULL WHERE ymin_itemid=0 OR NOT ymin_itemid IN (SELECT itemid FROM items);
UPDATE graphs SET ymax_itemid=NULL WHERE ymax_itemid=0 OR NOT ymax_itemid IN (SELECT itemid FROM items);
UPDATE graphs SET ymin_type=0 WHERE ymin_type=2 AND ymin_itemid=NULL;
UPDATE graphs SET ymax_type=0 WHERE ymax_type=2 AND ymax_itemid=NULL;
ALTER TABLE ONLY graphs ADD CONSTRAINT c_graphs_1 FOREIGN KEY (templateid) REFERENCES graphs (graphid) ON DELETE CASCADE;
ALTER TABLE ONLY graphs ADD CONSTRAINT c_graphs_2 FOREIGN KEY (ymin_itemid) REFERENCES items (itemid);
ALTER TABLE ONLY graphs ADD CONSTRAINT c_graphs_3 FOREIGN KEY (ymax_itemid) REFERENCES items (itemid);
