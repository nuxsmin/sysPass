<div id="searchbox" class="round shadow">
    <form method="post" name="frmSearch" id="frmSearch" OnSubmit="return sysPassUtil.Common.accSearch(0);">
        <div id="tblTools">
            <div id="toolsLeft">
                <i id="btnClear" class="material-icons" title="<?php echo _('Limpiar'); ?>">clear_all</i>

                <div class="mdl-textfield mdl-js-textfield">
                    <input class="mdl-textfield__input fg-blue100" type="text" id="txtSearch"
                           name="search" value="<?php echo $searchTxt; ?>"/>
                    <label class="mdl-textfield__label" for="txtSearch"><?php echo _('Texto a buscar'); ?></label>
                </div>
                <div id="help-account_search" class="icon material-icons fg-blue80">help_outline</div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-account_search">
                    <p>
                        <?php echo _('Parámetros especiales:'); ?>
                    </p>
                    <ul>
                        <li>:user [login] &gt; <?php echo _('Buscar cuentas a las que \'login\' tenga acceso'); ?></li>
                        <li>:group [group_name]
                            &gt; <?php echo _('Buscar cuentas a las que \'group_name\' tenga acceso'); ?></li>
                        <li>:file [file_name]
                            &gt; <?php echo _('Buscar cuentas con archivos con el nombre \'file_name\''); ?></li>
                    </ul>
                </div>

                <?php if ($showGlobalSearch && !$isAdmin): ?>
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect mdl-switch--inline" for="chkgsearch"
                           title="<?php echo _('Búsqueda global'); ?>">
                        <input type="checkbox" id="chkgsearch" class="mdl-switch__input fg-blue100" name="chkgsearch"
                            <?php echo ($searchGlobal) ? 'checked="checked"' : ''; ?>/>
                        <span class="mdl-switch__label" title="<?php echo _('Búsqueda global'); ?>"></span>
                    </label>
                    <input type="hidden" name="gsearch" value="<?php echo $searchGlobal; ?>">
                <?php endif; ?>
                <input type="hidden" name="start" value="<?php echo $limitStart; ?>">
                <input type="hidden" name="skey" value="<?php echo $searchKey; ?>"/>
                <input type="hidden" name="sorder" value="<?php echo $searchOrder; ?>"/>
                <input type="hidden" name="sk" value="<?php echo $sk; ?>">
                <input type="hidden" name="isAjax" value="1">
                <select id="selCustomer" name="customer" class="select-box sel-chosen-customer sel-chosen-deselect">
                    <option value="0"></option>
                    <?php foreach ($customers as $id => $name): ?>
                        <option
                            value="<?php echo $id; ?>" <?php echo ($id === $searchCustomer) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="selCategory" name="category" class="select-box sel-chosen-category sel-chosen-deselect">
                    <option value="0"></option>
                    <?php foreach ($categories as $id => $name): ?>
                        <option
                            value="<?php echo $id; ?>" <?php echo ($id === $searchCategory) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="toolsRight">
                <input type="number" name="rpp" id="rpp" min="6" step="1" pattern=""
                       placeholder="<?php echo _('CPP'); ?>" title="<?php echo _('Cuentas por página'); ?>"
                       value="<?php echo $limitCount; ?>"/>
            </div>
        </div>
    </form>
</div>
<script>
    $(function () {
        $('#frmSearch select').on('change', function (e) {
            sysPassUtil.Common.clearSearch(1);
            sysPassUtil.Common.accSearch(0, e)
        });
        $('#frmSearch #btnClear').click(function (e) {
            sysPassUtil.Common.clearSearch();
            sysPassUtil.Common.accSearch(0, e);
        })
        $('#frmSearch input:text:visible:first').focus();
        $('#frmSearch #txtSearch').on('keyup', function (e) {
            // Performs a 500ms delay to not fill the API very quickly
            setTimeout(function () {
                sysPassUtil.Common.accSearch(1, e);
            }, 500)
        });
        $('#frmSearch #rpp').on('change', function (e) {
            sysPassUtil.Common.accSearch(0, e);
        })

        <?php if ( $showGlobalSearch ): ?>
        $('#chkgsearch').click(
            function () {
                if ($(this).prop('checked') == true) {
                    $('input[name="gsearch"]').val(1);
                } else {
                    $('input[name="gsearch"]').val(0);
                }

                sysPassUtil.Common.accSearch(0);
            }
        );
        <?php endif; ?>
    })
</script>