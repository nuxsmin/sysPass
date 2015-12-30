<div id="title" class="midroundup titleNormal">
    <?php echo _('Enlaces Públicos'); ?>
</div>

<table id="tblFiles" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Habilitar Enlaces Públicos'); ?>
            <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
            <div class="tooltip" for="help-publinks" style="display: none;">
                <p>
                    <?php echo _('Habilita la posibilidad de generar enlaces públicos para ver los detalles de una cuenta.'); ?>
                </p>

                <p>
                    <?php echo _('Las cuentas enlazadas serán visibles por cualquiera que disponga del enlace.'); ?>
                </p>

                <p>
                    <?php echo _('Para crear enlaces, los usuarios tienen que tener activada la opción en su perfl.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label for="publinks_enabled"><?php echo ($chkPubLinks) ? _('SI') : _('NO'); ?></label>
            <input type="checkbox" name="publinks_enabled" id="publinks_enabled"
                   class="checkbox" <?php echo $chkPubLinks, ' ', $isDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Tiempo de caducidad'); ?>
        </td>
        <td class="valField">
            <input type="number" name="publinks_maxtime"
                   pattern="[0-9]{1,5}" step="5" maxlength="5"
                   value="<?php echo $pubLinksMaxTime; ?>" title="<?php echo _('Tiempo en minutos'); ?>"/>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Número máximo  de visitas'); ?>
        </td>
        <td class="valField">
            <input type="number" name="publinks_maxviews"
                   pattern="[0-9]{1,5}" step="1" maxlength="5"
                   value="<?php echo $pubLinksMaxViews; ?>" title="<?php echo _('Tiempo en minutos'); ?>"/>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Usar imagen para clave'); ?>
            <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
            <div class="tooltip" for="help-publinksimage" style="display: none;">
                <p>
                    <?php echo _('La clave de la cuenta es visualizada como una imagen.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label for="publinks_image_enabled"><?php echo ($chkPubLinksImage) ? _('SI') : _('NO'); ?></label>
            <input type="checkbox" name="publinks_image_enabled" id="publinks_image_enabled"
                   class="checkbox" <?php echo $chkPubLinksImage, ' ', $isDisabled; ?> />
        </td>
    </tr>
</table>