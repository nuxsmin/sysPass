<h5 class="datagrid-header-title"><?php use SP\Modules\Web\Mvc\View\Template;

    echo __('Plugins'); ?></h5>
<?php
/** @var \SP\Modules\Web\Mvc\View\TemplateInterface $this */
include $this->includeTemplate('datagrid-grid', 'grid');