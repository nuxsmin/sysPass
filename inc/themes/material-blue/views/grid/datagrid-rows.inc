<?php
/**
 * @var $data  SP\Html\DataGrid\DataGridTab
 * @var $this SP\Core\Template
 */
?>

<!-- Rows -->
<?php if ($data->getData()->getDataCount() === 0): ?>
    <div class="noRes round"><?php echo _('No se encontraron registros'); ?></div>
<?php else: ?>
    <?php foreach ($data->getData()->getData() as $dataIndex => $dataItem): ?>
        <?php if($dataIndex === 'count'): continue; endif; ?>
        <ul>
            <?php foreach ($data->getData()->getDataRowSources() as $rowSrc): ?>
                <li class="cell-data" style="width: <?php echo $data->getHeader()->getWidth(); ?>%;"><?php echo ($dataItem->{$rowSrc} !== '') ? $dataItem->{$rowSrc} : '&nbsp;'; // Fix height ?></li>
            <?php endforeach; ?>

            <?php if (count($data->getData()->getDataRowSourcesWithIcon()) > 0): ?>
                <li class="cell-nodata" style="width: <?php echo $data->getHeader()->getWidth(); ?>%;">
                    <?php foreach ($data->getData()->getDataRowSourcesWithIcon() as $rowSrcIcon): ?>
                        <?php if ($dataItem->{$rowSrcIcon[0]} == 1): ?>
                            <i class="material-icons <?php echo $rowSrcIcon[1]->getClass(); ?>" title="<?php echo $rowSrcIcon[1]->getTitle(); ?>">
                                <?php echo $rowSrcIcon[1]->getIcon(); ?>
                            </i>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </li>
            <?php endif; ?>

            <li class="cell-actions round">
                <?php foreach ($data->getDataActions() as $action): ?>
                    <?php if (!$action->isSkip()): ?>
                        <?php if ($action->getFilterRowSource() && $dataItem->{$action->getFilterRowSource()} == 1): continue; endif; ?>
                        <i class="btn-action material-icons <?php echo $action->getIcon()->getClass(); ?>"
                           title="<?php echo $action->getTitle(); ?>"
                           data-action-id="<?php echo $action->getId(); ?>"
                           data-nextaction-id="<?php echo $actionId; ?>"
                           data-item-id="<?php echo $dataItem->{$data->getData()->getDataRowSourceId()}; ?>"
                           data-activetab="<?php echo $index; ?>"
                           data-onclick="<?php echo $action->getOnClick(); ?>"><?php echo $action->getIcon()->getIcon(); ?></i>
                    <?php endif; ?>
                <?php endforeach; ?>
            </li>
        </ul>
    <?php endforeach; ?>

    <!-- Pager -->
    <?php include $data->getDataPagerTemplate(); ?>
<?php endif; ?>

<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>
