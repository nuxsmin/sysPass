<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @var SP\Modules\Web\Html\DataGrid\DataGridInterface $data
 * @var TemplateInterface $this
 * @var callable $_getvar
 */

use SP\Modules\Web\Mvc\View\TemplateInterface;

use function SP\__;

if (!isset($data)) {
    $data = $_getvar('data');
}

$pager = $data->getPager();
?>
<div class="pager round5 shadow">
    <div class="pager-left">
        <?php echo $data->getData()->getDataCount(); ?>
        @ <?php echo $data->getTime(); ?>s
        <?php if ($pager->getFilterOn()): ?>
            <span class="filter-on round"><?php echo __('Filter ON'); ?></span>
        <?php endif; ?>
    </div>
    <div class="pager-right">
        <?php if ($pager->getLimitStart() > 1): ?>
            <i id="btn-pager-first"
               class="btn-action-pager material-icons <?php echo $pager->getIconFirst()->getClass(); ?>"
               data-action-id="<?php echo $pager->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $pager->getSourceAction()->getName(); ?>"
               data-start="<?php echo $pager->getFirst(); ?>"
               data-count="<?php echo $pager->getLimitCount(); ?>"
               data-onclick="<?php echo $pager->getOnClick(); ?>"><?php echo $pager->getIconFirst()->getIcon(); ?></i>
            <span
                    for="btn-pager-first"
                    class="mdl-tooltip mdl-tooltip--top"><?php echo $pager->getIconFirst()->getTitle(); ?></span>

            <i id="btn-pager-prev"
               class="btn-action-pager material-icons <?php echo $pager->getIconPrev()->getClass(); ?>"
               data-action-id="<?php echo $pager->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $pager->getSourceAction()->getName(); ?>"
               data-start="<?php echo $pager->getPrev(); ?>"
               data-count="<?php echo $pager->getLimitCount(); ?>"
               data-onclick="<?php echo $pager->getOnClick(); ?>"><?php echo $pager->getIconPrev()->getIcon(); ?></i>

            <span
                    for="btn-pager-prev"
                    class="mdl-tooltip mdl-tooltip--top"><?php echo $pager->getIconPrev()->getTitle(); ?></span>
        <?php endif; ?>
        &nbsp;
        <?php printf('%d / %d (%d)', $pager->getFirstPage(), $pager->getLastPage(), $pager->getLimitCount()); ?>
        &nbsp;
        <?php if ($pager->getLimitStart() < $pager->getTotalRows() && $pager->getFirstPage() != $pager->getLastPage()): ?>
            <i id="btn-pager-next"
               class="btn-action-pager material-icons <?php echo $pager->getIconNext()->getClass(); ?>"
               data-action-id="<?php echo $pager->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $pager->getSourceAction()->getName(); ?>"
               data-start="<?php echo $pager->getNext(); ?>"
               data-count="<?php echo $pager->getLimitCount(); ?>"
               data-onclick="<?php echo $pager->getOnClick(); ?>"><?php echo $pager->getIconNext()->getIcon(); ?></i>

            <span
                    for="btn-pager-next"
                    class="mdl-tooltip mdl-tooltip--top"><?php echo $pager->getIconNext()->getTitle(); ?></span>
            <i id="btn-pager-last"
               class="btn-action-pager material-icons <?php echo $pager->getIconLast()->getClass(); ?>"
               data-action-id="<?php echo $pager->getSourceAction()->getId(); ?>"
               data-action-form="<?php echo $pager->getSourceAction()->getName(); ?>"
               data-start="<?php echo $pager->getLast(); ?>"
               data-count="<?php echo $pager->getLimitCount(); ?>"
               data-onclick="<?php echo $pager->getOnClick(); ?>"><?php echo $pager->getIconLast()->getIcon(); ?></i>
            <span
                    for="btn-pager-last"
                    class="mdl-tooltip mdl-tooltip--top"><?php echo $pager->getIconLast()->getTitle(); ?></span>
        <?php endif; ?>
    </div>
</div>
