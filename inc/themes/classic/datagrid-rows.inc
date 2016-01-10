<?php
/**
 * @var $data  SP\Html\DataGrid\DataGridTab
 * @var $this  SP\Core\Template
 */
?>

    <!-- Rows -->
<?php if ($data->getData()->getDataCount() === 0): ?>
    <div class="noRes round"><?php echo _('No se encontraron registros'); ?></div>
<?php else: ?>
    <?php foreach ($data->getData()->getData() as $index => $item): ?>
        <?php if ($index === 'count'): continue; endif; ?>
        <ul>
            <?php foreach ($data->getData()->getDataRowSources() as $rowSrc): ?>
                <li class="cell-data" style="width: <?php echo $data->getHeader()->getWidth(); ?>%;">
                    <?php echo (!is_null($item->$rowSrc)) ? $item->$rowSrc : '&nbsp;'; // Fix height ?>
                </li>
            <?php endforeach; ?>

            <?php if (count($data->getData()->getDataRowSourcesWithIcon()) > 0): ?>
                <li class="cell-nodata" style="width: <?php echo $data->getHeader()->getWidth(); ?>%;">
                    <?php foreach ($data->getData()->getDataRowSourcesWithIcon() as $rowSrcIcon): ?>
                        <?php if ($item->$rowSrcIcon[0] == 1): ?>
                            <img class="material-icons" title="<?php echo $rowSrcIcon[1]->getTitle(); ?>"
                                src="<?php echo $rowSrcIcon[1]->getIcon();?>" />
                        <?php endif; ?>
                    <?php endforeach; ?>
                </li>
            <?php endif; ?>

            <li class="cell-actions round">
                <?php foreach ($data->getDataActions() as $action): ?>
                    <?php if (!$action->isSkip()): ?>
                        <?php if ($action->getFilterRowSource() && $item->{$action->getFilterRowSource()} == 1): continue; endif; ?>
                        <img class="material-icons <?php echo $action->getIcon()->getClass(); ?>"
                             title="<?php echo $action->getTitle(); ?>"
                             data-itemid="<?php echo $item->{$data->getData()->getDataRowSourceId()}; ?>"
                             data-activetab="<?php echo $index; ?>"
                             data-nextactionid="<?php echo $actionId; ?>"
                             Onclick="<?php echo $action->getOnClick(); ?>"
                             src="<?php echo $action->getIcon()->getIcon(); ?>"/>
                    <?php endif; ?>
                <?php endforeach; ?>
            </li>
        </ul>
    <?php endforeach; ?>

    <!-- Pager -->
    <?php include $data->getDataPagerTemplate(); ?>

<?php endif; ?>