<?php
/**
 * @var $data  SP\Html\DataGrid\DataGridTab
 * @var $this SP\Core\Template
 */
?>
<div id="pageNav" class="round shadow">
    <div id="pageNavLeft">
        <?php echo $data->getPager()->getTotalRows(); ?> @ <?php echo $data->getTime(); ?>s
        <?php if ($data->getPager()->getFilterOn()): ?>
            <span class="filterOn round"><?php echo _('Filtro ON'); ?></span>
        <?php endif; ?>
    </div>
    <div id="pageNavRight">
        <?php if ($data->getPager()->getLimitStart() >= 1): ?>
            <img src="imgs/arrow_first.png" onClick="<?php echo $data->getPager()->getOnClickFirst(); ?>"
                 title="<?php echo _('Primera página'); ?>"/>
            <img src="imgs/arrow_left.png" onClick="<?php echo $data->getPager()->getOnClickPrev(); ?>"
                 title="<?php echo _('Página anterior'); ?>"/>
        <?php endif; ?>
        &nbsp;<?php echo $data->getPager()->getFirstPage(), '/', $data->getPager()->getLastPage(); ?>&nbsp;
        <?php if ($data->getPager()->getLimitStart() < $data->getPager()->getTotalRows() && $data->getPager()->getFirstPage() != $data->getPager()->getLastPage()): ?>
            <img src="imgs/arrow_right.png" onClick="<?php echo $data->getPager()->getOnClickNext(); ?>"
                 title="<?php echo _('Página siguiente'); ?>"/>
            <img src="imgs/arrow_last.png" onClick="<?php echo $data->getPager()->getOnClickLast(); ?>"
                 title="<?php echo _('Última página'); ?>"/>
        <?php endif; ?>
    </div>
</div>