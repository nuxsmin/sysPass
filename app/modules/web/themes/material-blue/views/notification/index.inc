<?php
use function SP\__;

/** @var \SP\Modules\Web\Mvc\View\TemplateInterface $this */
?>
<div class="sp-management-tabs">
    <h5 class="datagrid-header-title"><?php
        echo __('Notifications'); ?></h5>
    <?php
    include $this->includeTemplate('datagrid-grid', 'grid'); ?>
</div>