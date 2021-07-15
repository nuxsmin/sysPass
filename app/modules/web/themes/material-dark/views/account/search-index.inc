<?php
/**
 * @var ThemeIcons        $icons
 * @var callable          $_getvar
 * @var ConfigData        $configData
 * @var AccountSearchItem $account
 * @var DataGrid          $data
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Html\DataGrid\DataGrid;
use SP\Services\Account\AccountSearchItem;

if (!isset($data)) {
    $data = $_getvar('data');
}
?>

<div id="res-content">
    <?php if ($data->getData()->getDataCount() === 0): ?>
        <div class="noRes round"><?php echo __('No records found'); ?></div>
    <?php else: ?>
        <!-- Data Header-->
        <div id="search-header">
            <?php include $data->getDataHeaderTemplate(); ?>
        </div>

        <?php if (AccountSearchItem::$topNavbar): ?>
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
