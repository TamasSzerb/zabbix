ALTER TABLE scripts MODIFY scriptid DEFAULT NULL;
ALTER TABLE scripts MODIFY usrgrpid DEFAULT NULL;
ALTER TABLE scripts MODIFY usrgrpid NULL;
ALTER TABLE scripts MODIFY groupid DEFAULT NULL;
ALTER TABLE scripts MODIFY groupid NULL;
ALTER TABLE scripts ADD description nclob DEFAULT '';
ALTER TABLE scripts ADD confirmation nvarchar2(255) DEFAULT '';
ALTER TABLE scripts ADD type number(10) DEFAULT '0' NOT NULL;
ALTER TABLE scripts ADD execute_on number(10) DEFAULT '1' NOT NULL;
UPDATE scripts SET usrgrpid=NULL WHERE usrgrpid=0;
UPDATE scripts SET groupid=NULL WHERE groupid=0;
UPDATE scripts SET type=1,command=TRIM(SUBSTR(command, 5)) WHERE SUBSTR(command, 1, 4)='IPMI';
DELETE FROM scripts WHERE usrgrpid IS NOT NULL AND usrgrpid NOT IN (SELECT usrgrpid FROM usrgrp);
DELETE FROM scripts WHERE groupid IS NOT NULL AND groupid NOT IN (SELECT groupid FROM groups);
ALTER TABLE scripts ADD CONSTRAINT c_scripts_1 FOREIGN KEY (usrgrpid) REFERENCES usrgrp (usrgrpid);
ALTER TABLE scripts ADD CONSTRAINT c_scripts_2 FOREIGN KEY (groupid) REFERENCES groups (groupid);
