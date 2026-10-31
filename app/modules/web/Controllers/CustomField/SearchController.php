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

namespace SP\Modules\Web\Controllers\CustomField;

use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\CustomField\UseCases\Dtos\SearchCustomFieldsCommand;
use SP\Domain\CustomField\UseCases\SearchCustomFieldsUseCase;
use SP\Modules\Web\Controllers\SearchGridControllerBase;
use SP\Modules\Web\Presenters\Helpers\Grid\CustomFieldGrid;
use SP\Modules\Web\Html\DataGrid\DataGridInterface;
use SP\Modules\Web\Mvc\Controller\ItemTrait;

/**
 * Class SearchController
 */
final class SearchController extends SearchGridControllerBase
{
    use ItemTrait;

    public function __construct(
        \SP\Modules\Web\Mvc\Controller\WebControllerHelper $webControllerHelper,
        private readonly SearchCustomFieldsUseCase $searchCustomFieldsUseCase,
        private readonly CustomFieldGrid $customFieldGrid
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();
    }

    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        $result = $this->searchCustomFieldsUseCase->execute(new SearchCustomFieldsCommand($itemSearchData));

        return $this->customFieldGrid->updatePager(
            $this->customFieldGrid->getGrid($result->result),
            $itemSearchData
        );
    }

    protected function getAclAction(): int
    {
        return AclActionsInterface::CUSTOMFIELD_SEARCH;
    }
}
