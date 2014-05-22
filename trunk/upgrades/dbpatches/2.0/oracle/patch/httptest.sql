ALTER TABLE httptest MODIFY httptestid DEFAULT NULL;
ALTER TABLE httptest MODIFY applicationid DEFAULT NULL;
ALTER TABLE httptest DROP COLUMN lastcheck;
ALTER TABLE httptest DROP COLUMN curstate;
ALTER TABLE httptest DROP COLUMN curstep;
ALTER TABLE httptest DROP COLUMN lastfailedstep;
ALTER TABLE httptest DROP COLUMN time;
ALTER TABLE httptest DROP COLUMN error;
DELETE FROM httptest WHERE applicationid NOT IN (SELECT applicationid FROM applications);
ALTER TABLE httptest ADD CONSTRAINT c_httptest_1 FOREIGN KEY (applicationid) REFERENCES applications (applicationid) ON DELETE CASCADE;
