CREATE TABLE actions_tmp (
	actionid		bigint unsigned		DEFAULT '0'	NOT NULL,
	userid		bigint unsigned		DEFAULT '0'	NOT NULL,
	subject		varchar(255)		DEFAULT ''	NOT NULL,
	message		blob		DEFAULT ''	NOT NULL,
	recipient		integer		DEFAULT '0'	NOT NULL,
	maxrepeats		integer		DEFAULT '0'	NOT NULL,
	repeatdelay		integer		DEFAULT '600'	NOT NULL,
	source		integer		DEFAULT '0'	NOT NULL,
	actiontype		integer		DEFAULT '0'	NOT NULL,
	status		integer		DEFAULT '0'	NOT NULL,
	scripts		blob		DEFAULT ''	NOT NULL,
	PRIMARY KEY (actionid)
);

insert into actions_tmp select * from actions;
drop table actions;
alter table actions_tmp rename actions;
