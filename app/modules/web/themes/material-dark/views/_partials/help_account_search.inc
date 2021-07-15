<div id="help-account_search"
     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?> btn-help"
     data-help="help-account-search"><?php echo $icons->getIconHelp()->getIcon(); ?>
</div>

<div class="mdl-tooltip" for="help-account_search">
    <div class="tooltip-text">
        <?php echo ucwords(__('Special parameters')); ?>
    </div>
</div>

<div for="help-account-search" class="help-box"
     title="<?php echo ucwords(__('Special parameters')); ?>">
    <div class="help-container">
        <p class="help-text"><?php echo __('You could use either one or several special parameters separated by blank spaces'); ?></p>
        <ul>
            <li><?php printf('<strong>user:"login"</strong> <br> %s', __('Search for accounts which \'login\' has access rights')); ?></li>
            <li><?php printf('<strong>owner:"login"</strong> <br> %s', __('Get the accounts in which user login "login" is the owner')); ?></li>
            <li><?php printf('<strong>group:"group_name"</strong> <br> %s', __('Search for accounts which \'group_name\' has access rights')); ?></li>
            <li><?php printf('<strong>maingroup:"group_name"</strong> <br> %s', __('Get the accounts in which main group "group_name" is set')); ?></li>
            <li><?php printf('<strong>file:"file_name"</strong> <br> %s', __('Search for accounts which contain a file with the name \'file_name\'')); ?></li>
            <li><?php printf('<strong>client:"client_name"</strong> <br> %s', __('Search for accounts by client name')); ?></li>
            <li><?php printf('<strong>category:"category_name"</strong> <br> %s', __('Search for accounts by category name')); ?></li>
            <li><?php printf('<strong>id:"account_id"</strong> <br> %s', __('Returns the account for the given ID')); ?></li>
            <li><?php printf('<strong>name_regex:"regex"</strong> <br> %s', __('Search for accounts name by regular expression')); ?></li>
            <li><?php printf('<strong>is|not:expired</strong> <br> %s', __('Search for accounts with expired password')); ?></li>
            <li><?php printf('<strong>is|not:private</strong> <br> %s', __('Get private accounts for current user')); ?></li>
            <li><?php printf('<strong>op:and|or</strong> <br> %s', __('Operator for special parameters')); ?></li>
        </ul>
    </div>
</div>