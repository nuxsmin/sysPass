DELIMITER $$

alter table CustomFieldData
    drop column id$$

alter table CustomFieldData
    add primary key (moduleId, itemId, definitionId)$$

alter table CustomFieldData
    drop foreign key fk_CustomFieldData_definitionId$$

alter table CustomFieldData
    add constraint fk_CustomFieldData_definitionId
        foreign key (definitionId) references CustomFieldDefinition (id)
            on delete cascade$$

alter table AuthToken
    add column expireDate int(10) unsigned DEFAULT NULL$$
