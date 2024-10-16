<?php
/**
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

declare(strict_types=1);

namespace SP\Modules\Web\Controllers;

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Html\DataGrid\DataGridInterface;

use function SP\__u;

/**
 * Class SearchGridControllerBase
 */
abstract class SearchGridControllerBase extends ControllerBase
{
    /**
     * @return ActionResponse
     */
    #[Action(ResponseType::JSON)]
    public function searchAction(): ActionResponse
    {
        if (!$this->acl->checkUserAccess($this->getAclAction())) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        return ActionResponse::ok('', ['html' => $this->render()]);
    }

    abstract protected function getAclAction(): int;

    abstract protected function getSearchGrid(): DataGridInterface;
}
