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
 * @var DataGridTab $data
 * @var TemplateInterface $this
 * @var DataGridAction|DataGridActionSearch $action
 */

use SP\Modules\Web\Html\DataGrid\Action\DataGridAction;
use SP\Modules\Web\Html\DataGrid\Action\DataGridActionSearch;
use SP\Modules\Web\Html\DataGrid\DataGridTab;
use SP\Modules\Web\Mvc\View\TemplateInterface;

if (!isset($data)) {
    $data = $_getvar('data');
}
?>

    <table class="mdl-data-table mdl-js-data-table table-responsive data-table">
        <thead>
        <tr>
            <?php foreach ($data->getHeader()->getHeaders() as $header): ?>
                <th class="mdl-data-table__cell--non-numeric"><?php echo $header; ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>

        <tbody id="data-rows-<?php echo $data->getId(); ?>">
        <?php include $data->getDataRowTemplate(); ?>
        </tbody>
    </table>

    <!-- Pager -->
<?php include $data->getDataPagerTemplate(); ?>