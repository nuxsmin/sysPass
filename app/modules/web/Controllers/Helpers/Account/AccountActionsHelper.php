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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use SP\Account\AccountAcl;
use SP\Account\AccountSearchItem;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\UI\ThemeIcons;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionType;
use SP\Modules\Web\Controllers\Helpers\HelperBase;

/**
 * Class AccountIconsHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
class AccountActionsHelper extends HelperBase
{
    /**
     * @var ThemeIcons
     */
    protected $icons;
    /**
     * @var string
     */
    protected $sk;

    /**
     * @return DataGridAction
     */
    public function getViewAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT_VIEW);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Detalles de Cuenta'));
        $action->setTitle(__('Detalles de Cuenta'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconView());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowView');
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * Set icons for view
     *
     * @param AccountAcl        $accountAcl
     * @param AccountActionsDto $accountActionsDto
     * @return DataGridAction[]
     */
    public function getActionsForAccount(AccountAcl $accountAcl, AccountActionsDto $accountActionsDto)
    {
        $actionsEnabled = [];

        $actionBack = $this->getBackAction();

        if ($accountActionsDto->isHistory()) {
            $actionBack->addData('item-id', $accountActionsDto->getAccountId());
            $actionBack->setName(__('Ver Actual'));
            $actionBack->setTitle(__('Ver Actual'));
        } else {
            $actionBack->setData([]);
            $actionBack->setClasses(['btn-back']);
        }

        $actionsEnabled[] = $actionBack;

        if ($accountAcl->isShowDelete()) {
            $actionsEnabled[] = $this->getDeleteAction()->addData('item-id', $accountActionsDto->getAccountId());
        }

        if ($accountActionsDto->isHistory() === false
            && $accountActionsDto->isLinked() === false
            && $this->configData->isPublinksEnabled()
            && $accountAcl->isShowLink()
            && $accountAcl->isShowViewPass()
        ) {
            $action = $accountActionsDto->getPublicLinkId() ? $this->getPublicLinkRefreshAction() : $this->getPublicLinkAction();
            $itemId = $accountActionsDto->getPublicLinkId() ?: $accountActionsDto->getAccountId();

            $actionsEnabled[] = $action->addData('item-id', $itemId);
        }

        if ($accountAcl->isShowViewPass()) {
            $actionViewPass = $this->getViewPassAction();
            $actionCopy = $this->getCopyPassAction();

            $actionViewPass->addData('parent-id', $accountActionsDto->getAccountParentId());
            $actionCopy->addData('parent-id', $accountActionsDto->getAccountParentId());

            $actionViewPass->addData('history', (int)$accountActionsDto->isHistory());
            $actionCopy->addData('history', (int)$accountActionsDto->isHistory());

            if ($accountActionsDto->isHistory()) {
                $actionViewPass->addData('item-id', $accountActionsDto->getAccountHistoryId());
                $actionCopy->addData('item-id', $accountActionsDto->getAccountHistoryId());
            } else {
                $actionViewPass->addData('item-id', $accountActionsDto->getAccountId());
                $actionCopy->addData('item-id', $accountActionsDto->getAccountId());
            }

            $actionsEnabled[] = $actionViewPass;
            $actionsEnabled[] = $actionCopy;
        }

        if ($accountAcl->isShowCopy()) {
            $actionsEnabled[] = $this->getCopyAction()->addData('item-id', $accountActionsDto->getAccountId());
        }

        if ($accountAcl->isShowEditPass()) {
            $actionsEnabled[] = $this->getEditPassAction()->addData('item-id', $accountActionsDto->getAccountId());
        }

        if ($accountAcl->isShowEdit()) {
            $actionsEnabled[] = $this->getEditAction()->addData('item-id', $accountActionsDto->getAccountId());
        }

        if ($accountAcl->getActionId() === ActionsInterface::ACCOUNT_VIEW
            && !$accountAcl->isShowEdit()
            && $this->configData->isMailRequestsEnabled()
        ) {
            $actionsEnabled[] = $this->getRequestAction()->addData('item-id', $accountActionsDto->getAccountId());
        }

        if ($accountAcl->isShowRestore()) {
            $actionRestore = $this->getRestoreAction();
            $actionRestore->addData('item-id', $accountActionsDto->getAccountId());
            $actionRestore->addData('history-id', $accountActionsDto->getAccountHistoryId());

            $actionsEnabled[] = $actionRestore;
        }

        if ($accountAcl->isShowSave()) {
            $actionsEnabled[] = $this->getSaveAction()->addAttribute('form', 'frmAccount');
        }

        return $actionsEnabled;
    }

    /**
     * @return DataGridAction
     */
    public function getBackAction()
    {
        $action = new DataGridAction();
        $action->setId('btnBack');
        $action->setName(__('Atrás'));
        $action->setTitle(__('Atrás'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconBack());
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getDeleteAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT_DELETE);
        $action->setType(DataGridActionType::DELETE_ITEM);
        $action->setName(__('Eliminar Cuenta'));
        $action->setTitle(__('Eliminar Cuenta'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconDelete());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowDelete');
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_DELETE));
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', Acl::getActionRoute(ActionsInterface::ACCOUNT_DELETE));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getPublicLinkRefreshAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::PUBLICLINK_REFRESH);
        $action->setName(__('Actualizar Enlace Público'));
        $action->setTitle(__('Actualizar Enlace Público'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconPublicLink());
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_REFRESH));
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', 'link/refresh');
        $action->addData('action-next', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getPublicLinkAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::PUBLICLINK_CREATE);
        $action->setName(__('Crear Enlace Público'));
        $action->setTitle(__('Crear Enlace Público'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconPublicLink());
        $action->addData('action-route', 'publicLink/saveCreateFromAccount');
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', 'link/save');
        $action->addData('action-next', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getViewPassAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT_VIEW_PASS);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Ver Clave'));
        $action->setTitle(__('Ver Clave'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconViewPass());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowViewPass');
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW_PASS));
        $action->addData('action-full', 1);
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW_PASS));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getCopyPassAction()
    {
        // Añadir la clase para usar el portapapeles
        $ClipboardIcon = $this->icons->getIconClipboard()->setClass('clip-pass-button');

        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT_VIEW_PASS);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Copiar Clave en Portapapeles'));
        $action->setTitle(__('Copiar Clave en Portapapeles'));
        $action->addClass('btn-action');
        $action->setIcon($ClipboardIcon);
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowCopyPass');
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_COPY_PASS));
        $action->addData('action-full', 0);
        $action->addData('action-sk', $this->sk);
        $action->addData('useclipboard', '1');
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getCopyAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT_COPY);
        $action->setType(DataGridActionType::MENUBAR_ITEM);
        $action->setName(__('Copiar Cuenta'));
        $action->setTitle(__('Copiar Cuenta'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconCopy());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowCopy');
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_COPY));
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', Acl::getActionRoute(ActionsInterface::ACCOUNT_COPY));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getEditPassAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT_EDIT_PASS);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Modificar Clave de Cuenta'));
        $action->setTitle(__('Modificar Clave de Cuenta'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconEditPass());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowViewPass');
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_EDIT_PASS));
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', Acl::getActionRoute(ActionsInterface::ACCOUNT_EDIT_PASS));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getEditAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT_EDIT);
        $action->setType(DataGridActionType::EDIT_ITEM);
        $action->setName(__('Editar Cuenta'));
        $action->setTitle(__('Editar Cuenta'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconEdit());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowEdit');
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_EDIT));
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', Acl::getActionRoute(ActionsInterface::ACCOUNT_EDIT));
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getRequestAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT_REQUEST);
        $action->setName(__('Solicitar Modificación'));
        $action->setTitle(__('Solicitar Modificación'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconEmail());
        $action->setRuntimeFilter(AccountSearchItem::class, 'isShowRequest');
        $action->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_REQUEST));
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));
        $action->addAttribute('type', 'submit');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getRestoreAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT_EDIT_RESTORE);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Restaurar cuenta desde este punto'));
        $action->setTitle(__('Restaurar cuenta desde este punto'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconRestore());
        $action->addData('action-route', 'account/saveEditRestore');
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', 'account/saveEditRestore');
        $action->addAttribute('type', 'button');

        return $action;
    }

    /**
     * @return DataGridAction
     */
    public function getSaveAction()
    {
        $action = new DataGridAction();
        $action->setId(ActionsInterface::ACCOUNT);
        $action->setType(DataGridActionType::VIEW_ITEM);
        $action->setName(__('Guardar'));
        $action->setTitle(__('Guardar'));
        $action->addClass('btn-action');
        $action->setIcon($this->icons->getIconSave());
        $action->addData('action-route', 'account/save');
        $action->addData('action-sk', $this->sk);
        $action->addData('onclick', 'account/save');
        $action->addAttribute('type', 'submit');

        return $action;
    }

    /**
     * Initialize class
     */
    protected function initialize()
    {
        $this->sk = $this->context->generateSecurityKey();
        $this->icons = $this->view->getTheme()->getIcons();
    }
}