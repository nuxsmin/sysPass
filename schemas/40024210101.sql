DELIMITER $$

alter table CustomFieldData
    drop column id$$

alter table CustomFieldData
    add primary key (moduleId, itemId, definitionId)$$
