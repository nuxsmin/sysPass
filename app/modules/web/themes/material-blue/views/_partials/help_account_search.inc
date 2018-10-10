<div id="help-account_search"
     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?> btn-help"
     data-help="help-account-search"><?php echo $icons->getIconHelp()->getIcon(); ?>
</div>

<div class="mdl-tooltip" for="help-account_search">
    <div class="tooltip-text">
        <?php echo ucwords(__('Parámetros especiales')); ?>
    </div>
</div>

<div for="help-account-search" class="help-box" title="<?php echo ucwords(__('Parámetros especiales')); ?>">
    <div class="help-container">
        <p class="help-text"><?php echo __('Es posible utilizar uno o varios parámetros especiales separados por espacios'); ?></p>
        <ul>
            <li><?php printf('<strong>user:"login"</strong> <br> %s', __('Buscar cuentas a las que \'login\' tenga acceso')); ?></li>
            <li><?php printf('<strong>owner:"login"</strong> <br> %s', __('Devolver las cuentas en las que \'login\' es propietario')); ?></li>
            <li><?php printf('<strong>group:"group_name"</strong> <br> %s', __('Buscar cuentas a las que \'group_name\' tenga acceso')); ?></li>
            <li><?php printf('<strong>maingroup:"group_name"</strong> <br> %s', __('Devolver las cuentas con grupo principal \'group_name\'')); ?></li>
            <li><?php printf('<strong>file:"file_name"</strong> <br> %s', __('Buscar cuentas con archivos con el nombre \'file_name\'')); ?></li>
            <li><?php printf('<strong>client:"client_name"</strong> <br> %s', __('Buscar cuentas por nombre de cliente')); ?></li>
            <li><?php printf('<strong>category:"category_name"</strong> <br> %s', __('Buscar cuentas por nombre de categoría')); ?></li>
            <li><?php printf('<strong>id:"account_id"</strong> <br> %s', __('Devolver la cuenta con ID')); ?></li>
            <li><?php printf('<strong>is|not:expired</strong> <br> %s', __('Buscar cuentas con la clave caducada')); ?></li>
            <li><?php printf('<strong>is|not:private</strong> <br> %s', __('Devolver las cuentas privadas del usuario actual')); ?></li>
            <li><?php printf('<strong>op:and|or</strong> <br> %s', __('Operador para los parámetros especiales')); ?></li>
        </ul>
    </div>
</div>