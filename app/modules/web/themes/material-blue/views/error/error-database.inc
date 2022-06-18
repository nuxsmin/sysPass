<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 * @var ThemeIcons $icons
 * @var \SP\Mvc\View\TemplateInterface $this
 */

use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

?>
<div id="actions" align="center">
    <?php include $this->includePartial('error-list'); ?>

    <div class="buttons">
        <button id="btnBack"
                class="btn-back mdl-button mdl-js-button mdl-button--raised mdl-button--colored">
            <i class="material-icons"
               title="<?php echo __('Back'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            <?php echo __('Back'); ?>
        </button>
        <a class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored"
           href="index.php?r=install&skipInstalled=true">
            <?php echo __('Install'); ?>
            <i class="material-icons"
               title="<?php echo __('Install'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
        </a>
    </div>
</div>