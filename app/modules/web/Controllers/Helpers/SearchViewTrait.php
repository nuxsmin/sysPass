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

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\Http\Ports\RequestService;
use SP\Html\DataGrid\DataGridInterface;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\View\TemplateInterface;

use function SP\__u;

/**
 * Trait SearchViewTrait
 *
 * @property AclInterface $acl
 * @property TemplateInterface $view
 * @property RequestService $request
 * @method render()
 */
trait SearchViewTrait
{
    use JsonTrait;

    /**
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess($this->getAclAction())) {
            return $this->returnJsonResponse(
                JsonMessage::JSON_ERROR,
                __u('You don\'t have permission to do this operation')
            );
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    abstract private function getAclAction(): int;

    abstract protected function getSearchGrid(): DataGridInterface;
}
