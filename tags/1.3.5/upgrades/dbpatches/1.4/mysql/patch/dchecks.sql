CREATE TABLE dchecks (
        dcheckid                bigint unsigned         DEFAULT '0'     NOT NULL,
        druleid         bigint unsigned         DEFAULT '0'     NOT NULL,
        type            integer         DEFAULT '0'     NOT NULL,
        ports           varchar(255)            DEFAULT '0'     NOT NULL,
        PRIMARY KEY (dcheckid)
) type=InnoDB;
