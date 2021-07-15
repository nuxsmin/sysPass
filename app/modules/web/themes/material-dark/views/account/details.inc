<?php
/**
 * @var ThemeIcons     $icons
 * @var callable       $_getvar
 * @var AccountExtData $accountData
 * @var AccountAcl     $accountAcl
 * @var ConfigData     $configData
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\AccountExtData;
use SP\Services\Account\AccountAcl;

?>

<div id="data-details">
    <table class="data">
        <tr>
            <td class="descField"><?php echo __('Visits'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Visits'); ?></div>

                <?php echo $accountData->getCountView() . '(' . $accountData->getCountDecrypt() . ')'; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Date Added'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Date Added'); ?></div>

                <?php echo $accountData->getDateAdd() ?>
            </td>
        </tr>

        <?php if ($_getvar('isModified') && $accountData->getUserEditName()): ?>
            <tr>
                <td class="descField"><?php echo __('Last Modification'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Last Modification'); ?></div>

                    <?php printf('%s (%s)', $accountData->getDateEdit(), $accountData->getUserEditName()); ?>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <td class="descField"><?php echo __('Owner'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Owner'); ?></div>

                <?php echo $accountData->getUserName() ?: $accountData->getUserLogin(); ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Main Group'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Main Group'); ?></div>

                <?php echo $accountData->getUserGroupName(); ?>
            </td>
        </tr>

        <?php if ($accountAcl->isModified()): ?>
            <tr>
                <td class="descField"><?php echo __('Date Modified'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Date Modified'); ?></div>

                    <?php echo $accountData->getDateEdit(); ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Editor'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Editor'); ?></div>

                    <?php echo $accountData->getUserEditName() ?: $accountData->getUserEditLogin(); ?>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <td class="descField"><?php echo __('Password Date'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Password Date'); ?></div>

                <?php echo $_getvar('accountPassDate'); ?>
            </td>
        </tr>

        <?php if ($_getvar('accountPassDateChange', 0) > 0): ?>
            <tr>
                <td class="descField"><?php echo __('Password Expiry Date'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Password Expiry Date'); ?></div>

                    <?php echo $_getvar('accountPassDateChange'); ?>
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="descField"><?php echo __('ID'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('ID'); ?></div>

                <?php echo $accountData->getId(); ?>
            </td>
        </tr>
    </table>
</div>
