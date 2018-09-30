<?php
/**
 * @var $data  SP\Html\DataGrid\DataGridTab
 * @var $this  \SP\Mvc\View\Template
 */

use SP\Html\Html;

?>

    <!-- Rows -->
<?php if ($data->getData()->getDataCount() > 0):
    $numFields = count($data->getData()->getDataRowSources());
    foreach ($data->getData()->getData() as $dataIndex => $dataItem): ?>
        <tr data-item-id="<?php echo $dataItem->{$data->getData()->getDataRowSourceId()}; ?>">
            <?php foreach ($data->getData()->getDataRowSources() as $rowSrc): ?>
                <?php
                if ($rowSrc['isMethod'] === true && method_exists($dataItem, $rowSrc['name'])):
                    $value = $dataItem->{$rowSrc['name']}();
                else:
                    $value = $dataItem->{$rowSrc['name']};
                endif;

                $value = $rowSrc['filter'] !== null ? $rowSrc['filter']($value) : $value;

                if ($value !== ''):
                    $value = $rowSrc['truncate'] === true ? Html::truncate($value, 150 / $numFields) : $value;
                else:
                    $value = '&nbsp;';
                endif;
                ?>
                <td class="cell-data"><?php echo $value; ?></td>
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

            <?php if ($data->getDataActionsCount() > 0): ?>
                <td class="cell-actions">
                    <div>
                        <?php foreach ($data->getDataActions() as $action):
                            if (!$action->isSkip()):
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
                                   data-item-id="<?php echo $dataItem->{$data->getData()->getDataRowSourceId()}; ?>"
                                   data-onclick="<?php echo $action->getOnClick(); ?>"
                                    <?php foreach ($action->getData() as $dataName => $dataValue): echo 'data-', $dataName, '=', '"', $dataValue, '"'; endforeach; ?>
                                ><?php echo $action->getIcon()->getIcon(); ?></i>
                                <span
                                        for="btn-action-<?php echo $dataItem->{$data->getData()->getDataRowSourceId()}, '-', $action->getId(); ?>"
                                        class="mdl-tooltip mdl-tooltip--top"><?php echo $action->getTitle(); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>