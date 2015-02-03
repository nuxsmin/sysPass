<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
 *  
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

$startTime = microtime();
$rowClass = "row_even";
$isDemoMode = SP_Util::demoIsEnabled();
$start = (isset($data['start'])) ? (int)$data['start'] : 0;

$events = SP_Log::getEvents($start);
?>

<div id="title" class="midroundup titleNormal">
    <?php echo _('Registro de Eventos'); ?>
</div>

<?php
if (!$events) {
    die('<div class="noRes round">' . _('No se encontraron registros') . '</div>');
}

$numRows = SP_Log::$numRows;
?>

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
        <?php
        foreach ($events as $log) {
            $rowClass = ($rowClass == "row_even") ? "row_odd" : "row_even";
            $description = ($isDemoMode === false) ? utf8_decode($log->log_description) : preg_replace("/\d+\.\d+\.\d+\.\d+/", "*.*.*.*", utf8_decode($log->log_description));
            ?>

            <tr class="<?php echo $rowClass; ?>">
                <td class="cell">
                    <?php echo $log->log_id; ?>
                </td>
                <td class="cell">
                    <?php echo $log->date; ?>
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
                    $descriptions = explode(';;', $description);

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
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
$endTime = microtime();
$totalTime = round($endTime - $startTime, 5);

SP_Html::printQueryLogNavBar($start, $numRows, $totalTime);
?>

<div class="action fullWidth">
    <ul>
        <li>
            <img src="imgs/clear.png" title="<?php echo _('Vaciar registro de eventos'); ?>" class="inputImg"
                 OnClick="clearEventlog('<?php echo SP_Common::getSessionKey(); ?>');"/>
        </li>
    </ul>
</div>