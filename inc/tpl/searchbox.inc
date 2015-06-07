<form method="post" name="frmSearch" id="frmSearch" OnSubmit="return accSearch(0);">
    <table id="tblTools" class="round shadow">
        <tr>
            <td id="toolsLeft">
                <img src="imgs/clear.png" title="<?php echo _('Limpiar'); ?>" class="inputImg" id="btnClear" onClick="clearSearch(); accSearch(0);" />
                <input type="text" name="search" id="txtSearch" onKeyUp="accSearch(1,event)" value="<?php echo $searchTxt; ?>" placeholder="<?php echo _('Texto a buscar'); ?>"/>
                <?php if ( $globalSearch && ! $isAdmin ): ?>
                <input type="checkbox" name="gsearch" id="gsearch" class="checkbox" <?php echo ($searchGlobal) ? 'checked="checked"' : ''; ?>/>
                <label for="gsearch" title="<?php echo _('Búsqueda global');?>"><?php echo ($searchGlobal) ? 'ON' : 'OFF'; ?></label>
                <?php endif; ?>                
                <input type="hidden" name="start" value="<?php echo $searchStart; ?>">
                <input type="hidden" name="skey" value="<?php echo $searchKey; ?>" />
                <input type="hidden" name="sorder" value="<?php echo $searchOrder; ?>" />
                <input type="hidden" name="sk" value="<?php echo $sk; ?>">
                <input type="hidden" name="isAjax" value="1">
                <?php
                SP_Html::printSelect(DB::getValuesForSelect('customers', 'customer_id', 'customer_name'), $customersSelProp);
                SP_Html::printSelect(DB::getValuesForSelect('categories', 'category_id', 'category_name'), $categoriesSelProp);
                ?>
                <br>
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
    
    $("#rpp").spinner({step: 3, max: 50, min: 6, numberFormat: "n", stop: function(event, ui) {
            accSearch(0);
        }});
    <?php if ( $globalSearch ): ?>
    $('#tblTools').find('.checkbox').button();
    $('#gsearch').click(function(){
        if ( $(this).next('label').hasClass('ui-state-active') ){
            $(this).next('label').children('span').html('OFF');
        } else{
            $(this).next('label').children('span').html('ON');
        }
        accSearch(0);
    });
    <?php endif; ?>
    $('input:text:visible:first').focus();
</script>