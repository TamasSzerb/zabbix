ALTER TABLE ONLY history_str ALTER itemid DROP DEFAULT;
ALTER TABLE ONLY history_str ADD ns integer DEFAULT '0' NOT NULL;
