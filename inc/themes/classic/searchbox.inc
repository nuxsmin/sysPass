<form method="post" name="frmSearch" id="frmSearch" OnSubmit="return accSearch(0);">
    <table id="tblTools" class="round shadow">
        <tr>
            <td id="toolsLeft">
                <img src="<?php echo $icons->getIconClear()->getIcon(); ?>" title="<?php echo _('Limpiar'); ?>" class="inputImg" id="btnClear"/>
                <input type="text" name="search" id="txtSearch" value="<?php echo $searchTxt; ?>"
                       placeholder="<?php echo _('Texto a buscar'); ?>"/>
                <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="" class="inputImgMini help-tooltip"/>

                <div class="tooltip" style="display:none;">
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
                    <label for="chkgsearch"
                           title="<?php echo _('Búsqueda global'); ?>"><?php echo ($searchGlobal) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="chkgsearch" id="chkgsearch"
                           class="checkbox" <?php echo ($searchGlobal) ? 'checked="checked"' : ''; ?>/>
                    <input type="hidden" name="gsearch" value="<?php echo $searchGlobal; ?>">
                <?php endif; ?>
                <input type="hidden" name="start" value="<?php echo $data->getPager()->getLimitStart(); ?>">
                <input type="hidden" name="skey" value="<?php echo $data->getPager()->getSortKey(); ?>"/>
                <input type="hidden" name="sorder" value="<?php echo $data->getPager()->getSortOrder(); ?>"/>
                <input type="hidden" name="searchfav" value="<?php echo $searchFavorites; ?>"/>
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
                <i class="material-icons icon-searchfav cursor-pointer <?php echo ($searchFavorites === true) ? 'fg-orange80' : 'fg-grey'; ?>" title="<?php echo ($searchFavorites === true) ? _('Mostrar Todos') : _('Mostrar Favoritos'); ?>">star</i>
            </td>
            <td id="toolsRight">
                <input type="text" name="rpp" id="rpp" placeholder="<?php echo _('CPP'); ?>"
                       title="<?php echo _('Cuentas por página'); ?>" value="<?php echo $data->getPager()->getLimitCount(); ?>"/>
            </td>
        </tr>
    </table>
</form>
<script>
    $(function () {
        $('#frmSearch select').on('change', function (e) {
            sysPassUtil.Common.clearSearch(1);
            sysPassUtil.Common.accSearch(0)
        });

        $('#frmSearch #btnClear').click(function (e) {
            sysPassUtil.Common.clearSearch();
            sysPassUtil.Common.accSearch(0);
        });

        $('#frmSearch input:text:visible:first').focus();
        $('#frmSearch #txtSearch').on('keyup', function (e) {
            setTimeout(function(){
                sysPassUtil.Common.accSearch(1, e);
            }, 500);
        });

        $('#frmSearch #rpp').on('change', function () {
            sysPassUtil.Common.accSearch(0);
        });

        $("#rpp").spinner({
            step: 3, max: 50, min: 6, numberFormat: "n", stop: function (event, ui) {
                sysPassUtil.Common.accSearch(0);
            }
        });

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

        $('.icon-searchfav').on('click', function () {
            var searchfav = $('#frmSearch').find('[name=searchfav]');

            if (searchfav.val() == 0) {
                searchfav.val(1).change();
            } else {
                searchfav.val(0).change();
            }

            sysPassUtil.Common.accSearch(0);
        });

        $(':input[name=searchfav]').on('change', function(){
            var icon = $('.icon-searchfav');

            console.info($(this).val());

            if ($(this).val() == 0){
                icon.removeClass('fg-orange80').addClass('fg-grey');
                icon.attr('title', '<?php echo _('Mostrar Favoritos'); ?>');
            } else {
                icon.addClass('fg-orange80').removeClass('fg-grey');
                icon.attr('title', '<?php echo _('Mostrar Todos'); ?>');
            }
        });
    })
</script>