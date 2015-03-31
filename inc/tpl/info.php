<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

$activeTab = $data['activeTab'];
$onCloseAction = $data['onCloseAction'];
?>

<div id="title" class="midroundup titleNormal">
    <?php echo _('Información de la Aplicación'); ?>
</div>

<table class="data round">
    <tr>
        <td class="descField">
            <?php echo _('Versión sysPass'); ?>
        </td>
        <td class="valField">
            <?php echo implode('.', SP_Util::getVersion(true)); ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Base de Datos'); ?>
        </td>
        <td class="valField">
            <?php
            foreach (DB::getDBinfo() as $infoattr => $infoval) {
                echo $infoattr, ': ', $infoval, '<br><br>';
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('PHP'); ?>
        </td>
        <td class="valField">
            <?php
            echo _('Versión'), ': ', phpversion(), '<br><br>';
            echo _('Extensiones'), ': ', wordwrap(implode(', ', get_loaded_extensions()), 75, '<br>'), '<br><br>';
            echo _('Memoria'), ': ', (memory_get_usage(true) / 1024), ' KB<br><br>';
            echo _('Usuario'), ': ', get_current_user(), '<br><br>';
            ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Servidor'); ?>
        </td>
        <td class="valField">
            <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
        </td>
    </tr>
</table>