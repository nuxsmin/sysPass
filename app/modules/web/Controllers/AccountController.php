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

use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\Core\SessionUtil;
use SP\DataModel\AccountExtData;
use SP\Forms\AccountForm;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Mgmt\Files\FileUtil;
use SP\Modules\Web\Controllers\Helpers\AccountHelper;
use SP\Modules\Web\Controllers\Helpers\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Helpers\AccountSearchHelper;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\Account\AccountService;
use SP\Services\PublicLink\PublicLinkService;
use SP\Util\ErrorUtil;
use SP\Util\ImageUtil;
use SP\Util\Util;

/**
 * Class AccountController
 *
 * @package SP\Modules\Web\Controllers
 */
class AccountController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;
    use ItemTrait;

    /**
     * Index action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
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

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Search action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
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

            $this->returnJsonResponseData($data);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            // FIXME
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
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
            $AccountHelper->setAccountData($id, ActionsInterface::ACCOUNT_VIEW);

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

            $AccountHelper->getAccountService()->incrementViewCounter();
            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * View public link action
     *
     * @param string $hash Link's hash
     */
    public function viewLinkAction($hash)
    {
        $LayoutHelper = new LayoutHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
        $LayoutHelper->getPublicLayout('account-link', 'account');

        try {
            $publicLinkService = new PublicLinkService();
            $publicLinkData = $publicLinkService->getByHash($hash);

            if (time() < $publicLinkData->getPublicLinkDateExpire()
                && $publicLinkData->getPublicLinkCountViews() < $publicLinkData->getPublicLinkMaxCountViews()
            ) {
                $publicLinkService->addLinkView($publicLinkData);

//                if ($publicLinkData->isNotify()) {
//                    $Message = new NoticeMessage();
//                    $Message->setTitle(__('Enlace visualizado'));
//                    $Message->addDescription(sprintf('%s : %s', __('Cuenta'), $PublicLink->getItemId()));
//                    $Message->addDescription(sprintf('%s : %s', __('Origen'), $this->configData->isDemoEnabled() ? '*.*.*.*' : HttpUtil::getClientAddress(true)));
//                    $Message->addDescription(sprintf('%s : %s', __('Agente'), Request::getRequestHeaders('HTTP_USER_AGENT')));
//                    $Message->addDescription(sprintf('HTTPS : %s', Checks::httpsEnabled() ? 'ON' : 'OFF'));
//
//
//                    $NoticeData = new NoticeData();
//                    $NoticeData->setNoticeComponent(__('Cuentas'));
//                    $NoticeData->setNoticeDescription($Message);
//                    $NoticeData->setNoticeType(__('Información'));
//                    $NoticeData->setNoticeUserId($PublicLink->getPublicLinkUserId());
//
//                    Notice::getItem($NoticeData)->add();
//                }

                $accountService = new AccountService();
                $accountService->incrementViewCounter($publicLinkData->getPublicLinkItemId());
                $accountService->incrementDecryptCounter($publicLinkData->getPublicLinkItemId());

                /** @var Vault $vault */
                $vault = unserialize($publicLinkData->getPublicLinkData());

                /** @var AccountExtData $accountData */
                $accountData = Util::unserialize(AccountExtData::class, $vault->getData(PublicLinkService::getKeyForHash($this->config, $publicLinkData)));

                $this->view->assign('title',
                    [
                        'class' => 'titleNormal',
                        'name' => __('Detalles de Cuenta'),
                        'icon' => $this->icons->getIconView()->getIcon()
                    ]
                );

                $this->view->assign('isView', true);
                $this->view->assign('useImage', $this->configData->isPublinksImageEnabled() || $this->configData->isAccountPassToImage());

                if ($this->view->useImage) {
                    $this->view->assign('accountPassImage', ImageUtil::convertText($accountData->getAccountPass()));
                } else {
                    $this->view->assign('copyPassRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW_PASS));
                }

                $this->view->assign('accountData', $accountData);

                $this->eventDispatcher->notifyEvent('show.account.link', $this);
            } else {
                ErrorUtil::showErrorFull($this->view, ErrorUtil::ERR_PAGE_NO_PERMISSION, 'account-link');
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorFull($this->view, ErrorUtil::ERR_PAGE_NO_PERMISSION, 'account-link');
        }

        $this->view();
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     *
     * @param int $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function listFilesAction($id)
    {
        if (!$this->configData->isFilesEnabled()) {
            die(__('Gestión de archivos deshabilitada'));
        }

        try {
            $this->setAction(ActionsInterface::ACCOUNT_FILE);

            $this->view->addTemplate('files-list', 'account');

            $this->view->assign('accountId', $id);
            $this->view->assign('deleteEnabled', Request::analyze('del', 0));
            $this->view->assign('files', FileUtil::getAccountFiles($id));
            $this->view->assign('sk', SessionUtil::getSessionKey());
            $this->view->assign('fileViewRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_VIEW));
            $this->view->assign('fileDownloadRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DOWNLOAD));
            $this->view->assign('fileDeleteRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DELETE));
            $this->view->assign('fileUploadRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_UPLOAD));

            if (!is_array($this->view->files) || count($this->view->files) === 0) {
                return;
            }

            $this->eventDispatcher->notifyEvent('show.account.listfiles', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
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
            $AccountHelper->setActionId(ActionsInterface::ACCOUNT_CREATE);

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
            $this->view->assign('formRoute', 'account/saveCreate');

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.create', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
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
            $AccountHelper->setAccountData($id, ActionsInterface::ACCOUNT_COPY);

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
            $this->view->assign('formRoute', 'account/saveCopy');

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.copy', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
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
            $AccountHelper->setAccountData($id, ActionsInterface::ACCOUNT_EDIT);

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
            $this->view->assign('formRoute', 'account/saveEdit');

            $AccountHelper->getAccountService()->incrementViewCounter();
            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.edit', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
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
            $AccountHelper->setAccountData($id, ActionsInterface::ACCOUNT_DELETE);

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
            $this->view->assign('formRoute', 'account/saveDelete');

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.delete', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
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
            $AccountHelper->setAccountData($id, ActionsInterface::ACCOUNT_EDIT_PASS);

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
            $this->view->assign('formRoute', 'account/saveEditPass');

            $this->view->assign('accountPassDateChange', gmdate('Y-m-d', $AccountHelper->getAccountData()->getAccountPassDateChange()));

            $this->eventDispatcher->notifyEvent('show.account.editpass', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
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
            $AccountHelper->setAccountDataHistory($id, ActionsInterface::ACCOUNT_VIEW_HISTORY);

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
            $this->view->assign('formRoute', 'account/saveRestore');

            $this->view->assign('isView', true);

            $AccountHelper->setCommonData();

            $this->eventDispatcher->notifyEvent('show.account.history', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
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
            $AccountHelper->setAccountDataHistory($id, ActionsInterface::ACCOUNT_REQUEST);

            $this->view->addTemplate('account-request');
            $this->view->assign('formRoute', 'account/saveRequest');

            $this->eventDispatcher->notifyEvent('show.account.request', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Display account's password
     *
     * @param int $id        Account's ID
     * @param int $isHistory The account's ID refers to history
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function viewPassAction($id, $isHistory)
    {
        try {
            $accountService = new AccountService();
            $accountPassHelper = new AccountPasswordHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

            $account = $isHistory === 0 ? $accountService->getPasswordForId($id) : $accountService->getPasswordHistoryForId($id);

            $data = [
                'acclogin' => $account->getAccountLogin(),
                'accpass' => $accountPassHelper->getPassword($account, $this->acl, AccountPasswordHelper::TYPE_FULL),
                'useimage' => $this->configData->isAccountPassToImage(),
                'html' => $this->render()
            ];

            $this->eventDispatcher->notifyEvent('show.account.pass', $this);

            $this->returnJsonResponseData($data);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, $e->getMessage());
        }
    }

    /**
     * Copy account's password
     *
     * @param int $id        Account's ID
     * @param int $isHistory The account's ID refers to history
     * @throws Helpers\HelperException
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function copyPassAction($id, $isHistory)
    {
        $accountService = new AccountService();
        $accountPassHelper = new AccountPasswordHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        $account = $isHistory === 0 ? $accountService->getPasswordForId($id) : $accountService->getPasswordHistoryForId($id);

        $data = [
            'accpass' => $accountPassHelper->getPassword($account, $this->acl, AccountPasswordHelper::TYPE_NORMAL),
        ];

        $this->eventDispatcher->notifyEvent('copy.account.pass', $this);

        $this->returnJsonResponseData($data);
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
            $form->validate(ActionsInterface::ACCOUNT_CREATE);
            $form->getItemData()->setAccountUserId($this->userData->getUserId());

            $accountService = new AccountService();
            $account = $accountService->create($form->getItemData());

            $this->addCustomFieldsForItem(ActionsInterface::ACCOUNT, $account->getId());

            $accountService->logAction($account->getId(), ActionsInterface::ACCOUNT_CREATE);

            $this->eventDispatcher->notifyEvent('create.account', $this);

            $this->returnJsonResponseData(
                [
                    'itemId' => $account->getId(),
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_EDIT)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Cuenta creada')
            );
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
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
            $form->validate(ActionsInterface::ACCOUNT_EDIT);

            $accountService = new AccountService();
            $accountService->update($form->getItemData());

            $this->updateCustomFieldsForItem(ActionsInterface::ACCOUNT, $id);

            $accountService->logAction($id, ActionsInterface::ACCOUNT_EDIT);

            $this->eventDispatcher->notifyEvent('edit.account', $this);

            $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Cuenta actualizada')
            );
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
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
            $form->validate(ActionsInterface::ACCOUNT_EDIT_PASS);

            $accountService = new AccountService();
            $accountService->editPassword($form->getItemData());

            $accountService->logAction($id, ActionsInterface::ACCOUNT_EDIT_PASS);

            $this->eventDispatcher->notifyEvent('edit.account.pass', $this);

            $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Clave actualizada')
            );
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
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
            $accountService->editRestore($historyId, $id);

            $accountService->logAction($id, ActionsInterface::ACCOUNT_EDIT_RESTORE);

            $this->eventDispatcher->notifyEvent('edit.account.restore', $this);

            $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Cuenta restaurada')
            );
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
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

            if ($accountService->delete($id)) {
                $this->deleteCustomFieldsForItem(ActionsInterface::ACCOUNT, $id);

                // FIXME: obtener cuenta antes de eliminar
//                $accountService->logAccountAction($id, ActionsInterface::ACCOUNT_DELETE);

                $this->eventDispatcher->notifyEvent('delete.account', $this);

                $this->returnJsonResponseData(
                    ['nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_SEARCH)],
                    JsonResponse::JSON_SUCCESS,
                    __u('Cuenta eliminada')
                );
            }
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Initialize class
     */
    protected function initialize()
    {
        if ($this->actionName !== 'viewLinkAction') {
            $this->checkLoggedIn();
        }
    }
}