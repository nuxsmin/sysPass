<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Api;

use SP\Account\Account;
use SP\DataModel\AccountData;
use SP\Account\AccountSearch;
use SP\Core\Acl;
use SP\Core\ActionsInterface;
use SP\Core\Crypt;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;
use SP\DataModel\ItemSearchData;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Categories\CategorySearch;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Customers\CustomerSearch;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class Api para la gestión de peticiones a la API de sysPass
 *
 * @package SP
 */
class SyspassApi extends ApiBase
{
    /**
     * Devolver la clave de una cuenta
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAccountPassword()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_VIEW_PASS);

        $accountId = $this->getParam('id', true, 0);

        $Account = new Account(new AccountData($accountId));
        $Account->getData();

        $Acl = new Acl(ActionsInterface::ACTION_ACC_VIEW_PASS);
        $Acl->setAccountData($Account->getAccountDataForACL());

        $access = ($Acl->checkAccountAccess()
            && Acl::checkUserAccess(ActionsInterface::ACTION_ACC_VIEW_PASS));

        if (!$access) {
            throw new SPException(SPException::SP_WARNING, _('Acceso no permitido'));
        }

        $Account->getAccountPassData();
        $Account->incrementDecryptCounter();

        $ret = [
            'accountId' => $accountId,
            'pass' => Crypt::getDecrypt($Account->getAccountData()->getAccountPass(), $Account->getAccountData()->getAccountIV(), $this->mPass)
        ];

        if ($this->getParam('details', true, 0)) {
            $ret['details'] = $Account->getAccountData();
        }

        return $this->wrapJSON($ret);
    }

    /**
     * Devolver los resultados de una búsqueda
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAccountSearch()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_SEARCH);

        $Search = new AccountSearch();
        $Search->setTxtSearch($this->getParam('text'));
        $Search->setLimitCount($this->getParam('count', false, 100));
        $Search->setCategoryId($this->getParam('categoryId', false, 0));
        $Search->setCustomerId($this->getParam('customerId', false, 0));

        $ret = $Search->getAccounts();

        debugLog($ret);

        return $this->wrapJSON($ret);
    }

    /**
     * Devolver los detalles de una cuenta
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAccountData()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_VIEW);

        $accountId = $this->getParam('id', true, 0);

        $Account = new Account(new AccountExtData($accountId));
        $ret = $Account->getData();

        $Acl = new Acl(ActionsInterface::ACTION_ACC_VIEW);
        $Acl->setAccountData($Account->getAccountDataForACL());

        $access = ($Acl->checkAccountAccess()
            && Acl::checkUserAccess(ActionsInterface::ACTION_ACC_VIEW));

        if (!$access) {
            throw new SPException(SPException::SP_WARNING, _('Acceso no permitido'));
        }

        $Account->incrementViewCounter();

        return $this->wrapJSON($ret);
    }

    /**
     * Añadir una nueva cuenta
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addAccount()
    {
        $this->checkAuth();
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_NEW);

        $AccountData = new AccountExtData();
        $AccountData->setAccountUserId($this->UserData->getUserId());
        $AccountData->setAccountUserGroupId($this->UserData->getUserGroupId());
        $AccountData->setAccountName($this->getParam('name', true));
        $AccountData->setAccountPass($this->getParam('pass', true));
        $AccountData->setAccountCustomerId($this->getParam('customerId', true));
        $AccountData->setAccountCategoryId($this->getParam('categoryId', true));
        $AccountData->setAccountLogin($this->getParam('login', true));
        $AccountData->setAccountUrl($this->getParam('url'));
        $AccountData->setAccountNotes($this->getParam('notes'));

        $Account = new Account($AccountData);

        $Account->createAccount();

        $ret = [
            'accountId' => $AccountData->getAccountId(),
            'result' => _('Cuenta creada'),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Eliminar una cuenta
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteAccount()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_DELETE);

        $accountId = $this->getParam('id', true);

        $Account = new Account();
        $Account->deleteAccount($accountId);

        $ret = [
            'accountId' => $accountId,
            'result' => _('Cuenta eliminada'),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Devuelve el listado de categorías
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getCategories()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_MGM_CATEGORIES);

        $SearchData = new ItemSearchData();
        $SearchData->setSeachString($this->getParam('name', false, ''));
        $SearchData->setLimitCount($this->getParam('count', false, 100));

        $ret = CategorySearch::getItem()->getMgmtSearch($SearchData);

        return $this->wrapJSON($ret);
    }

    /**
     * Añade una nueva categoría
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addCategory()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_MGM_CATEGORIES);

        $CategoryData = new CategoryData();
        $CategoryData->setCategoryName($this->getParam('name', true));
        $CategoryData->setCategoryDescription($this->getParam('description'));

        $Category = Category::getItem($CategoryData)->add();

        $ret = [
            'categoryId' => $Category->getItemData()->getCategoryId(),
            'result' => _('Categoría creada'),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Elimina una categoría
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteCategory()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_MGM_CATEGORIES);

        $id = $this->getParam('id', true);
        Category::getItem()->delete($id);

        $ret = [
            'categoryId' => $id,
            'result' => _('Categoría eliminada'),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Devuelve el listado de clientes
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getCustomers()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_MGM_CUSTOMERS);

        $SearchData = new ItemSearchData();
        $SearchData->setSeachString($this->getParam('name', false, ''));
        $SearchData->setLimitCount($this->getParam('count', false, 100));

        $ret = CustomerSearch::getItem()->getMgmtSearch($SearchData);

        return $this->wrapJSON($ret);
    }

    /**
     * Añade un nuevo cliente
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addCustomer()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_MGM_CUSTOMERS);

        $CustomerData = new CustomerData();
        $CustomerData->setCustomerName($this->getParam('name', true));
        $CustomerData->setCustomerDescription($this->getParam('description'));

        $Customer = Customer::getItem($CustomerData)->add();

        $ret = [
            'customerId' => $Customer->getItemData()->getCustomerId(),
            'result' => _('Cliente creado'),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Elimina un cñiente
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteCustomer()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_MGM_CUSTOMERS);

        $id = $this->getParam('id', true);
        Customer::getItem()->delete($id);

        $ret = [
            'customerId' => $id,
            'result' => _('Cliente eliminado'),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Devuelve la ayuda para una acción
     *
     * @param string $action
     * @return array
     */
    public function getHelp($action)
    {
        return $this->getActions()[$action]['help'];
    }

    /**
     * Devuelve las acciones que implementa la API
     *
     * @return array
     */
    public function getActions()
    {
        return [
            'getAccountPassword' => [
                'id' => ActionsInterface::ACTION_ACC_VIEW_PASS,
                'help' => [
                    'id' => _('Id de la cuenta'),
                    'userPass' => _('Clave del usuario asociado al token'),
                    'details' => _('Devolver detalles en la respuesta')
                ]
            ],
            'getAccountSearch' => [
                'id' => ActionsInterface::ACTION_ACC_SEARCH,
                'help' => [
                    'text' => _('Texto a buscar'),
                    'count' => _('Número de resultados a mostrar'),
                    'categoryId' => _('Id de categoría a filtrar'),
                    'customerId' => _('Id de cliente a filtrar')
                ]
            ],
            'getAccountData' => [
                'id' => ActionsInterface::ACTION_ACC_VIEW,
                'help' => [
                    'id' => _('Id de la cuenta'),
                    'userPass' => _('Clave del usuario asociado al token')
                ]
            ],
            'deleteAccount' => [
                'id' => ActionsInterface::ACTION_ACC_DELETE,
                'help' => [
                    'id' => _('Id de la cuenta')
                ]
            ],
            'addAccount' => [
                'id' => ActionsInterface::ACTION_ACC_NEW,
                'help' => [
                    'name' => _('Nombre de cuenta'),
                    'categoryId' => _('Id de categoría'),
                    'customerId' => _('Id de cliente'),
                    'pass' => _('Clave'),
                    'login' => _('Usuario de acceso'),
                    'url' => _('URL o IP de acceso'),
                    'notes' => _('Notas sobre la cuenta')
                ]
            ],
            'backup' => [
                'id' => ActionsInterface::ACTION_CFG_BACKUP,
                'help' => [
                ]
            ],
            'getCategories' => [
                'id' => ActionsInterface::ACTION_MGM_CATEGORIES,
                'help' => [
                    'name' => _('Nombre de categoría a buscar'),
                    'count' => _('Número de resultados a mostrar')
                ]
            ],
            'addCategory' => [
                'id' => ActionsInterface::ACTION_MGM_CATEGORIES,
                'help' => [
                    'name' => _('Nombre de la categoría'),
                    'description' => _('Descripción de la categoría')
                ]
            ],
            'deleteCategory' => [
                'id' => ActionsInterface::ACTION_MGM_CATEGORIES,
                'help' => [
                    'id' => _('Id de categoría')
                ]
            ],
            'getCustomers' => [
                'id' => ActionsInterface::ACTION_MGM_CUSTOMERS,
                'help' => [
                    'name' => _('Nombre de cliente a buscar'),
                    'count' => _('Número de resultados a mostrar')
                ]
            ],
            'addCustomer' => [
                'id' => ActionsInterface::ACTION_MGM_CUSTOMERS,
                'help' => [
                    'name' => _('Nombre del cliente'),
                    'description' => _('Descripción del cliente')
                ]
            ],
            'deleteCustomer' => [
                'id' => ActionsInterface::ACTION_MGM_CUSTOMERS,
                'help' => [
                    'id' => _('Id de cliente')
                ]
            ]
        ];
    }
}