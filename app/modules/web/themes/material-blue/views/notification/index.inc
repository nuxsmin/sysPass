<h5 class="datagrid-header-title"><?php use SP\Mvc\View\Template;

    echo __('Notifications'); ?></h5>
<?php
/** @var \SP\Mvc\View\TemplateInterface $this */
include $this->includeTemplate('datagrid-grid', 'grid');