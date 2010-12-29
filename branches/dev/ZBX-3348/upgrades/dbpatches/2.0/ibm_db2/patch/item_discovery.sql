CREATE TABLE item_discovery (
	itemdiscoveryid          bigint                                    NOT NULL,
	itemid                   bigint                                    NOT NULL,
	parent_itemid            bigint                                    NOT NULL,
	key_                     varchar(255)    WITH DEFAULT ''           NOT NULL,
	PRIMARY KEY (itemdiscoveryid)
);
REORG TABLE item_discovery;
CREATE UNIQUE INDEX item_discovery_1 on item_discovery (itemid,parent_itemid);
REORG TABLE item_discovery;
ALTER TABLE item_discovery ADD CONSTRAINT c_item_discovery_1 FOREIGN KEY (itemid) REFERENCES items (itemid) ON DELETE CASCADE;
REORG TABLE item_discovery;
ALTER TABLE item_discovery ADD CONSTRAINT c_item_discovery_2 FOREIGN KEY (parent_itemid) REFERENCES items (itemid) ON DELETE CASCADE;
REORG TABLE item_discovery;
