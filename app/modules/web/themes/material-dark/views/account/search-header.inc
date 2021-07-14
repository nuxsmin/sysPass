<?php
/**
 * @var ThemeIcons        $icons
 * @var callable          $_getvar
 * @var ConfigData        $configData
 * @var AccountSearchItem $account
 * @var DataGridTab       $data
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Html\DataGrid\DataGridTab;
use SP\Services\Account\AccountSearchItem;

if (!isset($data)) {
    $data = $_getvar('data');
}
?>

<div id="data-search-header" class="data-header data-header-minimal">
    <ul>
        <?php foreach ($data->getHeader()->getSortFields() as $field): ?>
            <li>
                <a id="btn-order-<?php echo $field->getSortKey(); ?>"
                   class="round <?php echo ($data->getPager()->getSortKey() == $field->getSortKey()) ? 'filterOn' : ''; ?>">
                    <?php echo $field->getName(); ?>
                </a>
                <span for="btn-order-<?php echo $field->getSortKey(); ?>"
                      class="mdl-tooltip mdl-tooltip--bottom"><?php echo $field->getTitle(); ?></span>
                <?php
                $classDown = ($data->getPager()->getSortKey() == $field->getSortKey()
                    && $data->getPager()->getSortOrder() === 0) ? 'mdl-color--amber-A200' : 'md-dark md-inactive';
                $classUp = ($data->getPager()->getSortKey() == $field->getSortKey()
                    && $data->getPager()->getSortOrder() === 1) ? 'mdl-color--amber-A200' : 'md-dark md-inactive';
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
        <li class="search-filter-spacer"></li>
    </ul>
</div>