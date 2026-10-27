<h5 class="datagrid-header-title"><?php
use SP\Modules\Web\Mvc\View\Template;
use function SP\__;

    echo __('Event Log'); ?></h5>
<?php
/** @var \SP\Modules\Web\Mvc\View\TemplateInterface $this */
include $this->includeTemplate('datagrid-grid', 'grid');