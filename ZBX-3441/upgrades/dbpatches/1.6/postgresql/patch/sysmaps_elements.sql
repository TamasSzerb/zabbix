alter table sysmaps_elements add iconid_disabled         bigint         DEFAULT '0'     NOT NULL;
update sysmaps_elements set iconid_disabled=iconid_off;
