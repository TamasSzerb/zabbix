DROP INDEX trigger_depends_1 ON trigger_depends;
ALTER TABLE trigger_depends MODIFY triggerid_down bigint unsigned NOT NULL;
ALTER TABLE trigger_depends MODIFY triggerid_up bigint unsigned NOT NULL;
DELETE FROM trigger_depends WHERE triggerid_down NOT IN (SELECT triggerid FROM triggers);
DELETE FROM trigger_depends WHERE triggerid_up NOT IN (SELECT triggerid FROM triggers);
CREATE UNIQUE INDEX trigger_depends_1 ON trigger_depends (triggerid_down,triggerid_up);
ALTER TABLE trigger_depends ADD CONSTRAINT c_trigger_depends_1 FOREIGN KEY (triggerid_down) REFERENCES triggers (triggerid) ON DELETE CASCADE;
ALTER TABLE trigger_depends ADD CONSTRAINT c_trigger_depends_2 FOREIGN KEY (triggerid_up) REFERENCES triggers (triggerid) ON DELETE CASCADE;
