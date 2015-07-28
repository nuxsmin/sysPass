<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

$action = SP_Common::parseParams('g', 'a');
$time = SP_Common::parseParams('g', 't');

$upgrade = ($action === 'upgrade');
?>

<div id="actions" class="upgrade" align="center">

    <?php if (isset($data['showlogo'])): ?>
        <div id="logo">
            <img src="<?php echo SP_Init::$WEBROOT; ?>/imgs/logo_full.svg" alt="sysPass logo"/>
        </div>
    <?php endif; ?>

    <form id="upgrade" action="index.php" method="get">
        <fieldset>
            <legend><?php echo _('Actualización de BBDD'); ?></legend>
            <p>
                <input type="text" name="h" id="hash" title="<?php echo _('Introducir Código de Seguridad'); ?>"
                       placeholder="<?php echo _('Código de Seguridad'); ?> "
                       value="" autocomplete="off" autofocus required/>
            </p>
            <input type="hidden" name="a" value="<?php echo $action; ?>">
            <input type="hidden" name="t" value="<?php echo $time; ?>">
            <input type="hidden" name="upgrade" value="1">
        </fieldset>

        <div class="buttons">
            <input type="submit" class="button round5" value="<?php echo _('Actualizar'); ?>"
                   title="<?php echo _('Iniciar Actualización'); ?>"/>
        </div>
    </form>
</div>
