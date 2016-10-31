<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $account SP\Account\AccountsSearchData
 * @var $this    SP\Core\Template
 */
?>
<div id="searchbox" class="round shadow">
    <form method="post" name="frmSearch" id="frmSearch">
        <div id="tblTools">
            <div id="toolsLeft">
                <i id="btnClear" class="material-icons"
                   title="<?php echo _('Limpiar'); ?>"><?php echo $icons->getIconClear()->getIcon(); ?></i>

                <div class="mdl-textfield mdl-js-textfield">
                    <input class="mdl-textfield__input mdl-color-text--indigo-400" type="text" id="search"
                           name="search" value="<?php echo $searchTxt; ?>"/>
                    <label class="mdl-textfield__label" for="search"><?php echo _('Texto a buscar'); ?></label>
                </div>

                <div id="help-account_search" class="icon material-icons mdl-color-text--indigo-A200">help_outline</div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-account_search">
                    <p>
                        <?php echo _('Parámetros especiales:'); ?>
                    </p>
                    <ul>
                        <li>user:login &gt; <?php echo _('Buscar cuentas a las que \'login\' tenga acceso'); ?></li>
                        <li>group:group_name
                            &gt; <?php echo _('Buscar cuentas a las que \'group_name\' tenga acceso'); ?></li>
                        <li>file:file_name
                            &gt; <?php echo _('Buscar cuentas con archivos con el nombre \'file_name\''); ?></li>
                        <li>tag:tag_name &gt; <?php echo _('Buscar cuentas con la etiqueta \'tag_name\''); ?></li>
                    </ul>
                </div>

                <?php if ($showGlobalSearch && !$isAdmin): ?>
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect mdl-switch--inline" for="globalSearch"
                           title="<?php echo _('Búsqueda global'); ?>">
                        <input type="checkbox" id="globalSearch" class="mdl-switch__input mdl-color-text--indigo-400"
                               name="chkgsearch"
                            <?php echo ($searchGlobal) ? 'checked="checked"' : ''; ?>/>
                        <span class="mdl-switch__label" title="<?php echo _('Búsqueda global'); ?>"></span>
                    </label>
                    <input type="hidden" name="gsearch" value="<?php echo $searchGlobal; ?>">
                <?php endif; ?>

                <select id="customer" name="customer" class="select-box select-box-deselect">
                    <option value=""><?php echo _('Seleccionar Cliente'); ?></option>
                    <?php foreach ($customers as $customer): ?>
                        <option
                            value="<?php echo $customer->id; ?>" <?php echo ($customer->id === $searchCustomer) ? 'selected' : ''; ?>><?php echo $customer->name; ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="category" name="category" class="select-box select-box-deselect">
                    <option value=""><?php echo _('Seleccionar Categoría'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?php echo $category->id; ?>" <?php echo ($category->id === $searchCategory) ? 'selected' : ''; ?>><?php echo $category->name; ?></option>
                    <?php endforeach; ?>
                </select>

                <i class="material-icons icon-searchfav <?php echo ($searchFavorites === true) ? 'fg-orange80' : ''; ?>"
                   title="<?php echo ($searchFavorites === true) ? _('Mostrar Todos') : _('Mostrar Favoritos'); ?>">star</i>

                <input type="hidden" name="start" value="<?php echo $data->getPager()->getLimitStart(); ?>">
                <input type="hidden" name="skey" value="<?php echo $data->getPager()->getSortKey(); ?>"/>
                <input type="hidden" name="sorder" value="<?php echo $data->getPager()->getSortOrder(); ?>"/>
                <input type="hidden" name="searchfav" value="<?php echo $searchFavorites; ?>"/>
                <input type="hidden" name="sk" value="<?php echo $sk; ?>">
                <input type="hidden" name="isAjax" value="1">

                <input type="submit" name="submit" style="display: none"/>
            </div>
            <div id="toolsRight">
                <input type="number" name="rpp" id="rpp" min="4" step="2" pattern=""
                       placeholder="<?php echo _('CPP'); ?>" title="<?php echo _('Cuentas por página'); ?>"
                       value="<?php echo $data->getPager()->getLimitCount(); ?>"/>
            </div>
        </div>
    </form>
</div>
<script type="text/javascript">
    sysPassApp.triggers().views.search();
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>