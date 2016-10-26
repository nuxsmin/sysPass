<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $account SP\Account\AccountsSearchData
 * @var $this    SP\Core\Template
 */
?>
<div id="data-search-header" class="data-header data-header-minimal">
    <ul>
        <?php foreach ($data->getHeader()->getSortFields() as $field): ?>
            <li>
                <a class="round <?php echo ($data->getPager()->getSortKey() == $field->getSortKey()) ? 'filterOn' : ''; ?>"
                   title="<?php echo $field->getTitle(); ?>">
                    <?php echo $field->getName(); ?>
                </a>
                <?php
                $classDown = ($data->getPager()->getSortKey() == $field->getSortKey() && $data->getPager()->getSortOrder() === 0) ? 'mdl-color--amber-A200' : 'md-dark md-inactive';
                $classUp = ($data->getPager()->getSortKey() == $field->getSortKey() && $data->getPager()->getSortOrder() === 1) ? 'mdl-color--amber-A200' : 'md-dark md-inactive';
                ?>
                <div
                    class="icon material-icons sort-down cursor-pointer <?php echo $classDown; ?>"
                    data-start="<?php echo $data->getPager()->getLimitStart(); ?>"
                    data-key="<?php echo $field->getSortKey(); ?>"
                    data-dir="0">
                    <?php echo $field->getIconDown()->getIcon(); ?>
                </div>
                <div
                    class="icon material-icons sort-up cursor-pointer <?php echo $classUp; ?>"
                    data-start="<?php echo $data->getPager()->getLimitStart(); ?>"
                    data-key="<?php echo $field->getSortKey(); ?>"
                    data-dir="1">
                    <?php echo $field->getIconUp()->getIcon(); ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>