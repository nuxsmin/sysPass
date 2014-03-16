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

//SP_Html::$htmlBodyOpts = 'onload="document.frmLogin.user.focus();"';

?>

<div id="boxLogin" class="round shadow">
    <div id="boxLogo"><img id="imgLogo" src="imgs/logo.png" title="sysPass"/></div>
    <div id="boxData">
        <form method="post" name="frmLogin" id="frmLogin" action="" OnSubmit="return doLogin();">
        
        <?php if ( SP_Config::getValue("demoenabled",0) ): ?>
            <input type="text" name="user" id="user" placeholder="<?php echo _('Usuario'); ?>" value="" title="> demo <"/><br />
            <input type="password" name="pass" id="pass" placeholder="<?php echo _('Clave'); ?>" value="" title="> syspass <"/><br />
            <span id="smpass" style="display: none"><input type="password" name="mpass" id="mpass" placeholder="<?php echo _('Clave Maestra'); ?>" value="" title="> 01234567890 <" disabled/><br /></span>
        <?php else: ?>
            <input type="text" name="user" id="user" placeholder="<?php echo  _('Usuario'); ?>" value="" /><br />
            <input type="password" name="pass" id="pass" placeholder="<?php echo _('Clave'); ?>" value="" /><br />
            <span id="smpass" style="display: none"><input type="password" name="mpass" id="mpass" placeholder="<?php echo _('Clave Maestra'); ?>" value="" disabled/><br /></span>
        <?php endif; ?>

            <input id="btnLogin" type="image" src="imgs/login.png" name="login" title="<?php echo _('Acceder') ?>" />
        </form>
    </div><!-- Close boxData -->
</div><!-- Close boxLogin -->

<?php if( SP_Common::parseParams('g', 'logout', FALSE, TRUE) ): ?>
<div id="boxLogout"><?php echo _('Sesión finalizada'); ?></div>
<script>$('#boxLogout').fadeOut(5000);</script>
<?php endif; ?>