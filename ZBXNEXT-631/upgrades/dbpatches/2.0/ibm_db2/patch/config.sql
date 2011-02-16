ALTER TABLE config ALTER COLUMN configid SET WITH DEFAULT NULL
/
REORG TABLE config
/
ALTER TABLE config ALTER COLUMN alert_usrgrpid SET WITH DEFAULT NULL
/
REORG TABLE config
/
ALTER TABLE config ALTER COLUMN alert_usrgrpid DROP NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ALTER COLUMN discovery_groupid SET WITH DEFAULT NULL
/
REORG TABLE config
/
ALTER TABLE config ALTER COLUMN default_theme SET WITH DEFAULT 'css_ob.css'
/
REORG TABLE config
/
ALTER TABLE config ADD ns_support integer WITH DEFAULT '0' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_color_0 char(6) SET WITH DEFAULT 'DBDBDB' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_color_1 char(6) SET WITH DEFAULT 'D6F6FF' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_color_2 char(6) SET WITH DEFAULT 'FFF6A5' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_color_3 char(6) SET WITH DEFAULT 'FFB689' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_color_4 char(6) SET WITH DEFAULT 'FF9999' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_color_5 char(6) SET WITH DEFAULT 'FF3838' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_name_0 varchar(32) SET WITH DEFAULT 'Not classified' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_name_1 varchar(32) SET WITH DEFAULT 'Information' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_name_2 varchar(32) SET WITH DEFAULT 'Warning' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_name_3 varchar(32) SET WITH DEFAULT 'Average' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_name_4 varchar(32) SET WITH DEFAULT 'High' NOT NULL
/
REORG TABLE config
/
ALTER TABLE config ADD severity_name_5 varchar(32) SET WITH DEFAULT 'Disaster' NOT NULL
/
REORG TABLE config
/
UPDATE config SET alert_usrgrpid=NULL WHERE NOT alert_usrgrpid IN (SELECT usrgrpid FROM usrgrp)
/
UPDATE config SET discovery_groupid=NULL WHERE NOT discovery_groupid IN (SELECT groupid FROM groups)
/
UPDATE config SET default_theme='css_ob.css' WHERE default_theme='default.css'
/
ALTER TABLE config ADD CONSTRAINT c_config_1 FOREIGN KEY (alert_usrgrpid) REFERENCES usrgrp (usrgrpid)
/
ALTER TABLE config ADD CONSTRAINT c_config_2 FOREIGN KEY (discovery_groupid) REFERENCES groups (groupid)
/
