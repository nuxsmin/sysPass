<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $account SP\Account\AccountSearchItem
 * @var $this    \SP\Mvc\View\Template
 * @var $icons   \SP\Core\UI\ThemeIcons
 */
?>
<div id="searchbox" class="round5 shadow">
    <form method="post" name="frmSearch" id="frmSearch" class="form-action" data-onsubmit="account/search"
          data-action-route="<?php echo $searchRoute; ?>">
        <div class="search-text">
            <div class="mdl-textfield mdl-js-textfield">
                <input class="mdl-textfield__input mdl-color-text--indigo-400" type="text" id="search"
                       name="search" value="<?php echo $searchTxt; ?>" tabindex="1"/>
                <label class="mdl-textfield__label" for="search"><?php echo __('Texto a buscar'); ?></label>
            </div>

            <div id="help-account_search" class="icon material-icons mdl-color-text--indigo-A200">help_outline</div>

            <div class="mdl-tooltip mdl-tooltip--large" for="help-account_search">
                <div class="tooltip-text">
                    <p class="tooltip-header">
                        <?php echo __('Parámetros especiales:'); ?>
                    </p>
                    <p><?php echo  __('Es posible utilizar uno o varios parámetros especiales separados por espacios'); ?></p>
                    <ul>
                        <li><?php printf('<strong>user:"login"</strong> <br> %s', __('Buscar cuentas a las que \'login\' tenga acceso')); ?></li>
                        <li><?php printf('<strong>owner:"login"</strong> <br> %s', __('Devolver las cuentas en las que \'login\' es propietario')); ?></li>
                        <li><?php printf('<strong>group:"group_name"</strong> <br> %s', __('Buscar cuentas a las que \'group_name\' tenga acceso')); ?></li>
                        <li><?php printf('<strong>maingroup:"group_name"</strong> <br> %s', __('Devolver las cuentas con grupo principal \'group_name\'')); ?></li>
                        <li><?php printf('<strong>file:"file_name"</strong> <br> %s', __('Buscar cuentas con archivos con el nombre \'file_name\'')); ?></li>
                        <li><?php printf('<strong>id:"account_id"</strong> <br> %s', __('Devolver la cuenta con ID')); ?></li>
                        <li><?php printf('<strong>is|not:expired</strong> <br> %s', __('Buscar cuentas con la clave caducada')); ?></li>
                        <li><?php printf('<strong>is|not:private</strong> <br> %s', __('Devolver las cuentas privadas del usuario actual')); ?></li>
                        <li><?php printf('<strong>op:and|or</strong> <br> %s', __('Operador para los parámetros especiales')); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="search-filters">
            <?php if ($showGlobalSearch && !$isAdmin): ?>
                <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect mdl-switch--inline" for="globalSearch"
                       title="<?php echo __('Búsqueda global'); ?>">
                    <input type="checkbox" id="globalSearch" class="mdl-switch__input mdl-color-text--indigo-400"
                           name="chkgsearch" tabindex="2"
                        <?php echo $searchGlobal ? 'checked="checked"' : ''; ?>/>
                    <span class="mdl-switch__label" title="<?php echo __('Búsqueda global'); ?>"></span>
                </label>
                <input type="hidden" name="gsearch" value="<?php echo $searchGlobal; ?>">
            <?php endif; ?>

            <select id="client" name="client" class="select-box select-box-deselect" tabindex="3">
                <option value=""><?php echo __('Seleccionar Cliente'); ?></option>
                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $clients */
                foreach ($clients as $client): ?>
                    <option
                            value="<?php echo $client->getId(); ?>" <?php echo $client->isSelected() ? 'selected' : ''; ?>><?php echo $client->getName(); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="category" name="category" class="select-box select-box-deselect" tabindex="4">
                <option value=""><?php echo __('Seleccionar Categoría'); ?></option>
                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $categories */
                foreach ($categories as $category): ?>
                    <option
                            value="<?php echo $category->getId(); ?>" <?php echo $category->isSelected() ? 'selected' : ''; ?>><?php echo $category->getName(); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="search-filters">
            <div class="filter-buttons">
                <button type="button" id="btn-favorite"
                        class="icon-searchfav mdl-button mdl-js-button mdl-button--icon" tabindex="5">
                    <i class="material-icons <?php echo ($searchFavorites === true) ? 'mdl-color-text--amber-A200' : ''; ?>"
                       title="<?php echo __(($searchFavorites === true) ? 'Mostrar Todos' : 'Mostrar Favoritos'); ?>">star</i>
                </button>
                <span for="btn-favorite"
                      class="mdl-tooltip mdl-tooltip--bottom"><?php echo __('Filtrar Favoritos'); ?></span>

                <button type="reset" id="btn-reset"
                        class="btn-clear mdl-button mdl-js-button mdl-button--icon" tabindex="6">
                    <i class="material-icons"><?php echo $icons->getIconClear()->getIcon(); ?></i>
                </button>
                <span for="btn-reset"
                      class="mdl-tooltip mdl-tooltip--bottom"><?php echo $icons->getIconClear()->getTitle(); ?></span>
            </div>
            <div class="filter-buttons filter-slider">
                <span id="txt-slider"></span>
                <input id="rpp" name="rpp" class="mdl-slider mdl-js-slider" type="range"
                       min="3" step="3" value="<?php echo $data->getPager()->getLimitCount(); ?>" tabindex="7"/>
                <span for="rpp"
                      class="mdl-tooltip mdl-tooltip--bottom"><?php echo __('Cuentas por página'); ?></span>
            </div>
            <i id="btn-filters" class="material-icons show-filter" data-state="0"
               data-icon-down="<?php echo $icons->getIconAdd()->getIcon(); ?>"
               data-icon-up="<?php echo $icons->getIconRemove()->getIcon(); ?>"><?php echo $icons->getIconAdd()->getIcon(); ?></i>
            <span for="btn-filters"
                  class="mdl-tooltip mdl-tooltip--bottom"><?php echo __('Más Filtros'); ?></span>
        </div>

        <div class="search-filters-tags">
            <select id="tags" name="tags[]" class="select-box" multiple tabindex="8">
                <option value=""><?php echo __('Seleccionar Etiqueta'); ?></option>
                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $tags */
                foreach ($tags as $tag): ?>
                    <option
                            value="<?php echo $tag->getId(); ?>" <?php echo $tag->isSelected() ? 'selected' : ''; ?>><?php echo $tag->getName(); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <input type="hidden" name="start" value="<?php echo $data->getPager()->getLimitStart(); ?>">
        <input type="hidden" name="skey" value="<?php echo $data->getPager()->getSortKey(); ?>"/>
        <input type="hidden" name="sorder" value="<?php echo $data->getPager()->getSortOrder(); ?>"/>
        <input type="hidden" name="searchfav" value="<?php echo $searchFavorites; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
</div>
<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>