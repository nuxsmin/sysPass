<?php /** @var $icons \Theme\Icons */ ?>

<div id="title" class="midroundup titleNormal">
    <?php echo _('Registro de Eventos'); ?>
</div>

<?php if (!$events): ?>
    <?php die('<div class="noRes round">' . _('No se encontraron registros') . '</div>'); ?>
<?php endif; ?>

<div id="resEventLog">
    <table class="data round">
        <thead>
        <tr class="header-grey">
            <th>
                <?php echo _('ID'); ?>
            </th>
            <th>
                <?php echo _('Fecha / Hora'); ?>
            </th>
            <th>
                <?php echo _('Nivel'); ?>

            <th>
                <?php echo _('Evento'); ?>
            </th>
            <th>
                <?php echo _('Usuario'); ?>
            </th>
            <th>
                <?php echo _('IP'); ?>
            </th>
            <th class="cell-description">
                <?php echo _('Descripción'); ?>
            </th>
        </tr>
        </thead>
        <tbody id="resSearch">
        <?php foreach ($events as $log): ?>
            <?php $rowClass = ($rowClass == "row_even") ? "row_odd" : "row_even"; ?>
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
                    <?php echo strtoupper($log->log_login); ?>
                </td>
                <td class="cell">
                    <?php echo ($isDemoMode) ? preg_replace('#\d+#', '*', $log->log_ipAddress) : $log->log_ipAddress; ?>
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
<div id="pageNav" class="round shadow">
    <div id="pageNavLeft">
        <?php echo $totalRows; ?> @ <?php echo round(microtime() - $queryTimeStart, 5); ?>s
    </div>
    <div id="pageNavRight">
        <?php if ($limitStart > 1): ?>
            <img src="imgs/arrow_first.png" onClick="<?php echo $pagerOnClick['first']; ?>"
                 title="<?php echo _('Primera página'); ?>"/>
            <img src="imgs/arrow_left.png" onClick="<?php echo $pagerOnClick['prev']; ?>"
                 title="<?php echo _('Página anterior'); ?>"/>
        <?php endif; ?>
        &nbsp;<?php echo $firstPage, '/', $lastPage; ?>&nbsp;
        <?php if ($limitStart < $totalRows && $firstPage != $lastPage): ?>
            <img src="imgs/arrow_right.png" onClick="<?php echo $pagerOnClick['next']; ?>"
                 title="<?php echo _('Página siguiente'); ?>"/>
            <img src="imgs/arrow_last.png" onClick="<?php echo $pagerOnClick['last']; ?>"
                 title="<?php echo _('Última página'); ?>"/>
        <?php endif; ?>
    </div>
</div>
<div class="action fullWidth">
    <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconClear()->getClassButton(); ?>"
            title="<?php echo _('Vaciar registro de eventos'); ?>" OnClick="sysPassUtil.Common.clearEventlog('<?php echo $sk; ?>');">
        <i class="material-icons"><?php echo $icons->getIconClear()->getIcon(); ?></i>
    </button>
</div>