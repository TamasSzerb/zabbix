ALTER TABLE ONLY history_log ALTER id DROP DEFAULT,
			     ALTER itemid DROP DEFAULT,
			     ADD ns integer DEFAULT '0' NOT NULL;
