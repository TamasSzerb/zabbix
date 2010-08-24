ALTER TABLE sysmaps MODIFY sysmapid bigint unsigned NOT NULL,
		    MODIFY width integer DEFAULT '600' NOT NULL,
		    MODIFY height integer DEFAULT '400' NOT NULL,
		    MODIFY backgroundid bigint unsigned NULL,
		    MODIFY label_type integer DEFAULT '2' NOT NULL,
		    MODIFY label_location integer DEFAULT '3' NOT NULL,
		    ADD expandproblem INTEGER DEFAULT '1' NOT NULL,
		    ADD markelements INTEGER DEFAULT '0' NOT NULL,
		    ADD show_unack INTEGER DEFAULT '0' NOT NULL;
UPDATE sysmaps SET backgroundid=NULL WHERE backgroundid=0;
UPDATE sysmaps SET show_unack=1 WHERE highlight>7 AND highlight<16;
UPDATE sysmaps SET show_unack=2 WHERE highlight>23;
UPDATE sysmaps SET highlight=(highlight-16) WHERE highlight>15;
UPDATE sysmaps SET highlight=(highlight-8) WHERE highlight>7;
UPDATE sysmaps SET markelements=1 WHERE highlight>3  AND highlight<8;
UPDATE sysmaps SET highlight=(highlight-4) WHERE highlight>3;
UPDATE sysmaps SET expandproblem=0 WHERE highlight>1 AND highlight<4;
UPDATE sysmaps SET highlight=(highlight-2) WHERE highlight>1;
ALTER TABLE sysmaps ADD CONSTRAINT c_sysmaps_1 FOREIGN KEY (backgroundid) REFERENCES images (imageid) ON UPDATE CASCADE;
