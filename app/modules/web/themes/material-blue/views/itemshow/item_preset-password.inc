<?php /** @var $password \SP\DataModel\ItemPreset\Password */ ?>

<table class="popup-data">
    <tbody>
    <tr>
        <td class="descField"><?php echo __('Longitud'); ?></td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="timeout" name="length" type="number"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       min="1" max="100"
                       value="<?php echo $password->getLength() ?: 1; ?>"/>
                <label class="mdl-textfield__label"
                       for="length"><?php echo __('Longitud'); ?></label>
            </div>
        </td>
    </tr>

    <tr>
        <td class="descField"><?php echo __('Caducidad clave cuentas'); ?></td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="expire_time" name="expire_time" type="number"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       min="0"
                       value="<?php echo $password->getExpireTime() ? $password->getExpireTime() / $expireTimeMultiplier : 1; ?>"/>
                <label class="mdl-textfield__label"
                       for="expire_time"><?php echo __('Tiempo caducidad (días)'); ?></label>
            </div>
        </td>
    </tr>

    <tr>
        <td class="descField"><?php echo __('Puntuación'); ?></td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="score" name="score" type="number"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       min="0" max="5"
                       value="<?php echo $password->getScore(); ?>" disabled="disabled" readonly/>
                <label class="mdl-textfield__label"
                       for="score"><?php echo __('Puntuación'); ?></label>
            </div>
        </td>
    </tr>

    <tr>
        <td class="descField"><?php echo __('Expresión Regular'); ?></td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="regex" name="regex" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       value="<?php echo htmlentities($password->getRegex()); ?>"/>
                <label class="mdl-textfield__label"
                       for="regex"><?php echo __('Expresión Regular'); ?></label>
            </div>
        </td>
    </tr>

    <tr>
        <td class="descField"></td>
        <td class="valField">
            <ul class="password-list-action mdl-list">
                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="use_numbers_enabled">
                            <input type="checkbox" id="use_numbers_enabled"
                                   class="mdl-switch__input"
                                   name="use_numbers_enabled" <?php echo $password->isUseNumbers() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Incluir Números'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="use_letters_enabled">
                            <input type="checkbox" id="use_letters_enabled"
                                   class="mdl-switch__input"
                                   name="use_letters_enabled" <?php echo $password->isUseLetters() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Incluir Letras'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="use_symbols_enabled">
                            <input type="checkbox" id="use_symbols_enabled"
                                   class="mdl-switch__input"
                                   name="use_symbols_enabled" <?php echo $password->isUseSymbols() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Incluir Símbolos'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="use_upper_enabled">
                            <input type="checkbox" id="use_upper_enabled"
                                   class="mdl-switch__input"
                                   name="use_upper_enabled" <?php echo $password->isUseUpper() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Incluir Mayúsculas'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="use_lower_enabled">
                            <input type="checkbox" id="use_lower_enabled"
                                   class="mdl-switch__input"
                                   name="use_lower_enabled" <?php echo $password->isUseLower() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Incluir Minúsculas'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="use_image_enabled">
                            <input type="checkbox" id="use_image_enabled"
                                   class="mdl-switch__input"
                                   name="use_image_enabled" <?php echo $password->isUseImage() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Imagen para mostrar clave'); ?></span>
                        <span class="mdl-list__item-sub-title">
                            <?php echo __('Generar una imagen con el texto de la clave de la cuenta.'); ?>
                            <br>
                            <?php echo __('Util para entornos donde copiar la clave supone un riesgo de seguridad.'); ?>
                        </span>
                    </span>
                </li>
            </ul>
        </td>
    </tr>
    </tbody>
</table>