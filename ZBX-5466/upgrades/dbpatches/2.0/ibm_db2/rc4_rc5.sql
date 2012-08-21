ALTER TABLE config ADD server_check_interval integer WITH DEFAULT '60' NOT NULL
/
REORG TABLE config
/
ALTER TABLE items ALTER COLUMN lastlogsize SET DATA TYPE bigint
/
REORG TABLE items
/
INSERT INTO graph_theme (graphthemeid, description, theme, backgroundcolor, graphcolor, graphbordercolor, gridcolor, maingridcolor, gridbordercolor, textcolor, highlightcolor, leftpercentilecolor, rightpercentilecolor, nonworktimecolor, gridview, legendview) VALUES ((SELECT MAX(graphthemeid) + 1 FROM graph_theme), 'Dark orange', 'darkorange', '333333', '0A0A0A', '888888', '222222', '4F4F4F', 'EFEFEF', 'DFDFDF', 'FF5500', 'FF5500', 'FF1111', '1F1F1F', 1, 1)
/
INSERT INTO graph_theme (graphthemeid, description, theme, backgroundcolor, graphcolor, graphbordercolor, gridcolor, maingridcolor, gridbordercolor, textcolor, highlightcolor, leftpercentilecolor, rightpercentilecolor, nonworktimecolor, gridview, legendview) VALUES ((SELECT MAX(graphthemeid) + 1 FROM graph_theme), 'Classic', 'classic', 'F0F0F0', 'FFFFFF', '333333', 'CCCCCC', 'AAAAAA', '000000', '222222', 'AA4444', '11CC11', 'CC1111', 'E0E0E0', 1, 1)
/
DELETE FROM ids WHERE table_name = 'graph_theme'
/
