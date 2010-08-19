ALTER TABLE opmediatypes MODIFY opmediatypeid bigint unsigned NOT NULL,
			 MODIFY operationid bigint unsigned NOT NULL,
			 MODIFY mediatypeid bigint unsigned NOT NULL;
DELETE FROM opmediatypes WHERE operationid NOT IN (SELECT operationid FROM operations);
DELETE FROM opmediatypes WHERE mediatypeid NOT IN (SELECT mediatypeid FROM media_type);
ALTER TABLE opmediatypes ADD CONSTRAINT c_opmediatypes_1 FOREIGN KEY (operationid) REFERENCES operations (operationid) ON DELETE CASCADE;
ALTER TABLE opmediatypes ADD CONSTRAINT c_opmediatypes_2 FOREIGN KEY (mediatypeid) REFERENCES media_type (mediatypeid) ON DELETE CASCADE;
