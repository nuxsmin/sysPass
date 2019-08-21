DELIMITER $$

alter table Plugin
  add versionLevel varchar(15) null $$

create table PluginData
(
    name   varchar(100)    not null,
    itemId int             not null,
    `data` blob            not null,
    `key`  varbinary(2000) not null,
    primary key (name, itemId),
    constraint fk_PluginData_name
        foreign key (name) references Plugin (name)
            on update cascade
            on delete cascade
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE utf8_unicode_ci $$