ALTER TABLE ONLY httptest ALTER httptestid DROP DEFAULT,
			  ALTER applicationid DROP DEFAULT;
DELETE FROM httptest WHERE NOT EXISTS (SELECT 1 FROM applications WHERE applications.applicationid=httptest.applicationid);
ALTER TABLE ONLY httptest ADD CONSTRAINT c_httptest_1 FOREIGN KEY (applicationid) REFERENCES applications (applicationid) ON DELETE CASCADE;
