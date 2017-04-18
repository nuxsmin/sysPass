<?php
/** @var $icons \Theme\Icons */
/** @var \SP\Html\DataGrid\DataGridPagerBase $Pager */
?>

<div id="title" class="midroundup titleNormal">
    <?php echo __('Registro de Eventos'); ?>
</div>

<?php if (!$events): ?>
    <?php die('<div class="noRes round">' . __('No se encontraron registros') . '</div>'); ?>
<?php endif; ?>

<div id="resEventLog">
    <table class="data round">
        <thead>
        <tr class="header-grey">
            <th>
                <?php echo __('ID'); ?>
            </th>
            <th>
                <?php echo __('Fecha / Hora'); ?>
            </th>
            <th>
                <?php echo __('Nivel'); ?>

            <th>
                <?php echo __('Evento'); ?>
            </th>
            <th>
                <?php echo __('Usuario'); ?>
            </th>
            <th>
                <?php echo __('IP'); ?>
            </th>
            <th class="cell-description">
                <?php echo __('Descripción'); ?>
            </th>
        </tr>
        </thead>
        <tbody id="resSearch">
        <?php foreach ($events as $log): ?>
            <?php $rowClass = ($rowClass === "row_even") ? "row_odd" : "row_even"; ?>
            <?php $description = ($isDemoMode === false) ? utf8_decode($log->log_description) : preg_replace("/\d+\.\d+\.\d+\.\d+/", "*.*.*.*", utf8_decode($log->log_description)); ?>

            <tr class="<?php echo $rowClass; ?>">
                <td class="cell">
                    <?php echo $log->log_id; ?>
                </td>
                <td class="cell">
                    <?php echo $log->log_date; ?>
                </td>
                <td class="cell">
                    <?php echo $log->log_level; ?>
                </td>
                <td class="cell">
                    <?php echo utf8_decode($log->log_action); ?>
                </td>
                <td class="cell">
                    <?php echo mb_strtoupper($log->log_login); ?>
                </td>
                <td class="cell">
                    <?php echo $isDemoMode ? preg_replace('#\d+#', '*', $log->log_ipAddress) : $log->log_ipAddress; ?>
                </td>
                <td class="cell-description">
                    <?php
                    $description = str_replace(';;', PHP_EOL, $description);
                    $descriptions = explode(PHP_EOL, $description);

                    foreach ($descriptions as $text) {
                        if (preg_match('/^SQL.*/', $text)) {
                            $text = preg_replace('/([[:alpha:]_]+),/', '\\1,<br>', $text);
                            $text = preg_replace('/(UPDATE|DELETE|TRUNCATE|INSERT|SELECT|WHERE|LEFT|ORDER|LIMIT|FROM)/', '<br>\\1', $text);
                        }

                        if (strlen($text) >= 150) {
                            echo wordwrap($text, 150, '<br>', true);
                        } else {
                            echo $text . '<br>';
                        }
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="pager round5 shadow">
    <div class="pager-left">
        <?php echo $Pager->getTotalRows(); ?> @ <?php echo round(microtime() - $queryTimeStart, 5); ?>s
    </div>
    <div class="pager-right">
        <?php if ($Pager->getLimitStart() > 1): ?>
            <i class="btn-action-pager material-icons <?php echo $Pager->getIconFirst()->getClass(); ?>"
               title="<?php echo $Pager->getIconFirst()->getTitle(); ?>"
               data-action-id="<?php echo $Pager->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $Pager->getSourceAction()->getName(); ?>"
               data-start="<?php echo $Pager->getFirst(); ?>"
               data-count="<?php echo $Pager->getLimitCount(); ?>"
               data-onclick="<?php echo $Pager->getOnClick(); ?>"><?php echo $Pager->getIconFirst()->getIcon(); ?></i>
            <i class="btn-action-pager material-icons <?php echo $Pager->getIconPrev()->getClass(); ?>"
               title="<?php echo $Pager->getIconPrev()->getTitle(); ?>"
               data-action-id="<?php echo $Pager->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $Pager->getSourceAction()->getName(); ?>"
               data-start="<?php echo $Pager->getPrev(); ?>"
               data-count="<?php echo $Pager->getLimitCount(); ?>"
               data-onclick="<?php echo $Pager->getOnClick(); ?>"><?php echo $Pager->getIconPrev()->getIcon(); ?></i>
        <?php endif; ?>
        &nbsp;
        <?php echo $Pager->getFirstPage(), '/', $Pager->getLastPage(); ?>
        &nbsp;
        <?php if ($Pager->getLimitStart() < $Pager->getTotalRows() && $Pager->getFirstPage() != $Pager->getLastPage()): ?>
            <i class="btn-action-pager material-icons <?php echo $Pager->getIconNext()->getClass(); ?>"
               title="<?php echo $Pager->getIconNext()->getTitle(); ?>"
               data-action-id="<?php echo $Pager->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $Pager->getSourceAction()->getName(); ?>"
               data-start="<?php echo $Pager->getNext(); ?>"
               data-count="<?php echo $Pager->getLimitCount(); ?>"
               data-onclick="<?php echo $Pager->getOnClick(); ?>"><?php echo $Pager->getIconNext()->getIcon(); ?></i>
            <i class="btn-action-pager material-icons <?php echo $Pager->getIconLast()->getClass(); ?>"
               title="<?php echo $Pager->getIconLast()->getTitle(); ?>"
               data-action-id="<?php echo $Pager->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $Pager->getSourceAction()->getName(); ?>"
               data-start="<?php echo $Pager->getLast(); ?>"
               data-count="<?php echo $Pager->getLimitCount(); ?>"
               data-onclick="<?php echo $Pager->getOnClick(); ?>"><?php echo $Pager->getIconLast()->getIcon(); ?></i>
        <?php endif; ?>
    </div>
</div>

<div class="item-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                    title="<?php echo __('Atrás'); ?>">
                <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button
                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconClear()->getClassButton(); ?>"
                title="<?php echo __('Vaciar registro de eventos'); ?>"
                data-onclick="eventlog/clear"
                data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_EVL; ?>">
                <i class="material-icons"><?php echo $icons->getIconClear()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>