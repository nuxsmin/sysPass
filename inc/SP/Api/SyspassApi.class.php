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

namespace SP\Api;

defined('APP_ROOT') || die();

use SP\Account\Account;
use SP\Account\AccountAcl;
use SP\Account\AccountSearch;
use SP\Account\AccountUtil;
use SP\Core\ActionsInterface;
use SP\Core\Backup;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;
use SP\DataModel\ItemSearchData;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Categories\CategorySearch;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Customers\CustomerSearch;

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
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAccountPassword()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_VIEW_PASS);

        $accountId = $this->getParam('id', true, 0);

        $AccountData = new AccountExtData($accountId);
        $Account = new Account($AccountData);
        $Account->getData();

        $AccountAcl = new AccountAcl($Account, ActionsInterface::ACTION_ACC_VIEW_PASS);
        $Acl = $AccountAcl->getAcl();

        if (!$Acl->isShowViewPass()) {
            throw new SPException(SPException::SP_WARNING, __('Acceso no permitido', false));
        }

        $Account->getAccountPassData();
        $Account->incrementDecryptCounter();

        $LogMessage = $this->Log->getLogMessage();
        $LogMessage->setAction(__('Ver Clave', false));
        $LogMessage->addDetails(__('ID', false), $accountId);
        $LogMessage->addDetails(__('Cuenta', false), $AccountData->getCustomerName() . ' / ' . $AccountData->getAccountName());
        $LogMessage->addDetails(__('Origen', false), 'API');
        $this->Log->writeLog();

        $mPass = $this->getMPass();
        $securedKey = Crypt::unlockSecuredKey($AccountData->getAccountKey(), $mPass);

        $ret = [
            'itemId' => $accountId,
            'pass' => Crypt::decrypt($AccountData->getAccountPass(), $securedKey, $mPass)
        ];

        if ($this->getParam('details', false, 0)) {
            // Para evitar los caracteres especiales
            $AccountData->setAccountPass('');
            $AccountData->setAccountKey('');

            $ret['details'] = $AccountData;
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

        $AccountAcl = new AccountAcl($Account, ActionsInterface::ACTION_ACC_VIEW);
        $Acl = $AccountAcl->getAcl();

        if (!$Acl->isShowView()) {
            throw new SPException(SPException::SP_WARNING, __('Acceso no permitido', false));
        }

        $Account->incrementViewCounter();

        return $this->wrapJSON($ret);
    }

    /**
     * Añadir una nueva cuenta
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addAccount()
    {
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

        $Account->setPasswordEncrypted($this->getMPass());
        $Account->createAccount(false);

        $LogMessage = $this->Log->getLogMessage();
        $LogMessage->setAction(__('Crear Cuenta', false));
        $LogMessage->addDescription(__('Cuenta creada', false));
        $LogMessage->addDetails(__('Nombre', false), $AccountData->getAccountName());
        $LogMessage->addDetails(__('Origen', false), 'API');
        $this->Log->writeLog();

        $ret = [
            'itemId' => $AccountData->getAccountId(),
            'result' => $LogMessage->getDescription(true),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Eliminar una cuenta
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteAccount()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_DELETE);

        $accountId = $this->getParam('id', true);

        $AccountData = AccountUtil::getAccountNameById($accountId);

        if ($AccountData === false) {
            throw new SPException(SPException::SP_ERROR, __('Cuenta no encontrada', false));
        }

        $Account = new Account();
        $Account->deleteAccount($accountId);

        $LogMessage = $this->Log->getLogMessage();
        $LogMessage->setAction(__('Eliminar Cuenta', false));
        $LogMessage->addDescription(__('Cuenta eliminada', false));
        $LogMessage->addDetails(__('Nombre', false), $AccountData->account_name);
        $LogMessage->addDetails(__('Origen', false), 'API');
        $this->Log->writeLog();

        $ret = [
            'itemId' => $accountId,
            'result' => $LogMessage->getDescription(true),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Devuelve el listado de categorías
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\InvalidClassException
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
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addCategory()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_MGM_CATEGORIES);

        $CategoryData = new CategoryData();
        $CategoryData->setCategoryName($this->getParam('name', true));
        $CategoryData->setCategoryDescription($this->getParam('description'));

        Category::getItem($CategoryData)->add();

        $LogMessage = $this->Log->getLogMessage();
        $LogMessage->setAction(__('Crear Categoría', false));
        $LogMessage->addDescription(__('Categoría creada', false));
        $LogMessage->addDetails(__('Nombre', false), $CategoryData->getCategoryName());
        $LogMessage->addDetails(__('Origen', false), 'API');
        $this->Log->writeLog();

        $ret = [
            'itemId' => $CategoryData->getCategoryId(),
            'result' => $LogMessage->getDescription(true),
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

        $CategoryData = Category::getItem()->getById($id);

        if (!is_object($CategoryData)) {
            throw new SPException(SPException::SP_ERROR, __('Categoría no encontrada', false));
        }

        Category::getItem()->delete($id);

        $LogMessage = $this->Log->getLogMessage();
        $LogMessage->setAction(__('Eliminar Categoría', false));
        $LogMessage->addDescription(__('Categoría eliminada', false));
        $LogMessage->addDetails(__('Nombre', false), $CategoryData->getCategoryName());
        $LogMessage->addDetails(__('Origen', false), 'API');
        $this->Log->writeLog();

        $ret = [
            'itemId' => $id,
            'result' => $LogMessage->getDescription(true),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Devuelve el listado de clientes
     *
     * @return string La cadena en formato JSON
     * @throws \SP\Core\Exceptions\InvalidClassException
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
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addCustomer()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_MGM_CUSTOMERS);

        $CustomerData = new CustomerData();
        $CustomerData->setCustomerName($this->getParam('name', true));
        $CustomerData->setCustomerDescription($this->getParam('description'));

        Customer::getItem($CustomerData)->add();

        $LogMessage = $this->Log->getLogMessage();
        $LogMessage->setAction(__('Crear Cliente', false));
        $LogMessage->addDescription(__('Cliente creado', false));
        $LogMessage->addDetails(__('Nombre', false), $CustomerData->getCustomerName());
        $LogMessage->addDetails(__('Origen', false), 'API');
        $this->Log->writeLog();

        $ret = [
            'itemId' => $CustomerData->getCustomerId(),
            'result' => $LogMessage->getDescription(true),
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

        $CustomerData = Customer::getItem()->getById($id);

        if (!is_object($CustomerData)) {
            throw new SPException(SPException::SP_ERROR, __('Cliente no encontrado', false));
        }

        Customer::getItem()->delete($id);

        $LogMessage = $this->Log->getLogMessage();
        $LogMessage->setAction(__('Eliminar Cliente', false));
        $LogMessage->addDescription(__('Cliente eliminado', false));
        $LogMessage->addDetails(__('Nombre', false), $CustomerData->getCustomerName());
        $LogMessage->addDetails(__('Origen', false), 'API');
        $this->Log->writeLog();

        $ret = [
            'itemId' => $id,
            'result' => $LogMessage->getDescription(true),
            'resultCode' => 0
        ];

        return $this->wrapJSON($ret);
    }

    /**
     * Realizar un backup de sysPass
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     */
    public function backup()
    {
        $ret = [
            'result' => __('Proceso de backup finalizado'),
            'resultCode' => 0
        ];

        if (!Backup::doBackup()) {
            $ret = [
                'result' => __('Error al realizar el backup'),
                'hint' => __('Revise el registro de eventos para más detalles'),
                'resultCode' => 1
            ];
        }

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
                    'id' => __('Id de la cuenta'),
                    'tokenPass' => __('Clave del token'),
                    'details' => __('Devolver detalles en la respuesta')
                ]
            ],
            'getAccountSearch' => [
                'id' => ActionsInterface::ACTION_ACC_SEARCH,
                'help' => [
                    'text' => __('Texto a buscar'),
                    'count' => __('Número de resultados a mostrar'),
                    'categoryId' => __('Id de categoría a filtrar'),
                    'customerId' => __('Id de cliente a filtrar')
                ]
            ],
            'getAccountData' => [
                'id' => ActionsInterface::ACTION_ACC_VIEW,
                'help' => [
                    'id' => __('Id de la cuenta')
                ]
            ],
            'deleteAccount' => [
                'id' => ActionsInterface::ACTION_ACC_DELETE,
                'help' => [
                    'id' => __('Id de la cuenta')
                ]
            ],
            'addAccount' => [
                'id' => ActionsInterface::ACTION_ACC_NEW,
                'help' => [
                    'tokenPass' => __('Clave del token'),
                    'name' => __('Nombre de cuenta'),
                    'categoryId' => __('Id de categoría'),
                    'customerId' => __('Id de cliente'),
                    'pass' => __('Clave'),
                    'login' => __('Usuario de acceso'),
                    'url' => __('URL o IP de acceso'),
                    'notes' => __('Notas sobre la cuenta')
                ]
            ],
            'backup' => [
                'id' => ActionsInterface::ACTION_CFG_BACKUP,
                'help' => ''
            ],
            'getCategories' => [
                'id' => ActionsInterface::ACTION_MGM_CATEGORIES,
                'help' => [
                    'name' => __('Nombre de categoría a buscar'),
                    'count' => __('Número de resultados a mostrar')
                ]
            ],
            'addCategory' => [
                'id' => ActionsInterface::ACTION_MGM_CATEGORIES,
                'help' => [
                    'name' => __('Nombre de la categoría'),
                    'description' => __('Descripción de la categoría')
                ]
            ],
            'deleteCategory' => [
                'id' => ActionsInterface::ACTION_MGM_CATEGORIES,
                'help' => [
                    'id' => __('Id de categoría')
                ]
            ],
            'getCustomers' => [
                'id' => ActionsInterface::ACTION_MGM_CUSTOMERS,
                'help' => [
                    'name' => __('Nombre de cliente a buscar'),
                    'count' => __('Número de resultados a mostrar')
                ]
            ],
            'addCustomer' => [
                'id' => ActionsInterface::ACTION_MGM_CUSTOMERS,
                'help' => [
                    'name' => __('Nombre del cliente'),
                    'description' => __('Descripción del cliente')
                ]
            ],
            'deleteCustomer' => [
                'id' => ActionsInterface::ACTION_MGM_CUSTOMERS,
                'help' => [
                    'id' => __('Id de cliente')
                ]
            ]
        ];
    }

    /**
     * @return bool
     */
    protected function passIsNeeded()
    {
        return $this->actionId === ActionsInterface::ACTION_ACC_VIEW_PASS
            || $this->actionId === ActionsInterface::ACTION_ACC_NEW;
    }
}