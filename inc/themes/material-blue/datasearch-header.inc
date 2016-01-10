<?php
/**
 * @var $data  SP\Html\DataGrid\DataGridTab
 * @var $account SP\Controller\AccountsSearchData
 * @var $this SP\Core\Template
 */
?>
<div id="data-search-header" class="data-header data-header-minimal">
    <ul>
        <?php foreach ($data->getHeader()->getSortFields() as $field): ?>
            <li data-key="<?php echo $field->getSortKey(); ?>">
                <a class="round <?php echo ($data->getPager()->getSortKey() == $field->getSortKey()) ? 'filterOn' : ''; ?>"
                   title="<?php echo $field->getTitle(); ?>">
                    <?php echo $field->getName(); ?>
                </a>
                <div
                    class="icon material-icons sort-down cursor-pointer <?php echo ($data->getPager()->getSortKey() == $field->getSortKey() && $data->getPager()->getSortOrder() === 0) ? 'mdl-color--amber-A200' : 'md-dark md-inactive'; ?>">
                    <?php echo $field->getIconDown()->getIcon(); ?>
                </div>
                <div
                    class="icon material-icons sort-up cursor-pointer <?php echo ($data->getPager()->getSortKey() == $field->getSortKey() && $data->getPager()->getSortOrder() === 1) ? 'mdl-color--amber-A200' : 'md-dark md-inactive'; ?>">
                    <?php echo $field->getIconUp()->getIcon(); ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>