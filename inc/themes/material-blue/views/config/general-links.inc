<?php /** @var $icons \Theme\Icons */ ?>

<div id="title" class="midroundup titleNormal">
    <?php echo __('Enlaces Públicos'); ?>
</div>

<table id="tblFiles" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo __('Habilitar Enlaces Públicos'); ?>
            <div id="help-publinks"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-publinks">
                <p>
                    <?php echo __('Habilita la posibilidad de generar enlaces públicos para ver los detalles de una cuenta.'); ?>
                </p>

                <p>
                    <?php echo __('Las cuentas enlazadas serán visibles por cualquiera que disponga del enlace.'); ?>
                </p>

                <p>
                    <?php echo __('Para crear enlaces, los usuarios tienen que tener activada la opción en su perfl.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="publinks_enabled">
                <input type="checkbox" id="publinks_enabled" class="mdl-switch__input mdl-color-text--indigo-400"
                       name="publinks_enabled"
                    <?php echo $chkPubLinks; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Tiempo de caducidad'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="publinks_maxtime" name="publinks_maxtime" type="number" step="5"
                       pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="5"
                       value="<?php echo $pubLinksMaxTime; ?>"/>
                <label class="mdl-textfield__label"
                       for="publinks_maxtime"><?php echo __('Tiempo en minutos'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Número máximo  de visitas'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="publinks_maxviews" name="publinks_maxviews" type="number" step="1"
                       pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="5"
                       value="<?php echo $pubLinksMaxViews; ?>"/>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Usar imagen para clave'); ?>
            <div id="help-publinksimage"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-publinksimage">
                <p>
                    <?php echo __('La clave de la cuenta es visualizada como una imagen.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="publinks_image_enabled">
                <input type="checkbox" id="publinks_image_enabled" class="mdl-switch__input mdl-color-text--indigo-400"
                       name="publinks_image_enabled"
                    <?php echo $chkPubLinksImage; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
</table>