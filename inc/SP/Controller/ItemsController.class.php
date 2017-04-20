<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Controller;

use SP\Account\AccountUtil;
use SP\Core\ItemsTypeInterface;
use SP\Core\SessionUtil;
use SP\DataModel\DataModelInterface;
use SP\Http\Request;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Util\Json;

/**
 * Class ItemsController
 *
 * @package SP\Controller
 */
class ItemsController implements ItemControllerInterface
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
     * Realizar la acción solicitada en la la petición HTTP
     */
    public function doAction()
    {
        $itemType = Request::analyze('itemType', false);

        $this->JsonResponse->setStatus(0);
        $this->JsonResponse->setData($this->getItems($itemType));
        $this->JsonResponse->setCsrf(SessionUtil::getSessionKey());

        Json::returnJson($this->JsonResponse);
    }

    /**
     * Devuelve los elementos solicitados
     *
     * @param $itemType int El tipo de elemento a devolver
     * @return array
     */
    protected function getItems($itemType)
    {
        switch ($itemType) {
            case ItemsTypeInterface::ITEM_CATEGORIES:
                return $this->getCategories();
            case ItemsTypeInterface::ITEM_CUSTOMERS:
                return $this->getCustomers();
            case ItemsTypeInterface::ITEM_CUSTOMERS_USER:
                return $this->getCustomersForUser();
            case ItemsTypeInterface::ITEM_ACCOUNTS_USER:
                return $this->getAccountsForUser();
            default:
                return [];
        }
    }

    /**
     * Devuelve las categorías disponibles
     *
     * @return array
     */
    protected function getCategories()
    {
        return $this->prepareItems(Category::getItem()->getAll());
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

    /**
     * Devuelve los clientes disponibles
     *
     * @return array
     */
    protected function getCustomers()
    {
        return $this->prepareItems(Customer::getItem()->getAll());
    }

    /**
     * Devolver los clientes visibles por el usuario
     *
     * @return array
     */
    protected function getCustomersForUser()
    {
        return Customer::getItem()->getItemsForSelectByUser();
    }

    /**
     * Devolver las cuentas visubles por el usuario
     *
     * @return array
     */
    protected function getAccountsForUser()
    {
        $outItems = [];

        foreach (AccountUtil::getAccountsForUser($this->itemId) as $account) {
            $obj = new \stdClass();
            $obj->id = $account->account_id;
            $obj->name = $account->customer_name . ' - ' . $account->account_name;

            $outItems[] = $obj;
        }

        return $outItems;
    }

    /**
     * Comprobaciones antes de realizar una acción
     */
    protected function preActionChecks()
    {
    }
}