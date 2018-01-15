<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers;

use SP\Account\AccountUtil;
use SP\Controller\RequestControllerTrait;
use SP\Core\SessionUtil;
use SP\DataModel\DataModelInterface;
use SP\Services\Account\AccountService;
use SP\Util\Json;

/**
 * Class ItemsController
 *
 * @package SP\Modules\Web\Controllers
 */
class ItemsController
{
    use RequestControllerTrait;

    /**
     * ItemsController constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Devolver las cuentas visibles por el usuario
     *
     * @param int $accountId
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function accountsUserAction($accountId = null)
    {
        $outItems = [];

        $accountService = new AccountService();

        foreach ($accountService->getForUser($accountId) as $account) {
            $obj = new \stdClass();
            $obj->id = $account->id;
            $obj->name = $account->clientName . ' - ' . $account->name;

            $outItems[] = $obj;
        }

        $this->JsonResponse->setStatus(0);
        $this->JsonResponse->setData($outItems);
        $this->JsonResponse->setCsrf(SessionUtil::getSessionKey());

        Json::returnJson($this->JsonResponse);
    }

    /**
     * Preparar los elementos para devolverlos
     *
     * @param array $items
     * @return array
     */
    protected function prepareItems(array $items)
    {
        $outItems = [];

        /** @var DataModelInterface $item */
        foreach ($items as $item) {
            $obj = new \stdClass();
            $obj->id = $item->getId();
            $obj->name = $item->getName();

            $outItems[] = $obj;
        }

        return $outItems;
    }
}