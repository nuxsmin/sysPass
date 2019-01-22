DELIMITER $$

alter table Plugin
  add versionLevel varchar(15) null $$

create table PluginData
(
  name varchar(100) not null,
  itemId int null,
  data blob not null,
  constraint `PRIMARY`
    primary key (name, itemId),
  constraint fk_PluginData_name
    foreign key (name) references Plugin (name)
      on update cascade on delete cascade
)$$