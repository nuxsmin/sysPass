<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Vault;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\Core\Session\Session;
use SP\Core\UI\ThemeIconsBase;
use SP\DataModel\AccountExtData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\Account\AccountHelper;
use SP\Modules\Web\Controllers\Helpers\Account\AccountHistoryHelper;
use SP\Modules\Web\Controllers\Helpers\Account\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Helpers\Account\AccountSearchHelper;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AccountForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\Account\AccountAclService;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Account\AccountService;
use SP\Services\Auth\AuthException;
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
    use JsonTrait, ItemTrait;

    /**
     * @var AccountService
     */
    protected $accountService;
    /**
     * @var ThemeIconsBase
     */
    protected $icons;

    /**
     * Index action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function indexAction()
    {
        try {
            $accountSearchHelper = $this->dic->get(AccountSearchHelper::class);
            $accountSearchHelper->getSearchBox();
            $accountSearchHelper->getAccountSearch();

            $this->eventDispatcher->notifyEvent('show.account.search', new Event($this));

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e);
        }
    }

    /**
     * Search action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function searchAction()
    {
        try {
            $AccountSearchHelper = $this->dic->get(AccountSearchHelper::class);
            $AccountSearchHelper->getAccountSearch();

            $this->eventDispatcher->notifyEvent('show.account.search', new Event($this));

            $data = [
                'sk' => $this->session->generateSecurityKey(),
                'html' => $this->render()
            ];

            $this->returnJsonResponseData($data);
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e);
        }
    }

    /**
     * View action
     *
     * @param int $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function viewAction($id)
    {
        try {
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setIsView(true);
            $accountHelper->setViewForAccount($accountDetailsResponse, ActionsInterface::ACCOUNT_VIEW);

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleNormal',
                    'name' => __('Detalles de Cuenta'),
                    'icon' => $this->icons->getIconView()->getIcon()
                ]
            );

            $this->accountService->incrementViewCounter($id);

            $this->eventDispatcher->notifyEvent('show.account', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * View public link action
     *
     * @param string $hash Link's hash
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function viewLinkAction($hash)
    {
        $layoutHelper = $this->dic->get(LayoutHelper::class);
        $layoutHelper->getPublicLayout('account-link', 'account');

        try {
            $publicLinkService = $this->dic->get(PublicLinkService::class);
            $publicLinkData = $publicLinkService->getByHash($hash);

            if (time() < $publicLinkData->getDateExpire()
                && $publicLinkData->getCountViews() < $publicLinkData->getMaxCountViews()
            ) {
                $publicLinkService->addLinkView($publicLinkData);

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

                $this->eventDispatcher->notifyEvent('show.account.link',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Enlace visualizado'))
                        ->addDetail(__u('Cuenta'), $accountData->getName())
                        ->addDetail(__u('Cliente'), $accountData->getClientName())
                        ->addDetail(__u('Agente'), $this->router->request()->headers()->get('User-Agent'))
                        ->addDetail(__u('HTTPS'), $this->router->request()->isSecure() ? __u('ON') : __u('OFF'))
                        ->addData('userId', $publicLinkData->getUserId())
                        ->addData('notify', $publicLinkData->isNotify()))
                );
            } else {
                ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_PAGE_NO_PERMISSION, 'account-link');
            }

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e, 'account-link');
        }
    }

    /**
     * Create action
     */
    public function createAction()
    {
        try {
            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForBlank(ActionsInterface::ACCOUNT_CREATE);

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleGreen',
                    'name' => __('Nueva Cuenta'),
                    'icon' => $this->icons->getIconAdd()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveCreate');

            $this->eventDispatcher->notifyEvent('show.account.create', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * Copy action
     *
     * @param int $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function copyAction($id)
    {
        try {
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForAccount($accountDetailsResponse, ActionsInterface::ACCOUNT_COPY);

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleGreen',
                    'name' => __('Nueva Cuenta'),
                    'icon' => $this->icons->getIconAdd()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveCopy');

            $this->eventDispatcher->notifyEvent('show.account.copy', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * Edit action
     *
     * @param int $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editAction($id)
    {
        try {
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForAccount($accountDetailsResponse, ActionsInterface::ACCOUNT_EDIT);

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleOrange',
                    'name' => __('Editar Cuenta'),
                    'icon' => $this->icons->getIconEdit()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveEdit');
            $this->view->assign(__FUNCTION__);

            $this->accountService->incrementViewCounter($id);

            $this->eventDispatcher->notifyEvent('show.account.edit', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * Delete action
     *
     * @param int $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteAction($id = null)
    {
        try {
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForAccount($accountDetailsResponse, ActionsInterface::ACCOUNT_DELETE);

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleRed',
                    'name' => __('Eliminar Cuenta'),
                    'icon' => $this->icons->getIconDelete()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveDelete');

            $this->eventDispatcher->notifyEvent('show.account.delete', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * Obtener los datos para mostrar el interface para modificar la clave de cuenta
     *
     * @param int $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editPassAction($id)
    {
        try {
            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForAccount($accountDetailsResponse, ActionsInterface::ACCOUNT_EDIT_PASS);

            $this->view->addTemplate('account-editpass');
            $this->view->assign('title',
                [
                    'class' => 'titleOrange',
                    'name' => __('Modificar Clave de Cuenta'),
                    'icon' => $this->icons->getIconEditPass()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveEditPass');

            $this->view->assign('accountPassDateChange', gmdate('Y-m-d', $accountDetailsResponse->getAccountVData()->getPassDateChange()));

            $this->eventDispatcher->notifyEvent('show.account.editpass', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e, 'account-editpass');
        }
    }

    /**
     * Obtener los datos para mostrar el interface para ver cuenta en fecha concreta
     *
     * @param int $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function viewHistoryAction($id)
    {
        try {
            $accountHistoryService = $this->dic->get(AccountHistoryService::class);
            $accountHistoryData = $accountHistoryService->getById($id);

            $accountHistoryHelper = $this->dic->get(AccountHistoryHelper::class);
            $accountHistoryHelper->setView($accountHistoryData, ActionsInterface::ACCOUNT_VIEW_HISTORY);

            $this->view->addTemplate('account-history');

            $this->view->assign('title',
                [
                    'class' => 'titleNormal',
                    'name' => __('Detalles de Cuenta'),
                    'icon' => 'access_time'
                ]
            );

            $this->view->assign('formRoute', 'account/saveRestore');

            $this->eventDispatcher->notifyEvent('show.account.history', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e, 'account-history');
        }
    }

    /**
     * Obtener los datos para mostrar el interface de solicitud de cambios en una cuenta
     *
     * @param int $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function requestAccessAction($id)
    {
        try {
            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setIsView(true);
            $accountHelper->setViewForRequest($this->accountService->getById($id), ActionsInterface::ACCOUNT_REQUEST);

            $this->view->addTemplate('account-request');
            $this->view->assign('formRoute', 'account/saveRequest');

            $this->eventDispatcher->notifyEvent('show.account.request', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showExceptionInView($this->view, $e, 'account-request');
        }
    }

    /**
     * Display account's password
     *
     * @param int $id Account's ID
     * @param int $isHistory The account's ID refers to history
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function viewPassAction($id, $isHistory)
    {
        try {
            $accountPassHelper = $this->dic->get(AccountPasswordHelper::class);

            $account = $isHistory === 0 ? $this->accountService->getPasswordForId($id) : $this->accountService->getPasswordHistoryForId($id);

            $data = [
                'acclogin' => $account->getLogin(),
                'accpass' => $accountPassHelper->getPassword($account, $this->acl, AccountPasswordHelper::TYPE_FULL),
                'useimage' => $this->configData->isAccountPassToImage(),
                'html' => $this->render()
            ];

            $this->eventDispatcher->notifyEvent('show.account.pass',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Clave visualizada'))
                        ->addDetail(__u('Cuenta'), $account->getName()))
            );

            $this->returnJsonResponseData($data);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Copy account's password
     *
     * @param int $id Account's ID
     * @param int $isHistory The account's ID refers to history
     * @throws Helpers\HelperException
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function copyPassAction($id, $isHistory)
    {
        $accountPassHelper = $this->dic->get(AccountPasswordHelper::class);

        $account = $isHistory === 0 ? $this->accountService->getPasswordForId($id) : $this->accountService->getPasswordHistoryForId($id);

        $data = [
            'accpass' => $accountPassHelper->getPassword($account, $this->acl, AccountPasswordHelper::TYPE_NORMAL),
        ];

        $this->eventDispatcher->notifyEvent('copy.account.pass',
            new Event($this,
                EventMessage::factory()
                    ->addDescription(__u('Clave copiada'))
                    ->addDetail(__u('Cuenta'), $account->getName()))
        );

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

            $itemData = $form->getItemData();
            $itemData->userId = $this->userData->getId();

            $accountId = $this->accountService->create($itemData);

            $this->addCustomFieldsForItem(ActionsInterface::ACCOUNT, $accountId);

            $accountDetails = $this->accountService->getById($accountId)->getAccountVData();

            $this->eventDispatcher->notifyEvent('create.account',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Cuenta creada'))
                        ->addDetail(__u('Cuenta'), $accountDetails->getName())
                        ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            $this->returnJsonResponseData(
                [
                    'itemId' => $accountId,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_EDIT)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Cuenta creada')
            );
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveEditAction($id)
    {
        try {
            $form = new AccountForm($id);
            $form->validate(ActionsInterface::ACCOUNT_EDIT);

            $itemData = $form->getItemData();

            $this->accountService->update($itemData);

            $this->updateCustomFieldsForItem(ActionsInterface::ACCOUNT, $id);

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('edit.account',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Cuenta actualizada'))
                        ->addDetail(__u('Cuenta'), $accountDetails->getName())
                        ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Cuenta actualizada')
            );
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveEditPassAction($id)
    {
        try {
            $form = new AccountForm($id);
            $form->validate(ActionsInterface::ACCOUNT_EDIT_PASS);

            $this->accountService->editPassword($form->getItemData());

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('edit.account.pass',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Clave actualizada'))
                        ->addDetail(__u('Cuenta'), $accountDetails->getName())
                        ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Clave actualizada')
            );
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves restore action
     *
     * @param int $historyId Account's history ID
     * @param int $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveEditRestoreAction($historyId, $id)
    {
        try {
            $this->accountService->editRestore($historyId, $id);

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('edit.account.restore',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Cuenta restaurada'))
                        ->addDetail(__u('Cuenta'), $accountDetails->getName())
                        ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Cuenta restaurada')
            );
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves delete action
     *
     * @param int $id Account's ID
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function saveDeleteAction($id)
    {
        try {
            if ($id === null) {
                $this->accountService->deleteByIdBatch($this->getItemsIdFromRequest());

                $this->deleteCustomFieldsForItem(ActionsInterface::ACCOUNT, $id);

                $this->eventDispatcher->notifyEvent('delete.account.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Cuentas eliminadas')))
                );

                $this->returnJsonResponseData(JsonResponse::JSON_SUCCESS, __u('Cuentas eliminadas'));
            } else {
                $accountDetails = $this->accountService->getById($id)->getAccountVData();

                $this->accountService->delete($id);

                $this->deleteCustomFieldsForItem(ActionsInterface::ACCOUNT, $id);

                $this->eventDispatcher->notifyEvent('delete.account',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Cuenta eliminada'))
                            ->addDetail(__u('Cuenta'), $accountDetails->getName())
                            ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Cuenta eliminada'));
            }
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves a request action
     *
     * @param $id Account's ID
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveRequestAction($id)
    {
        try {
            $description = Request::analyzeString('description');

            if (empty($description)) {
                throw new ValidationException(__u('Es necesaria una descripción'));
            }

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('request.account',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Solicitud'))
                        ->addDetail(__u('Solicitante'), sprintf('%s (%s)', $this->userData->getName(), $this->userData->getLogin()))
                        ->addDetail(__u('Cuenta'), $accountDetails->getName())
                        ->addDetail(__u('Cliente'), $accountDetails->getClientName())
                        ->addDetail(__u('Descripción'), $description)
                        ->addData('accountId', $id)
                        ->addData('whoId', $this->userData->getId())
                        ->addData('userId', $accountDetails->userId)
                        ->addData('userId', $accountDetails->userEditId))
            );

            $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(ActionsInterface::ACCOUNT)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Solicitud realizada')
            );
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Initialize class
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AuthException
     */
    protected function initialize()
    {
        if ($this->actionName !== 'viewLinkAction') {
            $this->checkLoggedIn();
        }

        if (DEBUG === true && $this->session->getAppStatus() === Session::APP_STATUS_RELOADED) {
            $this->session->resetAppStatus();

            // Reset de los datos de ACL de cuentas
            AccountAclService::clearAcl($this->session->getUserData()->getId());
        }

        $this->accountService = $this->dic->get(AccountService::class);
        $this->icons = $this->theme->getIcons();
    }
}