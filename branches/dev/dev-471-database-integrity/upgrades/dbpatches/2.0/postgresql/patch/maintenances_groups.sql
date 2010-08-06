DROP INDEX maintenances_groups_1;
ALTER TABLE ONLY maintenances_groups ALTER maintenanceid DROP DEFAULT;
ALTER TABLE ONLY maintenances_groups ALTER groupid DROP DEFAULT;
DELETE FROM maintenances_groups WHERE maintenanceid NOT IN (SELECT maintenanceid FROM maintenances);
DELETE FROM maintenances_groups WHERE groupid NOT IN (SELECT groupid FROM groups);
CREATE UNIQUE INDEX maintenances_groups_1 ON maintenances_groups (maintenanceid,groupid);
ALTER TABLE ONLY maintenances_groups ADD CONSTRAINT c_maintenances_groups_1 FOREIGN KEY (maintenanceid) REFERENCES maintenances (maintenanceid) ON DELETE CASCADE;
ALTER TABLE ONLY maintenances_groups ADD CONSTRAINT c_maintenances_groups_2 FOREIGN KEY (groupid) REFERENCES groups (groupid) ON DELETE CASCADE;
