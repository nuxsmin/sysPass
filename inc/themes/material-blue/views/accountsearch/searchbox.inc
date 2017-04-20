<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $account SP\Account\AccountsSearchItem
 * @var $this    SP\Core\Template
 * @var $icons   \SP\Core\UI\ThemeIconsBase
 */
?>
<div id="searchbox" class="round5 shadow">
    <form method="post" name="frmSearch" id="frmSearch" class="form-action" data-onsubmit="account/search">
        <div class="search-text">
            <div class="mdl-textfield mdl-js-textfield">
                <input class="mdl-textfield__input mdl-color-text--indigo-400" type="text" id="search"
                       name="search" value="<?php echo $searchTxt; ?>" tabindex="1"/>
                <label class="mdl-textfield__label" for="search"><?php echo __('Texto a buscar'); ?></label>
            </div>

            <div id="help-account_search" class="icon material-icons mdl-color-text--indigo-A200">help_outline</div>

            <div class="mdl-tooltip mdl-tooltip--large" for="help-account_search">
                <p>
                    <?php echo __('Parámetros especiales:'); ?>
                </p>
                <ul>
                    <li><?php printf('<strong>user:"login"</strong> <br> %s', __('Buscar cuentas a las que \'login\' tenga acceso')); ?></li>
                    <li><?php printf('<strong>owner:"login"</strong> <br> %s', __('Devolver las cuentas en las que \'login\' es propietario')); ?></li>
                    <li><?php printf('<strong>group:"group_name"</strong> <br> %s', __('Buscar cuentas a las que \'group_name\' tenga acceso')); ?></li>
                    <li><?php printf('<strong>maingroup:"group_name"</strong> <br> %s', __('Devolver las cuentas con grupo principal \'group_name\'')); ?></li>
                    <li><?php printf('<strong>file:"file_name"</strong> <br> %s', __('Buscar cuentas con archivos con el nombre \'file_name\'')); ?></li>
                    <li><?php printf('<strong>expired:</strong> <br> %s', __('Buscar cuentas con la clave caducada')); ?></li>
                    <li><?php printf('<strong>private:</strong> <br> %s', __('Devolver las cuentas privadas del usuario actual')); ?></li>
                </ul>
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

            <select id="customer" name="customer" class="select-box select-box-deselect" tabindex="3">
                <option value=""><?php echo __('Seleccionar Cliente'); ?></option>
                <?php foreach ($customers as $customer): ?>
                    <option
                            value="<?php echo $customer->id; ?>" <?php echo ((int)$customer->id === $searchCustomer) ? 'selected' : ''; ?>><?php echo $customer->name; ?></option>
                <?php endforeach; ?>
            </select>

            <select id="category" name="category" class="select-box select-box-deselect" tabindex="4">
                <option value=""><?php echo __('Seleccionar Categoría'); ?></option>
                <?php foreach ($categories as $category): ?>
                    <option
                            value="<?php echo $category->id; ?>" <?php echo ((int)$category->id === $searchCategory) ? 'selected' : ''; ?>><?php echo $category->name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="search-filters">
            <div class="filter-buttons">
                <button type="button" id="btn-favorite"
                        class="icon-searchfav mdl-button mdl-js-button mdl-button--icon" tabindex="5">
                    <i class="material-icons <?php echo ($searchFavorites === true) ? 'mdl-color-text--amber-A200' : ''; ?>"
                       title="<?php echo ($searchFavorites === true) ? __('Mostrar Todos') : __('Mostrar Favoritos'); ?>">star</i>
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
                <?php foreach ($tags as $tag): ?>
                    <option
                            value="<?php echo $tag->id; ?>" <?php echo in_array($tag->id, $searchTags) ? 'selected' : ''; ?>><?php echo $tag->name; ?></option>
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