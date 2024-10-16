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
 * @var SP\Html\DataGrid\DataGridTab $data
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\TemplateInterface;

$tabs = $_getvar('tabs');
?>
<div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
    <div id="tabsHeader" class="mdl-tabs__tab-bar"></div>

    <?php
    foreach ($tabs as $index => $data): ?>
        <div id="tabs-<?php
        echo $index; ?>"
             class="mdl-tabs__panel"
             data-tab-route="<?php
             echo $_getvar('tabsRoute'); ?>"
             data-tab-index="<?php
             echo $index; ?>">

            <?php
            include __DIR__ . DIRECTORY_SEPARATOR . 'datagrid-grid.inc'; ?>
        </div>

        <script type="text/javascript">
            sysPassApp.theme.html.tabs.add("#tabsHeader", <?php echo $index; ?>, "<?php echo $data->getTitle(
            ); ?>", <?php echo $index === $_getvar('activeTab') ? 1 : 0; ?>);
        </script>
    <?php
    endforeach; ?>
</div>
<!-- End Tabs-->
