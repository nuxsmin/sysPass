<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 * @var \SP\Domain\Core\UI\ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\TemplateInterface;

?>
<form method="post" name="frmConfig" id="frmConfig" class="form-action"
      data-onsubmit="config/save"
      data-action-route="configGeneral/save"
      data-hash="">

    <?php
    include $this->includeTemplate('general-site'); ?>
    <?php
    include $this->includeTemplate('general-events'); ?>
    <?php
    include $this->includeTemplate('general-proxy'); ?>
    <?php
    include $this->includeTemplate('general-auth'); ?>
</form>

<div class="tab-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->back()->getClassButton(); ?>"
                    title="<?php
                    echo __('Back'); ?>">
                <i class="material-icons"><?php
                    echo $icons->back()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button form="frmConfig"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->save()->getClassButton(); ?>"
                    title="<?php
                    echo $icons->save()->getTitle(); ?>">
                <i class="material-icons"><?php
                    echo $icons->save()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
