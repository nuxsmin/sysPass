<?php
/**
 * @var $data  SP\Html\DataGrid\DataGridTab
 * @var $this  SP\Core\Template
 */
use SP\Html\Html;

?>

    <!-- Rows -->
<?php if ($data->getData()->getDataCount() > 0): ?>
    <?php foreach ($data->getData()->getData() as $dataIndex => $dataItem): ?>
        <?php if ($dataIndex === 'count'): continue; endif; ?>

        <?php $numFields = count($data->getData()->getDataRowSources()); ?>

        <tr data-item-id="<?php echo $dataItem->{$data->getData()->getDataRowSourceId()}; ?>"
            data-activetab="<?php echo $index; ?>">
            <?php foreach ($data->getData()->getDataRowSources() as $rowSrc): ?>
                <td class="cell-data"><?php echo ($dataItem->{$rowSrc} !== '') ? Html::truncate($dataItem->{$rowSrc}, 100 / $numFields) : '&nbsp;'; // Fix height ?></td>
            <?php endforeach; ?>

            <?php if (count($data->getData()->getDataRowSourcesWithIcon()) > 0): ?>
                <td class="cell-nodata">
                    <?php foreach ($data->getData()->getDataRowSourcesWithIcon() as $rowSrcIcon): ?>
                        <?php if ($dataItem->{$rowSrcIcon['field']} == $rowSrcIcon['value']): ?>
                            <i class="material-icons <?php echo $rowSrcIcon['icon']->getClass(); ?>"
                               title="<?php echo $rowSrcIcon['icon']->getTitle(); ?>"><?php echo $rowSrcIcon['icon']->getIcon(); ?></i>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </td>
            <?php endif; ?>

            <td class="cell-actions">
                <div>
                    <?php if (count($data->getDataActions()) > 0): ?>
                        <?php foreach ($data->getDataActions() as $action): ?>
                            <?php if (!$action->isSkip()):
                                $filter = $action->getFilterRowSource();

                                if ($filter !== null) {
                                    /** @var array $filter */
                                    foreach ($filter as $f) {
                                        if ($dataItem->{$f['field']} == $f['value']) {
                                            continue 2;
                                        }
                                    }
                                }
                                ?>
                                <i id="btn-action-<?php echo $dataItem->{$data->getData()->getDataRowSourceId()}, '-', $action->getId(); ?>"
                                   class="btn-action material-icons <?php echo $action->getIcon()->getClass(); ?>"
                                   data-action-id="<?php echo $action->getId(); ?>"
                                   data-nextaction-id="<?php echo $actionId; ?>"
                                   data-item-id="<?php echo $dataItem->{$data->getData()->getDataRowSourceId()}; ?>"
                                   data-activetab="<?php echo $index; ?>"
                                   data-onclick="<?php echo $action->getOnClick(); ?>"><?php echo $action->getIcon()->getIcon(); ?></i>
                                <span
                                        for="btn-action-<?php echo $dataItem->{$data->getData()->getDataRowSourceId()}, '-', $action->getId(); ?>"
                                        class="mdl-tooltip mdl-tooltip--top"><?php echo $action->getTitle(); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        &nbsp;
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>