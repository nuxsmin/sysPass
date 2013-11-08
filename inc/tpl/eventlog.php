<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
$isDemoMode = SP_Config::getValue('demoenabled',0);
$start = ( isset($data['start']) ) ? (int)$data['start'] : 0;

$events = SP_Log::getEvents($start);
?>

<div id="title" class="midroundup titleNormal">
    <? echo _('Registro de Eventos'); ?>
</div>

<? 
if ( ! $events ) {
    die('<div class="noRes round">'._('No se encontraron registros').'</div>');
}

$numRows = SP_Log::$numRows;
?>

<div id="resEventLog">
    <table class="data round">
        <thead>
            <tr class="header-grey">
                <th>
                    <? echo _('ID'); ?>
                </th>
                <th>
                    <? echo _('Fecha / Hora'); ?>
                </th>
                <th>
                    <? echo _('Evento'); ?>
                </th>
                <th>
                    <? echo _('Usuario'); ?>
                </th>
                <th class="cell-description">
                    <? echo _('Descripción'); ?>
                </th>
            </tr>
        </thead>
        <tbody id="resSearch">
            <? foreach ( $events as $log ):
                $rowClass = ( $rowClass == "row_even" ) ? "row_odd" : "row_even";
                $description = ( $isDemoMode === 0 ) ? utf8_decode($log->log_description) : preg_replace("/\d+\.\d+\.\d+\.\d+/", "*.*.*.*", utf8_decode($log->log_description));
            ?>

            <tr class="<? echo $rowClass; ?>">
                <td class="cell">
                    <? echo $log->log_id; ?>
                </td>
                <td class="cell">
                    <? echo $log->date; ?>
                </td>
                <td class="cell">
                    <? echo utf8_decode($log->log_action); ?>
                </td>
                <td class="cell">
                    <? echo strtoupper($log->log_login); ?>
                </td>
                <td class="cell-description">
                    <? 
                    $descriptions = explode(';;', $description);
                    
                    foreach ( $descriptions as $text ){
                        if ( strlen($text) >= 300){
                            echo wordwrap($text, 300, '<br>', TRUE);
                        } else {
                            echo $text.'<br>';
                        }
                    }
                    ?>
                </td>
            </tr>
            <? endforeach; ?>
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
            <img src="imgs/clear.png" title="<? echo _('Vaciar registro de eventos'); ?>" class="inputImg" OnClick="clearEventlog('<? echo SP_Common::getSessionKey(); ?>');" />
        </li>
    </ul>
</div>