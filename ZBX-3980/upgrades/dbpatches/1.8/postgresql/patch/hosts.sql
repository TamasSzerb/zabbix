alter table hosts add maintenanceid bigint DEFAULT '0' NOT NULL;
alter table hosts add maintenance_status integer DEFAULT '0' NOT NULL;
alter table hosts add maintenance_type integer DEFAULT '0' NOT NULL;
alter table hosts add maintenance_from integer DEFAULT '0' NOT NULL;
alter table hosts add ipmi_ip varchar(64) DEFAULT '127.0.0.1' NOT NULL;
alter table hosts add ipmi_errors_from integer DEFAULT '0' NOT NULL;
alter table hosts add snmp_errors_from integer DEFAULT '0' NOT NULL;
alter table hosts add ipmi_error varchar(128) DEFAULT '' NOT NULL;
alter table hosts add snmp_error varchar(128) DEFAULT '' NOT NULL;
ALTER TABLE hosts ALTER COLUMN inbytes TYPE numeric(20);
ALTER TABLE hosts ALTER COLUMN inbytes SET DEFAULT '0';
ALTER TABLE hosts ALTER COLUMN inbytes SET NOT NULL;
ALTER TABLE hosts ALTER COLUMN outbytes TYPE numeric(20);
ALTER TABLE hosts ALTER COLUMN outbytes SET DEFAULT '0';
ALTER TABLE hosts ALTER COLUMN outbytes SET NOT NULL;
