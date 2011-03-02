ALTER TABLE auditlog ALTER COLUMN auditid SET WITH DEFAULT NULL
/
REORG TABLE auditlog
/
ALTER TABLE auditlog ALTER COLUMN userid SET WITH DEFAULT NULL
/
REORG TABLE auditlog
/
DELETE FROM auditlog WHERE NOT userid IN (SELECT userid FROM users)
/
ALTER TABLE auditlog ADD CONSTRAINT c_auditlog_1 FOREIGN KEY (userid) REFERENCES users (userid) ON DELETE CASCADE
/
