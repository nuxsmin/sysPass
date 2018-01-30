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
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\Core\SessionUtil;
use SP\DataModel\AccountExtData;
use SP\Forms\AccountForm;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\AccountHelper;
use SP\Modules\Web\Controllers\Helpers\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Helpers\AccountSearchHelper;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Repositories\PublicLink\PublicLinkRepository;
use SP\Services\Account\AccountFileService;
use SP\Services\Account\AccountHistoryService;
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
     * @var AccountService
     */
    protected $accountService;

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
            $AccountSearchHelper->getAccountSearch();

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
            $AccountSearchHelper->getAccountSearch();

            $this->eventDispatcher->notifyEvent('show.account.search', $this);

            $data = [
                'sk' => SessionUtil::getSessionKey(),
                'html' => $this->render()
            ];

            $this->returnJsonResponseData($data);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

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
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

            if (!$AccountHelper->setAccount(
                $accountDetailsResponse,
                $this->accountService,
                ActionsInterface::ACCOUNT_VIEW
            )) {
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

            $this->accountService->incrementViewCounter($id);

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
     * @throws \SP\Core\Dic\ContainerException
     */
    public function viewLinkAction($hash)
    {
        $LayoutHelper = new LayoutHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
        $LayoutHelper->getPublicLayout('account-link', 'account');

        try {
            $publicLinkService = new PublicLinkService();
            $publicLinkData = $publicLinkService->getByHash($hash);

            if (time() < $publicLinkData->getDateExpire()
                && $publicLinkData->getCountViews() < $publicLinkData->getMaxCountViews()
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

                $this->accountService->incrementViewCounter($publicLinkData->getItemId());
                $this->accountService->incrementDecryptCounter($publicLinkData->getItemId());

                /** @var Vault $vault */
                $vault = unserialize($publicLinkData->getData());

                /** @var AccountExtData $accountData */
                $accountData = Util::unserialize(AccountExtData::class, $vault->getData(PublicLinkService::getKeyForHash($this->config->getConfigData()->getPasswordSalt(), $publicLinkData)));

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
                    $this->view->assign('accountPassImage', ImageUtil::convertText($accountData->getPass()));
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

            $this->view->assign('deleteEnabled', Request::analyze('del', 0));

            $this->view->assign('files', (new AccountFileService())->getByAccountId($id));
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
            if (!$AccountHelper->checkAccess()) {
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
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

            if (!$AccountHelper->setAccount(
                $accountDetailsResponse,
                $this->accountService,
                ActionsInterface::ACCOUNT_COPY
            )) {
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
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

            if (!$AccountHelper->setAccount(
                $accountDetailsResponse,
                $this->accountService,
                ActionsInterface::ACCOUNT_EDIT
            )) {
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

            $this->accountService->incrementViewCounter($id);

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
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse);

            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

            if (!$AccountHelper->setAccount(
                $accountDetailsResponse,
                $this->accountService,
                ActionsInterface::ACCOUNT_DELETE
            )) {
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
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse);

            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

            if (!$AccountHelper->setAccount(
                $accountDetailsResponse,
                $this->accountService,
                ActionsInterface::ACCOUNT_EDIT_PASS
            )) {
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

            $this->view->assign('accountPassDateChange', gmdate('Y-m-d', $AccountHelper->getAccountDetailsResponse()->getAccountVData()->getPassDateChange()));

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
            $accountHistoryService = new AccountHistoryService();
            $accountHistoryData = $accountHistoryService->getById($id);

            $AccountHelper = new AccountHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

            if (!$AccountHelper->setAccountHistory(
                $accountHistoryService,
                $accountHistoryData,
                ActionsInterface::ACCOUNT_VIEW_HISTORY)
            ) {
                return;
            }

            $this->view->addTemplate('account-history');

            $this->view->assign('title',
                [
                    'class' => 'titleNormal',
                    'name' => __('Detalles de Cuenta'),
                    'icon' => 'access_time'
                ]
            );

            $this->view->assign('formRoute', 'account/saveRestore');
            $this->view->assign('isView', true);

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
            $AccountHelper->setAccount(
                $this->accountService->getById($id),
                $this->accountService,
                ActionsInterface::ACCOUNT_REQUEST
            );

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
            $accountPassHelper = new AccountPasswordHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

            $account = $isHistory === 0 ? $this->accountService->getPasswordForId($id) : $this->accountService->getPasswordHistoryForId($id);

            $data = [
                'acclogin' => $account->getLogin(),
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
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Dic\ContainerException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function copyPassAction($id, $isHistory)
    {
        $accountPassHelper = new AccountPasswordHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        $account = $isHistory === 0 ? $this->accountService->getPasswordForId($id) : $this->accountService->getPasswordHistoryForId($id);

        $data = [
            'accpass' => $accountPassHelper->getPassword($account, $this->acl, AccountPasswordHelper::TYPE_NORMAL),
        ];

        $this->eventDispatcher->notifyEvent('copy.account.pass', $this);

        $this->returnJsonResponseData($data);
    }

    /**
     * Saves copy action
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function saveCopyAction()
    {
        $this->saveCreateAction();
    }

    /**
     * Saves create action
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function saveCreateAction()
    {
        try {
            $form = new AccountForm();
            $form->validate(ActionsInterface::ACCOUNT_CREATE);
            $form->getItemData()->userId = $this->userData->getId();

            $accountId = $this->accountService->create($form->getItemData());

            $this->addCustomFieldsForItem(ActionsInterface::ACCOUNT, $accountId);

            $this->eventDispatcher->notifyEvent('create.account', $this);

            $this->returnJsonResponseData(
                [
                    'itemId' => $accountId,
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
     * @throws \SP\Core\Dic\ContainerException
     */
    public function saveEditAction($id)
    {
        try {
            $form = new AccountForm($id);
            $form->validate(ActionsInterface::ACCOUNT_EDIT);

            $this->accountService->update($form->getItemData());

            $this->updateCustomFieldsForItem(ActionsInterface::ACCOUNT, $id);

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
     * @throws \SP\Core\Dic\ContainerException
     */
    public function saveEditPassAction($id)
    {
        try {
            $form = new AccountForm($id);
            $form->validate(ActionsInterface::ACCOUNT_EDIT_PASS);

            $this->accountService->editPassword($form->getItemData());

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
     * @throws \SP\Core\Dic\ContainerException
     */
    public function saveEditRestoreAction($historyId, $id)
    {
        try {
            $this->accountService->editRestore($historyId, $id);

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
            if ($this->accountService->delete($id)) {
                $this->deleteCustomFieldsForItem(ActionsInterface::ACCOUNT, $id);

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
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function initialize()
    {
        if ($this->actionName !== 'viewLinkAction') {
            $this->checkLoggedIn();
        }

        $this->accountService = new AccountService();
    }
}