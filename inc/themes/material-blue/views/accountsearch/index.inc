<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $account SP\Account\AccountsSearchItem
 * @var $this    SP\Core\Template
 */
?>

<div id="res-content">
    <?php if ($data->getData()->getDataCount() === 0): ?>
        <div class="noRes round"><?php echo __('No se encontraron registros'); ?></div>
    <?php else: ?>
        <!-- Data Header-->
        <div id="search-header">
            <?php include $data->getDataHeaderTemplate(); ?>
        </div>

        <?php if (\SP\Account\AccountsSearchItem::$topNavbar): ?>
            <!-- Data Pager -->
            <div id="search-pager-top">
                <?php include $data->getDataPagerTemplate(); ?>
            </div>
        <?php endif; ?>

        <!-- Data Rows-->
        <div id="search-rows">
            <?php include $data->getDataRowTemplate(); ?>
        </div>

        <!-- Data Pager -->
        <div id="search-pager-bottom">
            <?php include $data->getDataPagerTemplate(); ?>
        </div>
    <?php endif; ?>
</div>
