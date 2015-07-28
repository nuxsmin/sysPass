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
$hash = SP_Common::parseParams('g', 'h');
$time = SP_Common::parseParams('g', 't');
$forced = SP_Common::parseParams('g', 'f', 0);

$passReset = ($action === 'passreset' && $hash && $time);
?>

<div id="actions" align="center">

    <?php if (isset($data['showlogo'])): ?>
        <div id="logo">
            <img src="<?php echo SP_Init::$WEBROOT; ?>/imgs/logo_full.svg" alt="sysPass logo"/>
        </div>
    <?php endif; ?>

    <?php if (SP_Util::mailIsEnabled() || $forced === 1) { ?>
        <form id="passreset" action="" method="post"
              onsubmit="sendAjax($(this).serialize(),'/ajax/ajax_passReset.php'); return false;">
            <fieldset id="resetdata">
                <legend><?php echo _('Solicitud de Cambio de Clave'); ?></legend>
                <?php if (!$passReset): ?>
                    <p>
                        <input type="text" name="login" id="login" title="<?php echo _('Login del Usuario'); ?>"
                               placeholder="<?php echo _('Usuario'); ?> "
                               value="<?php echo SP_Util::init_var('login'); ?>" autocomplete="off" autofocus required/>
                    </p>
                    <p>
                        <input type="text" name="email" id="email" title="<?php echo _('Email del Usuario'); ?>"
                               placeholder="<?php echo _('Email'); ?>  "
                               value="<?php echo SP_Util::init_var('email'); ?>" autocomplete="off" autofocus required/>
                    </p>
                <?php else: ?>
                    <p>
                        <input type="password" name="pass" id="pass" title="<?php echo _('Nueva Clave'); ?>"
                               placeholder="<?php echo _('Clave'); ?>" value="<?php echo SP_Util::init_var('pass'); ?>"
                               onKeyUp="checkPassLevel(this.value)" required/>
                        <span class="passLevel fullround"
                              title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
                    </p>
                    <p>
                        <input type="password" name="passv" id="passv"
                               title="<?php echo _('Nueva Clave (Verificar)'); ?>"
                               placeholder="<?php echo _('Clave (Verificar)'); ?>"
                               value="<?php echo SP_Util::init_var('passv'); ?>" required/>
                        <span class="passLevel fullround"
                              title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
                    </p>
                    <input type="hidden" name="time" value="<?php echo $time; ?>">
                    <input type="hidden" name="hash" value="<?php echo $hash; ?>">
                <?php endif; ?>
                <input type="hidden" name="isAjax" value="1">
                <input type="hidden" name="sk" value="<?php echo SP_Common::getSessionKey(true); ?>">
            </fieldset>

            <div class="buttons">
                <?php echo SP_Html::anchorText(_('Volver'), 'index.php', _('Volver a iniciar sesión'), 'class="button round5"'); ?>
                <?php if (!$passReset): ?>
                    <input type="submit" class="button round5" value="<?php echo _('Solicitar'); ?>"
                           title="<?php echo _('Solicitar cambio de clave'); ?>"/>
                <?php else: ?>
                    <input type="submit" class="button round5" value="<?php echo _('Cambiar'); ?>"
                           title="<?php echo _('Cambiar Clave'); ?>"/>
                <?php endif; ?>
            </div>
        </form>
    <?php
    } else {
        SP_Html::showCommonError('unavailable');
    } ?>
</div>