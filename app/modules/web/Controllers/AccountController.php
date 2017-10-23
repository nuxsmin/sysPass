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

use SP\Core\Acl;
use SP\Core\Exceptions\ValidationException;
use SP\Forms\AccountForm;
use SP\Modules\Web\Controllers\Helpers\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\AccountService;
use SP\Controller\ControllerBase;
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\SessionUtil;
use SP\Http\Request;
use SP\Http\Response;
use SP\Mgmt\Files\FileUtil;
use SP\Modules\Web\Controllers\Helpers\AccountHelper;
use SP\Modules\Web\Controllers\Helpers\AccountSearchHelper;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\CustomFieldService;

/**
 * Class AccountController
 *
 * @package SP\Modules\Web\Controllers
 */
class AccountController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;

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
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
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
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            // FIXME
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * View action
     *
     * @param int $id Account's ID
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
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     *
     * @param int $id Account's ID
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
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
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
            $this->view->assign('formAction', 'account/saveCreate');

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.new', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Copy action
     *
     * @param int $id Account's ID
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
            $this->view->assign('formAction', 'account/saveCopy');

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.copy', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Edit action
     *
     * @param int $id Account's ID
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
            $this->view->assign('formAction', 'account/saveEdit');

            $AccountHelper->getAccount()->incrementViewCounter();
            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.edit', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Delete action
     *
     * @param int $id Account's ID
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
            $this->view->assign('formAction', 'account/saveDelete');

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.delete', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Obtener los datos para mostrar el interface para modificar la clave de cuenta
     *
     * @param int $id Account's ID
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
            $this->view->assign('formAction', 'account/saveEditPass');

            $this->view->assign('accountPassDateChange', gmdate('Y-m-d', $AccountHelper->getAccount()->getAccountData()->getAccountPassDateChange()));

            $this->eventDispatcher->notifyEvent('show.account.editpass', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Obtener los datos para mostrar el interface para ver cuenta en fecha concreta
     *
     * @param int $id Account's ID
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
            $this->view->assign('formAction', 'account/saveRestore');

            $this->view->assign('isView', true);
            $AccountHelper->getAccount()->setAccountIsHistory(1);

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.viewhistory', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Obtener los datos para mostrar el interface de solicitud de cambios en una cuenta
     *
     * @param int $id Account's ID
     */
    public function requestAccessAction($id)
    {
        try {
            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $AccountHelper->setAccountDataHistory($id, ActionsInterface::ACTION_ACC_REQUEST);

            $this->view->addTemplate('request');
            $this->view->assign('formAction', 'account/saveRequest');

            $this->eventDispatcher->notifyEvent('show.account.request', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->showError(self::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Display account's password
     *
     * @param int $id        Account's ID
     * @param int $isHistory The account's ID refers to history
     */
    public function viewPassAction($id, $isHistory)
    {
        try {
            $accountService = new AccountService();
            $accountPassHelper = new AccountPasswordHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

            $account = $isHistory === 0 ? $accountService->getAccountPass($id) : $accountService->getAccountPassHistory($id);

            $data = [
                'acclogin' => $account->getAccountLogin(),
                'accpass' => $accountPassHelper->getPassword($account, $this->acl, AccountPasswordHelper::TYPE_FULL),
                'useimage' => $this->configData->isAccountPassToImage(),
                'html' => $this->render()
            ];

            $this->eventDispatcher->notifyEvent('show.account.pass', $this);

            $this->returnJsonResponse(0, '', $data);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(0, $e->getMessage());
        }
    }

    /**
     * Copy account's password
     *
     * @param int $id        Account's ID
     * @param int $isHistory The account's ID refers to history
     */
    public function copyPassAction($id, $isHistory)
    {
        $accountService = new AccountService();
        $accountPassHelper = new AccountPasswordHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        $account = $isHistory === 0 ? $accountService->getAccountPass($id) : $accountService->getAccountPassHistory($id);

        $data = [
            'accpass' => $accountPassHelper->getPassword($account, $this->acl, AccountPasswordHelper::TYPE_NORMAL),
        ];

        $this->returnJsonResponse(0, '', $data);
    }

    /**
     * Saves copy action
     */
    public function saveCopyAction()
    {
        $this->saveCreateAction();
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        try {
            $form = new AccountForm();
            $form->validate(ActionsInterface::ACTION_ACC_NEW);
            $form->getItemData()->setAccountUserId($this->userData->getUserId());

            $accountService = new AccountService();
            $account = $accountService->createAccount($form->getItemData());

            $customFieldService = new CustomFieldService();
            $customFieldService->addCustomFieldData(Request::analyze('customfield'), $account->getId(), ActionsInterface::ACTION_ACC);

            $accountService->logAccountAction($account->getId(), ActionsInterface::ACTION_ACC_NEW);

            $this->eventDispatcher->notifyEvent('add.account', $this);

            $this->returnJsonResponse(0, __('Cuenta creada', false), ['itemId' => $account->getId(), 'nextAction' => Acl::getActionRoute(ActionsInterface::ACTION_ACC_EDIT)]);
        } catch (ValidationException $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Saves edit action
     *
     * @param $id Account's ID
     */
    public function saveEditAction($id)
    {
        try {
            $form = new AccountForm($id);
            $form->validate(ActionsInterface::ACTION_ACC_EDIT);

            $accountService = new AccountService();
            $accountService->editAccount($form->getItemData());

            $customFieldService = new CustomFieldService();
            $customFieldService->updateCustomFieldData(Request::analyze('customfield'), $id, ActionsInterface::ACTION_ACC);

            $accountService->logAccountAction($id, ActionsInterface::ACTION_ACC_EDIT);

            $this->eventDispatcher->notifyEvent('edit.account', $this);

            $this->returnJsonResponse(0, __('Cuenta actualizada', false), ['itemId' => $id, 'nextAction' => Acl::getActionRoute(ActionsInterface::ACTION_ACC_VIEW)]);
        } catch (ValidationException $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Saves edit action
     *
     * @param $id Account's ID
     */
    public function saveEditPassAction($id)
    {
        try {
            $form = new AccountForm($id);
            $form->validate(ActionsInterface::ACTION_ACC_EDIT_PASS);

            $accountService = new AccountService();
            $accountService->editAccountPass($form->getItemData());

            $accountService->logAccountAction($id, ActionsInterface::ACTION_ACC_EDIT_PASS);

            $this->eventDispatcher->notifyEvent('edit.account.pass', $this);

            $this->returnJsonResponse(0, __('Clave actualizada', false), ['itemId' => $id, 'nextAction' => Acl::getActionRoute(ActionsInterface::ACTION_ACC_VIEW)]);
        } catch (ValidationException $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Saves restore action
     *
     * @param int $historyId Account's history ID
     * @param int $id        Account's ID
     */
    public function saveEditRestoreAction($historyId, $id)
    {
        try {
            $accountService = new AccountService();
            $accountService->editAccountRestore($historyId, $id);

            $accountService->logAccountAction($id, ActionsInterface::ACTION_ACC_EDIT_RESTORE);

            $this->eventDispatcher->notifyEvent('edit.account.restore', $this);

            $this->returnJsonResponse(0, __('Cuenta restaurada', false), ['itemId' => $id, 'nextAction' => Acl::getActionRoute(ActionsInterface::ACTION_ACC_VIEW)]);
        } catch (ValidationException $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Saves delete action
     *
     * @param int $id Account's ID
     */
    public function saveDeleteAction($id)
    {
        try {
            $accountService = new AccountService();

            if ($accountService->deleteAccount($id)) {
                $customFieldService = new CustomFieldService();
                $customFieldService->deleteCustomFieldData($id, ActionsInterface::ACTION_ACC);

                // FIXME: obtener cuenta antes de eliminar
//                $accountService->logAccountAction($id, ActionsInterface::ACTION_ACC_DELETE);

                $this->eventDispatcher->notifyEvent('delete.account', $this);

                $this->returnJsonResponse(0, __('Cuenta eliminada', false), ['nextAction' => Acl::getActionRoute(ActionsInterface::ACTION_ACC_SEARCH)]);
            }
        } catch (ValidationException $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Initialize class
     */
    protected function initialize()
    {
        $this->checkLoggedIn();
    }
}