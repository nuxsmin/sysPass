<?php
/**
 * @var $data  SP\Html\DataGrid\DataGridInterface
 * @var $this  SP\Core\Template
 */
?>
<div class="pager round5 shadow">
    <div class="pager-left">
        <?php echo $data->getData()->getDataCount(); ?> @ <?php echo $data->getTime(); ?>s
        <?php if ($data->getPager()->getFilterOn()): ?>
            <span class="filter-on round"><?php echo __('Filtro ON'); ?></span>
        <?php endif; ?>
    </div>
    <div class="pager-right">
        <?php if ($data->getPager()->getLimitStart() > 1): ?>
            <i id="btn-pager-first"
                class="btn-action-pager material-icons <?php echo $data->getPager()->getIconFirst()->getClass(); ?>"
               data-action-id="<?php echo $data->getPager()->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $data->getPager()->getSourceAction()->getName(); ?>"
               data-start="<?php echo $data->getPager()->getFirst(); ?>"
               data-count="<?php echo $data->getPager()->getLimitCount(); ?>"
               data-onclick="<?php echo $data->getPager()->getOnClick(); ?>"><?php echo $data->getPager()->getIconFirst()->getIcon(); ?></i>
            <span
                for="btn-pager-first"
                class="mdl-tooltip mdl-tooltip--top"><?php echo $data->getPager()->getIconFirst()->getTitle(); ?></span>

            <i id="btn-pager-prev"
               class="btn-action-pager material-icons <?php echo $data->getPager()->getIconPrev()->getClass(); ?>"
               data-action-id="<?php echo $data->getPager()->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $data->getPager()->getSourceAction()->getName(); ?>"
               data-start="<?php echo $data->getPager()->getPrev(); ?>"
               data-count="<?php echo $data->getPager()->getLimitCount(); ?>"
               data-onclick="<?php echo $data->getPager()->getOnClick(); ?>"><?php echo $data->getPager()->getIconPrev()->getIcon(); ?></i>

            <span
                for="btn-pager-prev"
                class="mdl-tooltip mdl-tooltip--top"><?php echo $data->getPager()->getIconPrev()->getTitle(); ?></span>
        <?php endif; ?>
        &nbsp;
        <?php printf('%d / %d (%d)', $data->getPager()->getFirstPage(), $data->getPager()->getLastPage(), $data->getPager()->getLimitCount()); ?>
        &nbsp;
        <?php if ($data->getPager()->getLimitStart() < $data->getPager()->getTotalRows() && $data->getPager()->getFirstPage() != $data->getPager()->getLastPage()): ?>
            <i id="btn-pager-next"
               class="btn-action-pager material-icons <?php echo $data->getPager()->getIconNext()->getClass(); ?>"
               data-action-id="<?php echo $data->getPager()->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $data->getPager()->getSourceAction()->getName(); ?>"
               data-start="<?php echo $data->getPager()->getNext(); ?>"
               data-count="<?php echo $data->getPager()->getLimitCount(); ?>"
               data-onclick="<?php echo $data->getPager()->getOnClick(); ?>"><?php echo $data->getPager()->getIconNext()->getIcon(); ?></i>

            <span
                for="btn-pager-next"
                class="mdl-tooltip mdl-tooltip--top"><?php echo $data->getPager()->getIconNext()->getTitle(); ?></span>
            <i id="btn-pager-last"
               class="btn-action-pager material-icons <?php echo $data->getPager()->getIconLast()->getClass(); ?>"
               data-action-id="<?php echo $data->getPager()->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $data->getPager()->getSourceAction()->getName(); ?>"
               data-start="<?php echo $data->getPager()->getLast(); ?>"
               data-count="<?php echo $data->getPager()->getLimitCount(); ?>"
               data-onclick="<?php echo $data->getPager()->getOnClick(); ?>"><?php echo $data->getPager()->getIconLast()->getIcon(); ?></i>
            <span
                for="btn-pager-last"
                class="mdl-tooltip mdl-tooltip--top"><?php echo $data->getPager()->getIconLast()->getTitle(); ?></span>
        <?php endif; ?>
    </div>
</div>