ALTER TABLE ids ALTER COLUMN nodeid SET WITH DEFAULT NULL;
REORG TABLE ids;
ALTER TABLE ids ALTER COLUMN nextid SET WITH DEFAULT NULL;
REORG TABLE ids;
