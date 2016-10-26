<?php
/**
 * @var $data  SP\Html\DataGrid\DataGridInterface
 * @var $this  SP\Core\Template
 */
?>
<div id="pageNav" class="round shadow">
    <div id="pageNavLeft">
        <?php echo $data->getData()->getDataCount(); ?> @ <?php echo $data->getTime(); ?>s
        <?php if ($data->getPager()->getFilterOn()): ?>
            <span class="filterOn round"><?php echo _('Filtro ON'); ?></span>
        <?php endif; ?>
    </div>
    <div id="pageNavRight">
        <?php if ($data->getPager()->getLimitStart() > 1): ?>
            <i class="btn-action-pager material-icons <?php echo $data->getPager()->getIconFirst()->getClass(); ?>"
               title="<?php echo $data->getPager()->getIconFirst()->getTitle(); ?>"
               data-action-id="<?php echo $data->getPager()->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $data->getPager()->getSourceAction()->getName(); ?>"
               data-start="<?php echo $data->getPager()->getFirst(); ?>"
               data-count="<?php echo $data->getPager()->getLimitCount(); ?>"
               data-onclick="<?php echo $data->getPager()->getOnClick(); ?>"><?php echo $data->getPager()->getIconFirst()->getIcon(); ?></i>
            <i class="btn-action-pager material-icons <?php echo $data->getPager()->getIconPrev()->getClass(); ?>"
               title="<?php echo $data->getPager()->getIconPrev()->getTitle(); ?>"
               data-action-id="<?php echo $data->getPager()->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $data->getPager()->getSourceAction()->getName(); ?>"
               data-start="<?php echo $data->getPager()->getPrev(); ?>"
               data-count="<?php echo $data->getPager()->getLimitCount(); ?>"
               data-onclick="<?php echo $data->getPager()->getOnClick(); ?>"><?php echo $data->getPager()->getIconPrev()->getIcon(); ?></i>
        <?php endif; ?>
        &nbsp;
        <?php echo $data->getPager()->getFirstPage(), '/', $data->getPager()->getLastPage(); ?>
        &nbsp;
        <?php if ($data->getPager()->getLimitStart() < $data->getPager()->getTotalRows() && $data->getPager()->getFirstPage() != $data->getPager()->getLastPage()): ?>
            <i class="btn-action-pager material-icons <?php echo $data->getPager()->getIconNext()->getClass(); ?>"
               title="<?php echo $data->getPager()->getIconNext()->getTitle(); ?>"
               data-action-id="<?php echo $data->getPager()->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $data->getPager()->getSourceAction()->getName(); ?>"
               data-start="<?php echo $data->getPager()->getNext(); ?>"
               data-count="<?php echo $data->getPager()->getLimitCount(); ?>"
               data-onclick="<?php echo $data->getPager()->getOnClick(); ?>"><?php echo $data->getPager()->getIconNext()->getIcon(); ?></i>
            <i class="btn-action-pager material-icons <?php echo $data->getPager()->getIconLast()->getClass(); ?>"
               title="<?php echo $data->getPager()->getIconLast()->getTitle(); ?>"
               data-action-id="<?php echo $data->getPager()->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $data->getPager()->getSourceAction()->getName(); ?>"
               data-start="<?php echo $data->getPager()->getLast(); ?>"
               data-count="<?php echo $data->getPager()->getLimitCount(); ?>"
               data-onclick="<?php echo $data->getPager()->getOnClick(); ?>"><?php echo $data->getPager()->getIconLast()->getIcon(); ?></i>
        <?php endif; ?>
    </div>
</div>