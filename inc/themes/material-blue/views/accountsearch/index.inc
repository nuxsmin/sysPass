<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $account SP\Account\AccountsSearchData
 * @var $this    SP\Core\Template
 */
?>
<?php if ($data->getData()->getDataCount() === 0): ?>
    <div class="noRes round"><?php echo _('No se encontraron registros'); ?></div>
<?php else: ?>
    <div id="res-content">
        <!-- Data Header-->
        <div id="search-header">
            <?php include $data->getDataHeaderTemplate(); ?>
        </div>

        <?php if (\SP\Account\AccountsSearchData::$topNavbar): ?>
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
    </div>
<?php endif; ?>
