<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Plugin;

use SP\Core\Application;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Plugin\UseCases\Dtos\SearchPluginsCommand;
use SP\Domain\Plugin\UseCases\SearchPluginsUseCase;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\Grid\PluginGrid;
use SP\Modules\Web\Html\DataGrid\DataGridInterface;
use SP\Modules\Web\Mvc\Controller\ItemTrait;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

/**
 * Class PluginSearchBase
 */
abstract class PluginSearchBase extends ControllerBase
{
    use ItemTrait;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        private readonly SearchPluginsUseCase $searchPluginsUseCase,
        private readonly PluginGrid $pluginGrid
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        $result = $this->searchPluginsUseCase->execute(new SearchPluginsCommand($itemSearchData));

        return $this->pluginGrid->updatePager(
            $this->pluginGrid->getGrid(
                QueryResult::withTotalNumRows($result->plugins, $result->total)
            ),
            $itemSearchData
        );
    }
}
