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

use SP\DataModel\AccountData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\AccountPasswordHelper;
use SP\Services\AccountService;
use SP\Controller\ControllerBase;
use SP\Core\ActionsInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\ItemException;
use SP\Core\Exceptions\SPException;
use SP\Core\SessionUtil;
use SP\Http\Request;
use SP\Http\Response;
use SP\Mgmt\Files\FileUtil;
use SP\Mgmt\Users\UserPass;
use SP\Modules\Web\Controllers\Helpers\AccountHelper;
use SP\Modules\Web\Controllers\Helpers\AccountSearchHelper;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Util\ImageUtil;
use SP\Util\Json;

/**
 * Class AccountController
 *
 * @package SP\Modules\Web\Controllers
 */
class AccountController extends ControllerBase implements CrudControllerInterface
{
    /**
     * Index action
     */
    public function indexAction()
    {
        try {
            $AccountSearchHelper = new AccountSearchHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountSearchHelper->getSearchBox();
            $AccountSearchHelper->getSearch();

            $this->eventDispatcher->notifyEvent('show.account.search', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Search action
     */
    public function searchAction()
    {
        try {
            $AccountSearchHelper = new AccountSearchHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountSearchHelper->getSearch();

            $this->eventDispatcher->notifyEvent('show.account.search', $this);

            $data = [
                'sk' => SessionUtil::getSessionKey(),
                'html' => $this->render()
            ];

            Response::printJson($data, 0);
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * View action
     *
     * @param $id
     */
    public function viewAction($id)
    {
        try {
            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountHelper->setAccountData($id, ActionsInterface::ACTION_ACC_VIEW);

            // Obtener los datos de la cuenta antes y comprobar el acceso
            if (!$AccountHelper->checkAccess($this)) {
                return;
            }

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleNormal',
                    'name' => __('Detalles de Cuenta'),
                    'icon' => $this->icons->getIconView()->getIcon()
                ]
            );

            $this->view->assign('isView', true);

            $AccountHelper->getAccount()->incrementViewCounter();
            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.view', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     *
     * @param int $id
     * @return bool|void
     */
    public function listFilesAction($id)
    {
        if (!$this->configData->isFilesEnabled()) {
            die(__('Gestión de archivos deshabilitada'));
        }

        try {
            $this->setAction(ActionsInterface::ACTION_ACC_FILES);

            $this->view->addTemplate('files-list', 'account');

            $this->view->assign('accountId', $id);
            $this->view->assign('deleteEnabled', Request::analyze('del', 0));
            $this->view->assign('files', FileUtil::getAccountFiles($id));
            $this->view->assign('sk', SessionUtil::getSessionKey(true));

            if (!is_array($this->view->files) || count($this->view->files) === 0) {
                return;
            }

            $this->eventDispatcher->notifyEvent('show.account.listfiles', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Create action
     */
    public function createAction()
    {
        try {
            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountHelper->setActionId(ActionsInterface::ACTION_ACC_NEW);

            // Obtener los datos de la cuenta antes y comprobar el acceso
            if (!$AccountHelper->checkAccess($this)) {
                return;
            }

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleGreen',
                    'name' => __('Nueva Cuenta'),
                    'icon' => $this->icons->getIconAdd()->getIcon()
                ]
            );

//        SessionFactory::setLastAcountId(0);
            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.new', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Copy action
     * @param $id
     */
    public function copyAction($id)
    {
        try {
            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountHelper->setAccountData($id, ActionsInterface::ACTION_ACC_COPY);

            // Obtener los datos de la cuenta antes y comprobar el acceso
            if (!$AccountHelper->checkAccess($this)) {
                return;
            }

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleGreen',
                    'name' => __('Nueva Cuenta'),
                    'icon' => $this->icons->getIconAdd()->getIcon()
                ]
            );

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.copy', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Edit action
     *
     * @param $id
     */
    public function editAction($id)
    {
        try {
            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountHelper->setAccountData($id, ActionsInterface::ACTION_ACC_EDIT);

            // Obtener los datos de la cuenta antes y comprobar el acceso
            if (!$AccountHelper->checkAccess($this)) {
                return;
            }

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleOrange',
                    'name' => __('Editar Cuenta'),
                    'icon' => $this->icons->getIconEdit()->getIcon()
                ]
            );

            $AccountHelper->getAccount()->incrementViewCounter();
            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.edit', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Save or modify action
     *
     * @param $id
     */
    public function saveAction($id)
    {
        // TODO: Implement saveAction() method.
    }

    /**
     * Delete action
     *
     * @param $id
     */
    public function deleteAction($id)
    {
        try {
            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountHelper->setAccountData($id, ActionsInterface::ACTION_ACC_DELETE);

            // Obtener los datos de la cuenta antes y comprobar el acceso
            if (!$AccountHelper->checkAccess($this)) {
                return;
            }

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleRed',
                    'name' => __('Eliminar Cuenta'),
                    'icon' => $this->icons->getIconDelete()->getIcon()
                ]
            );

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.delete', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Obtener los datos para mostrar el interface para modificar la clave de cuenta
     *
     * @param $id
     */
    public function editPassAction($id)
    {
        try {
            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountHelper->setAccountData($id, ActionsInterface::ACTION_ACC_EDIT_PASS);

            // Obtener los datos de la cuenta antes y comprobar el acceso
            if (!$AccountHelper->checkAccess($this)) {
                return;
            }

            $this->view->addTemplate('account-editpass');
            $this->view->assign('title',
                [
                    'class' => 'titleOrange',
                    'name' => __('Modificar Clave de Cuenta'),
                    'icon' => $this->icons->getIconEditPass()->getIcon()
                ]
            );

            $this->view->assign('accountPassDateChange', gmdate('Y-m-d', $AccountHelper->getAccount()->getAccountData()->getAccountPassDateChange()));

            $this->eventDispatcher->notifyEvent('show.account.editpass', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Obtener los datos para mostrar el interface para ver cuenta en fecha concreta
     *
     * @param $id
     */
    public function viewHistoryAction($id)
    {
        try {
            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountHelper->setAccountDataHistory($id, ActionsInterface::ACTION_ACC_VIEW_HISTORY);

            // Obtener los datos de la cuenta antes y comprobar el acceso
            if (!$AccountHelper->checkAccess($this)) {
                return;
            }

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleNormal',
                    'name' => __('Detalles de Cuenta'),
                    'icon' => 'access_time'
                ]
            );

            $this->view->assign('isView', true);
            $AccountHelper->getAccount()->setAccountIsHistory(1);

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.viewhistory', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Obtener los datos para mostrar el interface de solicitud de cambios en una cuenta
     *
     * @param $id
     */
    public function requestAccessAction($id)
    {
        try {
            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountHelper->setAccountDataHistory($id, ActionsInterface::ACTION_ACC_REQUEST);

            $this->view->addTemplate('request');

            $this->eventDispatcher->notifyEvent('show.account.request', $this);

            $this->view();
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Mostrar la clave de una cuenta
     *
     * @param $id
     * @param $isFull
     * @param $isLinked
     * @throws ItemException
     */
    public function viewPassAction($id, $isFull, $isLinked)
    {
//        $isHistory = Request::analyze('isHistory', false);

        $accountService = new AccountService();
        $account = $accountService->getAccountPass($id);

        $accountPassHelper = new AccountPasswordHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        if ($isFull) {
            $pass = $accountPassHelper->getPassword($accountService->getAccountPass($id), $this->acl, AccountPasswordHelper::TYPE_FULL);
        } else {
            $pass = $accountPassHelper->getPassword($accountService->getAccountPass($id), $this->acl, AccountPasswordHelper::TYPE_NORMAL);
        }

        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus(0);

        $data = [
            'acclogin' => $account->getAccountLogin(),
            'accpass' => $pass,
            'useimage' => $this->configData->isAccountPassToImage(),
            'html' => $this->render()
        ];

        $jsonResponse->setData($data);

        Json::returnJson($jsonResponse);
    }

    /**
     * Initialize class
     */
    protected function initialize()
    {
        $this->checkLoggedIn();
    }
}