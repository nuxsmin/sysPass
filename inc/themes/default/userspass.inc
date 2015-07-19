<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo _('Cambio de Clave'); ?></h2>
    <form method="post" name="updUsrPass" id="frmUpdUsrPass">
        <table class="fancydata">
        <tr>
            <td class="descField">
                <?php echo _('Clave'); ?>
            </td>
            <td class="valField">
                <input type="password" id="usrpass" name="pass" title="<?php echo _('Clave'); ?>" class="txtpass" OnFocus="$('#passLevel').show(); $('#resFancyAccion').hide();" OnKeyUp="checkPassLevel(this.value, 'fancyContainer')" />
                <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                     alt="<?php echo _('Mostrar Clave'); ?>"/>
                <img id="passGen" src="imgs/genpass.png" title="<?php echo _('Generar clave aleatoria'); ?>" class="inputImg" />
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo _('Clave (repetir)'); ?></td>
            <td class="valField">
                <input type="password" id="usrpassv" name="passv" title="<?php echo _('Clave (repetir)'); ?>" class="txtpassv" />
                <span class="passLevel fullround" title="<?php echo _('Nivel de fortaleza de la clave'); ?>" ></span>
            </td>
        </tr>
    </table>
    <input type="hidden" name="itemId" value="<?php echo $userId; ?>" />
    <input type="hidden" name="actionId" value="<?php echo $actionId; ?>" />
    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
</form>

    <div id="resCheck">
        <span id="resFancyAccion"></span>
    </div>
    <div class="action-in-box">
        <ul>
            <li>
                <img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" OnClick="appMgmtSave('frmUpdUsrPass')" alt="<?php echo _('Guardar'); ?>"/>
            </li>
        </ul>
    </div>
</div>