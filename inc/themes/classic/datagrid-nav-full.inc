<?php
/**
 * @var $data  SP\Html\DataGrid\DataGridInterface
 * @var $this SP\Core\Template
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
            <i class="material-icons <?php echo $data->getPager()->getIconFirst()->getClass(); ?>"
               title="<?php echo $data->getPager()->getIconFirst()->getTitle(); ?>"
               Onclick="<?php echo $data->getPager()->getOnClickFirst(); ?>"><?php echo $data->getPager()->getIconFirst()->getIcon(); ?></i>
            <i class="material-icons <?php echo $data->getPager()->getIconPrev()->getClass(); ?>"
               title="<?php echo $data->getPager()->getIconPrev()->getTitle(); ?>"
               Onclick="<?php echo $data->getPager()->getOnClickPrev(); ?>"><?php echo $data->getPager()->getIconPrev()->getIcon(); ?></i>
        <?php endif; ?>
        &nbsp;
        <?php echo $data->getPager()->getFirstPage(), '/', $data->getPager()->getLastPage(); ?>
        &nbsp;
        <?php if ($data->getPager()->getLimitStart() < $data->getPager()->getTotalRows() && $data->getPager()->getFirstPage() != $data->getPager()->getLastPage()): ?>
            <i class="material-icons <?php echo $data->getPager()->getIconNext()->getClass(); ?>"
               title="<?php echo $data->getPager()->getIconNext()->getTitle(); ?>"
               Onclick="<?php echo $data->getPager()->getOnClickNext(); ?>"><?php echo $data->getPager()->getIconNext()->getIcon(); ?></i>
            <i class="material-icons <?php echo $data->getPager()->getIconLast()->getClass(); ?>"
               title="<?php echo $data->getPager()->getIconLast()->getTitle(); ?>"
               Onclick="<?php echo $data->getPager()->getOnClickLast(); ?>"><?php echo $data->getPager()->getIconLast()->getIcon(); ?></i>
        <?php endif; ?>
    </div>
</div>