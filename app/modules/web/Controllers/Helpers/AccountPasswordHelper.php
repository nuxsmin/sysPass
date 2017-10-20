<?php

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Core\Acl;
use SP\Core\ActionsInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\SessionUtil;
use SP\DataModel\AccountPassData;
use SP\Mgmt\Users\UserPass;
use SP\Core\Crypt\Session as CryptSession;
use SP\Util\ImageUtil;

/**
 * Class AccountPasswordHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
class AccountPasswordHelper extends HelperBase
{
    const TYPE_NORMAL = 0;
    const TYPE_FULL = 1;

    /** @var  Acl */
    protected $acl;

    /**
     * @param AccountPassData $account
     * @param Acl $acl
     * @param $type
     * @return string
     */
    public function getPassword(AccountPassData $account, Acl $acl, $type)
    {
        $this->acl = $acl;

        switch ($type) {
            case self::TYPE_NORMAL:
                return $this->getPasswordClear($account);
                break;
            case self::TYPE_FULL:
                $this->setTemplateVars($account);
                break;
        }
    }

    /**
     * Returns account's password
     *
     * @param AccountPassData $accountData
     * @return string
     * @throws HelperException
     */
    protected function getPasswordClear(AccountPassData $accountData)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::ACTION_ACC_VIEW_PASS)
            || $accountData->getAccountId() === 0
        ) {
            throw new HelperException(__('No tiene permisos para acceder a esta cuenta', false));
        }

        if (!UserPass::checkUserUpdateMPass($this->session->getUserData()->getUserId())) {
            throw new HelperException(__('Clave maestra actualizada') . '<br>' . __('Reinicie la sesión para cambiarla'));
        }

        $key = CryptSession::getSessionKey();
        $securedKey = Crypt::unlockSecuredKey($accountData->getAccountKey(), $key);

        return trim(Crypt::decrypt($accountData->getAccountPass(), $securedKey, $key));
    }

    /**
     * @param AccountPassData $accountData
     */
    protected function setTemplateVars(AccountPassData $accountData)
    {
        $this->view->addTemplate('viewpass');

        $this->view->assign('header', __('Clave de Cuenta'));
        $this->view->assign('login', $accountData->getAccountLogin());

        $pass = $this->getPasswordClear($accountData);

        if ($this->configData->isAccountPassToImage()) {
            $this->view->assign('pass', ImageUtil::convertText($pass));
            $this->view->assign('isImage', 1);
        } else {
            $this->view->assign('pass', htmlentities($pass));
            $this->view->assign('isImage', 0);
        }

        $this->view->assign('isLinked', $accountData->account_parentId > 0);
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
    }
}