ALTER TABLE trigger_depends MODIFY triggerdepid bigint unsigned NOT NULL,
			    MODIFY triggerid_down bigint unsigned NOT NULL,
			    MODIFY triggerid_up bigint unsigned NOT NULL;
DROP INDEX trigger_depends_1 ON trigger_depends;
DELETE FROM trigger_depends WHERE triggerid_down NOT IN (SELECT triggerid FROM triggers);
DELETE FROM trigger_depends WHERE triggerid_up NOT IN (SELECT triggerid FROM triggers);
-- remove duplicates to allow unique index
DELETE trigger_depends
	FROM trigger_depends
	LEFT OUTER JOIN (
		SELECT MIN(triggerdepid) AS triggerdepid
		FROM trigger_depends
		GROUP BY triggerid_down,triggerid_up
	) keep_rows ON
		trigger_depends.triggerdepid=keep_rows.triggerdepid
	WHERE keep_rows.triggerdepid IS NULL;
CREATE UNIQUE INDEX trigger_depends_1 ON trigger_depends (triggerid_down,triggerid_up);
ALTER TABLE trigger_depends ADD CONSTRAINT c_trigger_depends_1 FOREIGN KEY (triggerid_down) REFERENCES triggers (triggerid) ON DELETE CASCADE;
ALTER TABLE trigger_depends ADD CONSTRAINT c_trigger_depends_2 FOREIGN KEY (triggerid_up) REFERENCES triggers (triggerid) ON DELETE CASCADE;
