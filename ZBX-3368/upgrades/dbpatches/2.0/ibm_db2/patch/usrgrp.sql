ALTER TABLE usrgrp ALTER COLUMN usrgrpid SET WITH DEFAULT NULL;
REORG TABLE usrgrp;
