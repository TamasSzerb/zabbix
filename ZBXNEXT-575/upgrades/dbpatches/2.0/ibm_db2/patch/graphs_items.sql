ALTER TABLE graphs_items ALTER COLUMN gitemid SET WITH DEFAULT NULL
/
REORG TABLE graphs_items
/
ALTER TABLE graphs_items ALTER COLUMN graphid SET WITH DEFAULT NULL
/
REORG TABLE graphs_items
/
ALTER TABLE graphs_items ALTER COLUMN itemid SET WITH DEFAULT NULL
/
REORG TABLE graphs_items
/
DELETE FROM graphs_items WHERE NOT graphid IN (SELECT graphid FROM graphs)
/
DELETE FROM graphs_items WHERE NOT itemid IN (SELECT itemid FROM items)
/
ALTER TABLE graphs_items ADD CONSTRAINT c_graphs_items_1 FOREIGN KEY (graphid) REFERENCES graphs (graphid) ON DELETE CASCADE
/
REORG TABLE graphs_items
/
ALTER TABLE graphs_items ADD CONSTRAINT c_graphs_items_2 FOREIGN KEY (itemid) REFERENCES items (itemid) ON DELETE CASCADE
/
REORG TABLE graphs_items
/
