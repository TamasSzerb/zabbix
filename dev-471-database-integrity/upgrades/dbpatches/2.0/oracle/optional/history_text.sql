ALTER TABLE history_text MODIFY itemid DEFAULT NULL;
ALTER TABLE history_text ADD ns number(10) DEFAULT '0' NOT NULL;
