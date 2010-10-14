----
---- Patching table `events`
----

DROP INDEX events_2 ON events;
CREATE INDEX events_2 ON events (clock);
ALTER TABLE events MODIFY eventid bigint unsigned NOT NULL,
		   ADD ns integer DEFAULT '0' NOT NULL,
		   ADD value_changed integer DEFAULT '0' NOT NULL;

-- Begin event redesign patch

CREATE TEMPORARY TABLE tmp_events_eventid (eventid bigint unsigned PRIMARY KEY);
CREATE INDEX tmp_events_index on events (source, object, objectid, value, eventid);

-- Which OK events should have value_changed flag set?
-- Those that have a PROBLEM event (or no event) before them.

INSERT INTO tmp_events_eventid (eventid)
(
	SELECT e1.eventid
		FROM events e1
		WHERE e1.source=0							-- EVENT_SOURCE_TRIGGERS
			AND e1.object=0 						-- EVENT_OBJECT_TRIGGER
			AND e1.value=0							-- TRIGGER_VALUE_FALSE
			AND (SELECT e2.value
				FROM events e2
				WHERE e2.source=e1.source
					AND e2.object=e1.object
					AND e2.objectid=e1.objectid
					AND e2.value<>2					-- TRIGGER_VALUE_UNKNOWN
					AND e2.eventid<e1.eventid
				ORDER BY e2.eventid DESC
				LIMIT 1)<>e1.value 					-- (NULL) or TRIGGER_VALUE_TRUE
);

-- Which PROBLEM events should have value_changed flag set?
-- (1) Those that have an OK event (or no event) before them.
-- (2) Those that came from a "MULTIPLE PROBLEM" trigger.

INSERT INTO tmp_events_eventid (eventid)
(
	SELECT e1.eventid
		FROM events e1, triggers t
		WHERE e1.source=0							-- EVENT_SOURCE_TRIGGERS
			AND e1.object=0 						-- EVENT_OBJECT_TRIGGER
			AND e1.objectid=t.triggerid
			AND e1.value=1							-- TRIGGER_VALUE_TRUE
			AND
				(
					(t.type=0					-- TRIGGER_TYPE_NORMAL
						AND (SELECT e2.value
							FROM events e2
							WHERE e2.source=e1.source
								AND e2.object=e1.object
								AND e2.objectid=e1.objectid
								AND e2.value<>2		-- TRIGGER_VALUE_UNKNOWN
								AND e2.eventid<e1.eventid
							ORDER BY e2.eventid DESC
							LIMIT 1)<>e1.value) 		-- (NULL) or TRIGGER_VALUE_FALSE
					OR
					(t.type=1)					-- TRIGGER_TYPE_MULTIPLE_TRUE
				)
);

-- Update the value_changed flag.

DROP INDEX tmp_events_index on events;

UPDATE events SET value_changed=1 WHERE eventid IN (SELECT eventid FROM tmp_events_eventid);

DROP TABLE tmp_events_eventid;

-- End event redesign patch

----
---- Patching table `triggers`
----

ALTER TABLE triggers MODIFY triggerid bigint unsigned NOT NULL,
		     MODIFY templateid bigint unsigned NULL,
		     DROP dep_level,
		     ADD value_flags integer DEFAULT '0' NOT NULL;
UPDATE triggers SET templateid=NULL WHERE templateid=0;
CREATE TEMPORARY TABLE tmp_triggers_triggerid (triggerid bigint unsigned PRIMARY KEY);
INSERT INTO tmp_triggers_triggerid (triggerid) (SELECT triggerid FROM triggers);
UPDATE triggers SET templateid=NULL WHERE NOT templateid IS NULL AND NOT templateid IN (SELECT triggerid FROM tmp_triggers_triggerid);
DROP TABLE tmp_triggers_triggerid;
ALTER TABLE triggers ADD CONSTRAINT c_triggers_1 FOREIGN KEY (templateid) REFERENCES triggers (triggerid) ON DELETE CASCADE;

-- Begin event redesign patch

CREATE TEMPORARY TABLE tmp_triggers (triggerid bigint unsigned PRIMARY KEY, eventid bigint unsigned);

INSERT INTO tmp_triggers (triggerid, eventid)
(
	SELECT t.triggerid, MAX(e.eventid)
		FROM triggers t, events e
		WHERE t.value=2							-- TRIGGER_VALUE_UNKNOWN
			AND e.source=0						-- EVENT_SOURCE_TRIGGERS	
			AND e.object=0						-- EVENT_OBJECT_TRIGGER
			AND e.objectid=t.triggerid
			AND e.value<>2						-- TRIGGER_VALUE_UNKNOWN
		GROUP BY t.triggerid
);

UPDATE triggers t1, tmp_triggers t2, events e
	SET t1.value=e.value,
		t1.value_flags=1
	WHERE t1.triggerid=t2.triggerid
		AND t2.eventid=e.eventid;

UPDATE triggers
	SET value=0,								-- TRIGGER_VALUE_FALSE
		value_flags=1
	WHERE value=2;								-- TRIGGER_VALUE_UNKNOWN

DROP TABLE tmp_triggers;

-- End event redesign patch
