<?php
/**
 * @var \SP\Core\UI\ThemeIcons                 $icons
 * @var callable                               $_getvar
 * @var \SP\Config\ConfigData                  $configData
 * @var \SP\Services\Account\AccountSearchItem $account
 * @var \SP\Html\DataGrid\DataGrid             $data
 */

if (!isset($data)) {
    $data = $_getvar('data');
}
?>

<div id="res-content">
    <?php if ($data->getData()->getDataCount() === 0): ?>
        <div class="noRes round"><?php echo __('No se encontraron registros'); ?></div>
    <?php else: ?>
        <!-- Data Header-->
        <div id="search-header">
            <?php include $data->getDataHeaderTemplate(); ?>
        </div>

        <?php if (\SP\Services\Account\AccountSearchItem::$topNavbar): ?>
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
