ALTER TABLE profiles ALTER COLUMN profileid SET WITH DEFAULT NULL
/
REORG TABLE profiles
/
ALTER TABLE profiles ALTER COLUMN userid SET WITH DEFAULT NULL
/
REORG TABLE profiles
/
DELETE FROM profiles WHERE NOT userid IN (SELECT userid FROM users)
/
DELETE FROM profiles WHERE idx LIKE 'web.%.sort' OR idx LIKE 'web.%.sortorder'
/
ALTER TABLE profiles ADD CONSTRAINT c_profiles_1 FOREIGN KEY (userid) REFERENCES users (userid) ON DELETE CASCADE
/
