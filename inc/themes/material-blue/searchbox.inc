<form method="post" name="frmSearch" id="frmSearch" OnSubmit="return accSearch(0);">
    <table id="tblTools" class="round shadow">
        <tr>
            <td id="toolsLeft">
                <i id="btnClear" class="material-icons" title="<?php echo _('Limpiar'); ?>">clear</i>
                <div class="mdl-textfield mdl-js-textfield">
                    <input class="mdl-textfield__input" type="text" id="txtSearch" name="search" value="<?php echo $searchTxt; ?>"/>
                    <label class="mdl-textfield__label" for="txtSearch"><?php echo _('Texto a buscar'); ?></label>
                </div>

                <?php if ( $showGlobalSearch && ! $isAdmin ): ?>
                    <label for="chkgsearch" title="<?php echo _('Búsqueda global');?>"><?php echo ($searchGlobal) ? 'ON' : 'OFF'; ?></label>
                    <input type="checkbox" name="chkgsearch" id="chkgsearch" class="checkbox" <?php echo ($searchGlobal) ? 'checked="checked"' : ''; ?>/>
                    <input type="hidden" name="gsearch" value="<?php echo $searchGlobal; ?>">
                <?php endif; ?>
                <input type="hidden" name="start" value="<?php echo $limitStart; ?>">
                <input type="hidden" name="skey" value="<?php echo $searchKey; ?>" />
                <input type="hidden" name="sorder" value="<?php echo $searchOrder; ?>" />
                <input type="hidden" name="sk" value="<?php echo $sk; ?>">
                <input type="hidden" name="isAjax" value="1">
                <select id="selCustomer" name="customer" class="select-box">
                    <option value="0"></option>
                    <?php foreach ($customers as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($id === $searchCustomer) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="selCategory" name="category" class="select-box">
                    <option value="0"></option>
                    <?php foreach ($categories as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo ($id === $searchCategory) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td id="toolsRight">
                <input type="text" name="rpp" id="rpp" placeholder="<?php echo _('CPP'); ?>" title="<?php echo _('Cuentas por página'); ?>" value="<?php echo $limitCount; ?>"/>
            </td>
        </tr>
    </table>
</form>
<script>
    mkChosen({id: 'selCustomer', placeholder: '<?php echo _('Seleccionar Cliente'); ?>', noresults: '<?php echo _('Sin resultados'); ?>' });
    mkChosen({id: 'selCategory', placeholder: '<?php echo _('Seleccionar Categoría'); ?>', noresults: '<?php echo _('Sin resultados'); ?>' });

    $('#frmSearch select').on('change', function(e){clearSearch(1); accSearch(0)});

    $("#rpp").spinner({step: 3, max: 50, min: 6, numberFormat: "n", stop: function(event, ui) {
            accSearch(0);
        }});
    $('#frmSearch input:text:visible:first').focus();
    $('#frmSearch #txtSearch').keyup(function(e){accSearch(1,e)});
    $('#frmSearch #btnClear').click(function(e){clearSearch(); accSearch(0);})

    <?php if ( $showGlobalSearch ): ?>
    $('#tblTools').find('.checkbox').button().click(
        function(){
            if ($(this).attr('checked') == undefined){
                $(this).button('option', 'label', 'OFF');
                $('input[name="gsearch"]').val(0);
            } else {
                $(this).button('option', 'label', 'ON');
                $('input[name="gsearch"]').val(1);
            }

            accSearch(0);
        }
    );
    <?php endif; ?>
</script>