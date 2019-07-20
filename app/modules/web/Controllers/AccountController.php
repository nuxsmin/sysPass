<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Bootstrap;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Context\SessionContext;
use SP\Core\Crypt\Vault;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\AccountExtData;
use SP\DataModel\ItemPreset\Password;
use SP\Http\JsonResponse;
use SP\Http\Uri;
use SP\Modules\Web\Controllers\Helpers\Account\AccountHelper;
use SP\Modules\Web\Controllers\Helpers\Account\AccountHistoryHelper;
use SP\Modules\Web\Controllers\Helpers\Account\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Helpers\Account\AccountSearchHelper;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AccountForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountAclService;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Account\AccountService;
use SP\Services\Auth\AuthException;
use SP\Services\ItemPreset\ItemPresetInterface;
use SP\Services\ItemPreset\ItemPresetService;
use SP\Services\PublicLink\PublicLinkService;
use SP\Services\ServiceException;
use SP\Services\User\UserService;
use SP\Util\ErrorUtil;
use SP\Util\ImageUtil;
use SP\Util\Util;

/**
 * Class AccountController
 *
 * @package SP\Modules\Web\Controllers
 */
final class AccountController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var AccountService
     */
    protected $accountService;
    /**
     * @var ThemeIcons
     */
    protected $icons;

    /**
     * Index action
     *
     * @throws ContainerExceptionInterface
     */
    public function indexAction()
    {
        try {
            $accountSearchHelper = $this->dic->get(AccountSearchHelper::class);
            $accountSearchHelper->getSearchBox();
            $accountSearchHelper->getAccountSearch();

            $this->eventDispatcher->notifyEvent('show.account.search', new Event($this));

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            ErrorUtil::showExceptionInView($this->view, $e);
        }
    }

    /**
     * Search action
     */
    public function searchAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountSearchHelper = $this->dic->get(AccountSearchHelper::class);
            $accountSearchHelper->getAccountSearch();

            $this->eventDispatcher->notifyEvent('show.account.search', new Event($this));

            return $this->returnJsonResponseData([
                'html' => $this->render()
            ]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * View action
     *
     * @param int $id Account's ID
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function viewAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $this->view->addTemplate('account');

            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setIsView(true);
            $accountHelper->setViewForAccount($accountDetailsResponse, Acl::ACCOUNT_VIEW);

            $this->view->assign('title',
                [
                    'class' => 'titleNormal',
                    'name' => __('Account Details'),
                    'icon' => $this->icons->getIconView()->getIcon()
                ]
            );

            $this->accountService->incrementViewCounter($id);

            $this->eventDispatcher->notifyEvent('show.account', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            if ($this->isAjax === false
                && !$this->view->isUpgraded()
            ) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * View public link action
     *
     * @param string $hash Link's hash
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function viewLinkAction($hash)
    {
        try {
            // Set the security token to its previous value because we can't tell
            // the browser which will be the new security token (not so good...)
            $this->session->setSecurityKey($this->previousSk);

            $layoutHelper = $this->dic->get(LayoutHelper::class);
            $layoutHelper->getPublicLayout('account-link', 'account');

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
                $accountData = Util::unserialize(AccountExtData::class, $vault->getData($publicLinkService->getPublicLinkKey($publicLinkData->getHash())->getKey()));

                $this->view->assign('title',
                    [
                        'class' => 'titleNormal',
                        'name' => __('Account Details'),
                        'icon' => $this->icons->getIconView()->getIcon()
                    ]
                );

                $this->view->assign('isView', true);
                $this->view->assign('useImage', $this->configData->isPublinksImageEnabled() || $this->configData->isAccountPassToImage());

                if ($this->view->useImage) {
                    $imageUtil = $this->dic->get(ImageUtil::class);
                    $this->view->assign('accountPassImage', $imageUtil->convertText($accountData->getPass()));
                } else {
                    $this->view->assign('copyPassRoute', Acl::getActionRoute(Acl::ACCOUNT_VIEW_PASS));
                }

                $this->view->assign('accountData', $accountData);

                $clientAddress = $this->configData->isDemoEnabled() ? '***' : $this->request->getClientAddress(true);

                $baseUrl = ($this->configData->getApplicationUrl() ?: Bootstrap::$WEBURI) . Bootstrap::$SUBURI;

                $deepLink = new Uri($baseUrl);
                $deepLink->addParam('r', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW) . '/' . $accountData->getId());

                $this->eventDispatcher->notifyEvent('show.account.link',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Link viewed'))
                        ->addDetail(__u('Account'), $accountData->getName())
                        ->addDetail(__u('Client'), $accountData->getClientName())
                        ->addDetail(__u('Agent'), $this->request->getHeader('User-Agent'))
                        ->addDetail(__u('HTTPS'), $this->request->isHttps() ? __u('ON') : __u('OFF'))
                        ->addDetail(__u('IP'), $clientAddress)
                        ->addDetail(__u('Link'), $deepLink->getUriSigned($this->configData->getPasswordSalt()))
                        ->addExtra('userId', $publicLinkData->getUserId())
                        ->addExtra('notify', $publicLinkData->isNotify()))
                );
            } else {
                ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_PAGE_NO_PERMISSION, true, 'account-link');
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            ErrorUtil::showExceptionInView($this->view, $e, 'account-link');
        }
    }

    /**
     * Create action
     */
    public function createAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForBlank(Acl::ACCOUNT_CREATE);

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleGreen',
                    'name' => __('New Account'),
                    'icon' => $this->icons->getIconAdd()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveCreate');

            $this->eventDispatcher->notifyEvent('show.account.create', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            if ($this->isAjax === false
                && !$this->view->isUpgraded()
            ) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * Copy action
     *
     * @param int $id Account's ID
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function copyAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForAccount($accountDetailsResponse, Acl::ACCOUNT_COPY);

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleGreen',
                    'name' => __('New Account'),
                    'icon' => $this->icons->getIconAdd()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveCopy');

            $this->eventDispatcher->notifyEvent('show.account.copy', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            if ($this->isAjax === false
                && !$this->view->isUpgraded()
            ) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * Edit action
     *
     * @param int $id Account's ID
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function editAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForAccount($accountDetailsResponse, Acl::ACCOUNT_EDIT);

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleOrange',
                    'name' => __('Edit Account'),
                    'icon' => $this->icons->getIconEdit()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveEdit');

            $this->accountService->incrementViewCounter($id);

            $this->eventDispatcher->notifyEvent('show.account.edit', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            if ($this->isAjax === false
                && !$this->view->isUpgraded()
            ) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * Delete action
     *
     * @param int $id Account's ID
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function deleteAction($id = null)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForAccount($accountDetailsResponse, Acl::ACCOUNT_DELETE);

            $this->view->addTemplate('account');
            $this->view->assign('title',
                [
                    'class' => 'titleRed',
                    'name' => __('Remove Account'),
                    'icon' => $this->icons->getIconDelete()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveDelete');

            $this->eventDispatcher->notifyEvent('show.account.delete', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            if ($this->isAjax === false
                && !$this->view->isUpgraded()
            ) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }

    /**
     * Obtener los datos para mostrar el interface para modificar la clave de cuenta
     *
     * @param int $id Account's ID
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function editPassAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setViewForAccount($accountDetailsResponse, Acl::ACCOUNT_EDIT_PASS);

            $this->view->addTemplate('account-editpass');
            $this->view->assign('title',
                [
                    'class' => 'titleOrange',
                    'name' => __('Edit Account Password'),
                    'icon' => $this->icons->getIconEditPass()->getIcon()
                ]
            );
            $this->view->assign('formRoute', 'account/saveEditPass');

            $this->eventDispatcher->notifyEvent('show.account.editpass', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            if ($this->isAjax === false
                && !$this->view->isUpgraded()
            ) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account-editpass');
        }
    }

    /**
     * Obtener los datos para mostrar el interface para ver cuenta en fecha concreta
     *
     * @param int $id Account's ID
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function viewHistoryAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountHistoryService = $this->dic->get(AccountHistoryService::class);
            $accountHistoryData = $accountHistoryService->getById($id);

            $accountHistoryHelper = $this->dic->get(AccountHistoryHelper::class);
            $accountHistoryHelper->setView($accountHistoryData, Acl::ACCOUNT_HISTORY_VIEW);

            $this->view->addTemplate('account-history');

            $this->view->assign('title',
                [
                    'class' => 'titleNormal',
                    'name' => __('Account Details'),
                    'icon' => 'access_time'
                ]
            );

            $this->view->assign('formRoute', 'account/saveRestore');

            $this->eventDispatcher->notifyEvent('show.account.history', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            if ($this->isAjax === false
                && !$this->view->isUpgraded()
            ) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account-history');
        }
    }

    /**
     * Obtener los datos para mostrar el interface de solicitud de cambios en una cuenta
     *
     * @param int $id Account's ID
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function requestAccessAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountHelper = $this->dic->get(AccountHelper::class);
            $accountHelper->setIsView(true);
            $accountHelper->setViewForRequest($this->accountService->getById($id), Acl::ACCOUNT_REQUEST);

            $this->view->addTemplate('account-request');
            $this->view->assign('formRoute', 'account/saveRequest');

            $this->eventDispatcher->notifyEvent('show.account.request', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            if ($this->isAjax === false
                && !$this->view->isUpgraded()
            ) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account-request');
        }
    }

    /**
     * Display account's password
     *
     * @param int $id Account's ID
     * @param int $parentId
     *
     * @return bool
     */
    public function viewPassAction($id, $parentId = 0)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountPassHelper = $this->dic->get(AccountPasswordHelper::class);

            $account = $this->accountService->getPasswordForId($id);

            $passwordPreset = $this->getPasswordPreset();
            $useImage = $this->configData->isAccountPassToImage()
                || $passwordPreset !== null && $passwordPreset->isUseImage();

            $this->view->assign('isLinked', $parentId > 0);

            $data = $accountPassHelper->getPasswordView($account, $useImage);

            $this->accountService->incrementDecryptCounter($id);

            $this->eventDispatcher->notifyEvent('show.account.pass',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Password viewed'))
                    ->addDetail(__u('Account'), $account->getName()))
            );

            return $this->returnJsonResponseData($data);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return Password
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     */
    private function getPasswordPreset()
    {
        $itemPreset = $this->dic->get(ItemPresetService::class)
            ->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD);

        if ($itemPreset !== null && $itemPreset->getFixed() === 1) {
            return $itemPreset->hydrate(Password::class);
        }

        return null;
    }

    /**
     * Display account's password
     *
     * @param int $id Account's ID
     *
     * @return bool
     */
    public function viewPassHistoryAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $accountPassHelper = $this->dic->get(AccountPasswordHelper::class);

            $account = $this->accountService->getPasswordHistoryForId($id);

            $passwordPreset = $this->getPasswordPreset();
            $useImage = $this->configData->isAccountPassToImage()
                || $passwordPreset !== null && $passwordPreset->isUseImage();

            $this->view->assign('isLinked', 0);

            $data = $accountPassHelper->getPasswordView($account, $useImage);

            $this->eventDispatcher->notifyEvent('show.account.pass.history',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Password viewed'))
                    ->addDetail(__u('Account'), $account->getName()))
            );

            return $this->returnJsonResponseData($data);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Copy account's password
     *
     * @param int $id Account's ID
     *
     * @return bool
     * @throws Helpers\HelperException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws SPException
     */
    public function copyPassAction($id)
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        $accountPassHelper = $this->dic->get(AccountPasswordHelper::class);

        $account = $this->accountService->getPasswordForId($id);

        $data = [
            'accpass' => $accountPassHelper->getPasswordClear($account),
        ];

        $this->eventDispatcher->notifyEvent('copy.account.pass',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Password copied'))
                ->addDetail(__u('Account'), $account->getName()))
        );

        return $this->returnJsonResponseData($data);
    }

    /**
     * Copy account's password
     *
     * @param int $id Account's ID
     *
     * @return bool
     * @throws Helpers\HelperException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws SPException
     */
    public function copyPassHistoryAction($id)
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        $accountPassHelper = $this->dic->get(AccountPasswordHelper::class);

        $account = $this->accountService->getPasswordHistoryForId($id);

        $data = [
            'accpass' => $accountPassHelper->getPasswordClear($account),
        ];

        $this->eventDispatcher->notifyEvent('copy.account.pass.history',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Password copied'))
                ->addDetail(__u('Account'), $account->getName()))
        );

        return $this->returnJsonResponseData($data);
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
            $this->checkSecurityToken($this->previousSk, $this->request);

            $form = new AccountForm($this->dic);
            $form->validate(Acl::ACCOUNT_CREATE);

            $accountId = $this->accountService->create($form->getItemData());

            $accountDetails = $this->accountService->getById($accountId)->getAccountVData();

            $this->eventDispatcher->notifyEvent('create.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Account created'))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName()))
            );

            $this->addCustomFieldsForItem(Acl::ACCOUNT, $accountId, $this->request);

            return $this->returnJsonResponseData(
                [
                    'itemId' => $accountId,
                    'nextAction' => Acl::getActionRoute(Acl::ACCOUNT_EDIT)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Account created')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id Account's ID
     *
     * @return bool
     */
    public function saveEditAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $form = new AccountForm($this->dic, $id);
            $form->validate(Acl::ACCOUNT_EDIT);

            $itemData = $form->getItemData();

            $this->accountService->update($itemData);

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('edit.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Account updated'))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName()))
            );

            $this->updateCustomFieldsForItem(Acl::ACCOUNT, $id, $this->request);

            return $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(Acl::ACCOUNT_VIEW)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Account updated')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id Account's ID
     *
     * @return bool
     */
    public function saveEditPassAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $form = new AccountForm($this->dic, $id);
            $form->validate(Acl::ACCOUNT_EDIT_PASS);

            $this->accountService->editPassword($form->getItemData());

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('edit.account.pass',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Password updated'))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName()))
            );

            return $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(Acl::ACCOUNT_VIEW)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Password updated')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves restore action
     *
     * @param int $historyId Account's history ID
     * @param int $id        Account's ID
     *
     * @return bool
     */
    public function saveEditRestoreAction($historyId, $id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $this->accountService->editRestore($historyId, $id);

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->eventDispatcher->notifyEvent('edit.account.restore',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Account restored'))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName()))
            );

            return $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(Acl::ACCOUNT_VIEW)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Account restored')
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves delete action
     *
     * @param int $id Account's ID
     *
     * @return bool
     */
    public function saveDeleteAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if ($id === null) {
                $this->accountService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent(
                    'delete.account.selection',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Accounts removed')))
                );

                $this->deleteCustomFieldsForItem(Acl::ACCOUNT, $id);

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Accounts removed'));
            }

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->accountService->delete($id);

            $this->eventDispatcher->notifyEvent('delete.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Account removed'))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName()))
            );

            $this->deleteCustomFieldsForItem(Acl::ACCOUNT, $id);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Account removed'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves a request action
     *
     * @param $id Account's ID
     *
     * @return bool
     */
    public function saveRequestAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $description = $this->request->analyzeString('description');

            if (empty($description)) {
                throw new ValidationException(__u('A description is needed'));
            }

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $baseUrl = ($this->configData->getApplicationUrl() ?: Bootstrap::$WEBURI) . Bootstrap::$SUBURI;

            $deepLink = new Uri($baseUrl);
            $deepLink->addParam('r', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW) . '/' . $id);

            $usersId = [$accountDetails->userId, $accountDetails->userEditId];

            $userService = $this->dic->get(UserService::class);

            $this->eventDispatcher->notifyEvent('request.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Request'))
                    ->addDetail(__u('Requester'), sprintf('%s (%s)', $this->userData->getName(), $this->userData->getLogin()))
                    ->addDetail(__u('Account'), $accountDetails->getName())
                    ->addDetail(__u('Client'), $accountDetails->getClientName())
                    ->addDetail(__u('Description'), $description)
                    ->addDetail(__u('Link'), $deepLink->getUriSigned($this->configData->getPasswordSalt()))
                    ->addExtra('accountId', $id)
                    ->addExtra('whoId', $this->userData->getId())
                    ->setExtra('userId', $usersId)
                    ->setExtra('email', array_map(function ($value) {
                        return $value->email;
                    }, $userService->getUserEmailById($usersId))))
            );

            return $this->returnJsonResponseData(
                [
                    'itemId' => $id,
                    'nextAction' => Acl::getActionRoute(Acl::ACCOUNT)
                ],
                JsonResponse::JSON_SUCCESS,
                __u('Request done')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Initialize class
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AuthException
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        if ($this->actionName !== 'viewLinkAction') {
            $this->checkLoggedIn();
        }

        if (DEBUG === true
            && $this->session->getAppStatus() === SessionContext::APP_STATUS_RELOADED
        ) {
            $this->session->resetAppStatus();

            // Reset de los datos de ACL de cuentas
            AccountAclService::clearAcl($this->session->getUserData()->getId());
        }

        $this->accountService = $this->dic->get(AccountService::class);
        $this->icons = $this->theme->getIcons();
    }
}