<?php
/**
 * @var SP\Html\DataGrid\DataGridTab $data
 * @var Template                     $this
 * @var callable                     $_getvar
 */

use SP\Html\Html;
use SP\Mvc\View\Template;

if (!isset($data)) {
    $data = $_getvar('data');
}
?>

    <!-- Rows -->
<?php if ($data->getData()->getDataCount() > 0):
    $numFields = count($data->getData()->getDataRowSources());
    foreach ($data->getData()->getData() as $dataIndex => $dataItem):
        $itemId = $dataItem->{$data->getData()->getDataRowSourceId()};
        ?>
        <tr data-item-id="<?php echo $itemId; ?>">
            <?php foreach ($data->getData()->getDataRowSources() as $rowSrc): ?>
                <?php
                if ($rowSrc['isMethod'] === true
                    && method_exists($dataItem, $rowSrc['name'])
                ):
                    $value = $dataItem->{$rowSrc['name']}();
                else:
                    $value = $dataItem->{$rowSrc['name']};
                endif;

                if ($rowSrc['filter'] !== null):
                    $value = $rowSrc['filter']($value);
                endif;

                if ($value !== ''):
                    if ($rowSrc['truncate'] === true):
                        $value = Html::truncate($value, 150 / $numFields);
                    endif;
                else:
                    $value = '&nbsp;';
                endif;
                ?>
                <td class="cell-data"><?php echo $value; ?></td>
            <?php endforeach; ?>

            <?php
            $rowIcons = $data->getData()->getDataRowSourcesWithIcon();

            if (count($rowIcons) > 0): ?>
                <td class="cell-nodata">
                    <?php foreach ($rowIcons as $rowSrcIcon): ?>
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
                                $actionUid = uniqid('btn-action-', true);
                                $filter = $action->getFilterRowSource();

                                if ($filter !== null):
                                    /** @var array $filter */
                                    foreach ($filter as $f):
                                        if ($dataItem->{$f['field']} == $f['value']):
                                            continue 2;
                                        endif;
                                    endforeach;
                                endif;

                                if ($action->getRuntimeData() !== null) :
                                    $nodeData = array_merge($action->getData(), $action->getRuntimeData()($dataItem));
                                else:
                                    $nodeData = $action->getData();
                                endif;
                                ?>
                                <i id="<?php echo $actionUid; ?>"
                                   class="btn-action material-icons <?php echo $action->getIcon()->getClass(); ?>"
                                   data-item-id="<?php echo $itemId; ?>"
                                   data-onclick="<?php echo $action->getOnClick(); ?>"
                                    <?php foreach ($nodeData as $dataName => $dataValue):
                                        echo 'data-', $dataName, '=', '"', $dataValue, '"';
                                    endforeach; ?>><?php echo $action->getIcon()->getIcon(); ?></i>
                                <span
                                        for="<?php echo $actionUid; ?>"
                                        class="mdl-tooltip mdl-tooltip--top"><?php echo $action->getTitle(); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>