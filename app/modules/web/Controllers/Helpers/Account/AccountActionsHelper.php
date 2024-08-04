<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Modules\Web\Controllers\Helpers\Account;

use SP\Core\Application;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Adapters\AccountSearchItem;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionType;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Mvc\View\TemplateInterface;

use function SP\__;

/**
 * Class AccountActionsHelper
 */
final class AccountActionsHelper extends HelperBase
{
    public function __construct(
        Application                          $application,
        TemplateInterface                    $template,
        RequestService                       $request,
        private readonly ThemeIconsInterface $icons,
        private readonly AclInterface        $acl
    ) {
        parent::__construct($application, $template, $request);
    }

    /**
     * @return DataGridAction
     */
    public function getViewAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_VIEW);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Account Details'));
        $action->setTitle(__('Account Details'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->view());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowView');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW));
        $action->addData('onclick', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * Set icons for view
     *
     * @param AccountPermission $accountAcl
     * @param AccountActionsDto $accountActionsDto
     *
     * @return DataGridAction[]
     */
    public function getActionsForAccount(
        AccountPermission $accountAcl,
        AccountActionsDto $accountActionsDto
    ): array {
        $actions = [];

        $actionBack = $this->getBackAction();

        if ($accountActionsDto->isHistory()) {
            $actionBack->addData('item-id', $accountActionsDto->getAccountId());
            $actionBack->setName(__('View Current'));
            $actionBack->setTitle(__('View Current'));
        } else {
            $actionBack->setData([]);
            $actionBack->setClasses(['btn-back']);
        }

        $actions[] = $actionBack;

        if ($accountAcl->isShowEditPass()) {
            $actions[] = $this->getEditPassAction()->addData('item-id', $accountActionsDto->getAccountId());
        }

        if ($accountAcl->isShowEdit()) {
            $actions[] = $this->getEditAction()->addData('item-id', $accountActionsDto->getAccountId());
        }

        if ($accountAcl->getActionId() === AclActionsInterface::ACCOUNT_VIEW
            && !$accountAcl->isShowEdit()
            && $this->configData->isMailRequestsEnabled()
        ) {
            $actions[] = $this->getRequestAction()->addData('item-id', $accountActionsDto->getAccountId());
        }

        if ($accountAcl->isShowRestore()) {
            $actionRestore = $this->getRestoreAction();
            $actionRestore->addData('item-id', $accountActionsDto->getAccountId());
            $actionRestore->addData('history-id', $accountActionsDto->getAccountHistoryId());

            $actions[] = $actionRestore;
        }

        if ($accountAcl->isShowSave()) {
            $actions[] = $this->getSaveAction()->addAttribute('form', 'frmAccount');
        }

        return $actions;
    }

    /**
     * @return DataGridAction
     */
    public function getBackAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(0);
        $action->setName(__('Back'));
        $action->setTitle(__('Back'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->back());
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW));
        $action->addData('onclick', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getEditPassAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_EDIT_PASS);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Edit Account Password'));
        $action->setTitle(__('Edit Account Password'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->editPass());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowViewPass');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_EDIT_PASS));
        $action->addData('onclick', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_EDIT_PASS));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getEditAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_EDIT);
        $action->setType(DataGridActionType::EDIT_ITEM);
        $action->setName(__('Edit Account'));
        $action->setTitle(__('Edit Account'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->edit());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowEdit');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_EDIT));
        $action->addData('onclick', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_EDIT));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getRequestAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_REQUEST);
        $action->setName(__('Request Modification'));
        $action->setTitle(__('Request Modification'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->email());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowRequest');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_REQUEST));
        $action->addData('onclick', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'submit');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getRestoreAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_EDIT_RESTORE);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Restore account from this point'));
        $action->setTitle(__('Restore account from this point'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->restore());
        $action->addData('action-route', 'account/saveEditRestore');
        $action->addData('onclick', 'account/saveEditRestore');
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getSaveAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Save'));
        $action->setTitle(__('Save'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->save());
        $action->addData('action-route', 'account/save');
        $action->addData('onclick', 'account/save');
        $action->addAttribute('type', 'submit');

        return $action;
    }

    /**
     * Set icons for view
     *
     * @param AccountPermission $accountAcl
     * @param AccountActionsDto $accountActionsDto
     *
     * @return DataGridAction[]
     */
    public function getActionsGrouppedForAccount(
        AccountPermission $accountAcl,
        AccountActionsDto $accountActionsDto
    ): array {
        $userData = $this->context->getUserData();

        $actions = [];

        if ($accountAcl->isShowDelete()) {
            $actions[] = $this->getDeleteAction()
                              ->addData('item-id', $accountActionsDto->getAccountId());
        }

        if ($accountActionsDto->isHistory() === false
            && $accountActionsDto->isLinked() === false
            && $this->configData->isPublinksEnabled()
            && $accountAcl->isShowLink()
            && $accountAcl->isShowViewPass()
        ) {
            $itemId = $accountActionsDto->getPublicLinkId();

            if ($itemId) {
                $actionRefresh = $this->getPublicLinkRefreshAction();
                $actionRefresh->addData('item-id', $itemId);
                $actionRefresh->addData('account-id', $accountActionsDto->getAccountId());

                $actions[] = $actionRefresh;

                if ($userData->getIsAdminApp()
                    || $userData->getId() === $accountActionsDto->getPublicLinkCreatorId()
                ) {
                    $actionDelete = $this->getPublicLinkDeleteAction();
                    $actionDelete->addData('item-id', $itemId);
                    $actionDelete->addData('account-id', $accountActionsDto->getAccountId());

                    $actions[] = $actionDelete;
                }
            } else {
                $action = $this->getPublicLinkAction();
                $action->addData('account-id', $accountActionsDto->getAccountId());

                $actions[] = $action;
            }
        }

        if ($accountAcl->isShowViewPass()) {
            if ($accountActionsDto->isHistory()) {
                $actionViewPass = $this->getViewPassHistoryAction()
                                       ->addData('item-id', $accountActionsDto->getAccountHistoryId());
                $actionCopy = $this->getCopyPassHistoryAction()
                                   ->addData('item-id', $accountActionsDto->getAccountHistoryId());
            } else {
                $actionViewPass = $this->getViewPassAction()
                                       ->addData('item-id', $accountActionsDto->getAccountId());
                $actionCopy = $this->getCopyPassAction()
                                   ->addData('item-id', $accountActionsDto->getAccountId());
            }

            $actionViewPass->addData('parent-id', $accountActionsDto->getAccountParentId());
            $actionCopy->addData('parent-id', $accountActionsDto->getAccountParentId());

            $actions[] = $actionViewPass;
            $actions[] = $actionCopy;
        }

        if ($accountAcl->isShowCopy()) {
            $actions[] = $this->getCopyAction()
                              ->addData('item-id', $accountActionsDto->getAccountId());
        }

        return $actions;
    }

    /**
     * @return DataGridAction
     */
    public function getDeleteAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_DELETE);
        $action->setType(DataGridActionType::DELETE_ITEM);
        $action->setName(__('Remove Account'));
        $action->setTitle(__('Remove Account'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->delete());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowDelete');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_DELETE));
        $action->addData('onclick', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_DELETE));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getPublicLinkRefreshAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::PUBLICLINK_REFRESH);
        $action->setName(__('Update Public Link'));
        $action->setTitle(__('Update Public Link'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->publicLink());
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::PUBLICLINK_REFRESH));
        $action->addData('onclick', 'link/refresh');
        $action->addData('action-next', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getPublicLinkDeleteAction(): DataGridAction
    {
        $icon = $this->icons->publicLink()->mutate('link_off');

        $action = new DataGridAction();
        $action->setId(AclActionsInterface::PUBLICLINK_DELETE);
        $action->setName(__('Delete Public Link'));
        $action->setTitle(__('Delete Public Link'));
        $action->addClass('btn-action');
        $action->setIcon($icon);
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::PUBLICLINK_DELETE));
        $action->addData('onclick', 'link/delete');
        $action->addData('action-next', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getPublicLinkAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::PUBLICLINK_CREATE);
        $action->setName(__('Create Public Link'));
        $action->setTitle(__('Create Public Link'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->publicLink());
        $action->addData('action-route', 'publicLink/saveCreateFromAccount');
        $action->addData('onclick', 'link/save');
        $action->addData('action-next', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getViewPassHistoryAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_VIEW_PASS);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('View password'));
        $action->setTitle(__('View password'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->viewPass());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowViewPass');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_HISTORY_VIEW_PASS));
        $action->addData('action-full', 1);
        $action->addData('onclick', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_HISTORY_VIEW_PASS));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getCopyPassHistoryAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_VIEW_PASS);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Copy Password to Clipboard'));
        $action->setTitle(__('Copy Password to Clipboard'));
        $action->addClass('btn-action');
        $action->addClass('clip-pass-button');
        $action->setIcon($this->icons->clipboard());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowCopyPass');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_HISTORY_COPY_PASS));
        $action->addData('action-full', 0);
        $action->addData('useclipboard', '1');
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getViewPassAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_VIEW_PASS);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('View password'));
        $action->setTitle(__('View password'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->viewPass());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowViewPass');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW_PASS));
        $action->addData('action-full', 1);
        $action->addData('onclick', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW_PASS));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getCopyPassAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_COPY_PASS);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Copy Password to Clipboard'));
        $action->setTitle(__('Copy Password to Clipboard'));
        $action->addClass('btn-action');
        $action->addClass('clip-pass-button');
        $action->setIcon($this->icons->clipboard());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowCopyPass');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_COPY_PASS));
        $action->addData('action-full', 0);
        $action->addData('useclipboard', '1');
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getCopyAction(): DataGridAction
    {
        $action = new DataGridAction();
        $action->setId(AclActionsInterface::ACCOUNT_COPY);
        $action->setType(DataGridActionType::MENUBAR_ITEM);
        $action->setName(__('Copy Account'));
        $action->setTitle(__('Copy Account'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->copy());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowCopy');
        $action->addData('action-route', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_COPY));
        $action->addData('onclick', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_COPY));
        $action->addAttribute('type', 'button');

        return $action;
    }
}
