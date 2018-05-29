<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
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

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\DataModel\AccountPassData;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Modules\Web\Controllers\Helpers\HelperException;
use SP\Services\Crypt\MasterPassService;
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

    /** @var  \SP\Core\Acl\Acl */
    protected $acl;

    /**
     * @param AccountPassData  $account
     * @param \SP\Core\Acl\Acl $acl
     * @param                  $type
     * @return string|null
     * @throws HelperException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
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

        return null;
    }

    /**
     * Returns account's password
     *
     * @param AccountPassData $accountData
     * @return string
     * @throws HelperException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    protected function getPasswordClear(AccountPassData $accountData)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::ACCOUNT_VIEW_PASS)
            || $accountData->getId() === 0
        ) {
            throw new HelperException(__u('No tiene permisos para acceder a esta cuenta'));
        }

        if (!$this->dic->get(MasterPassService::class)->checkUserUpdateMPass($this->context->getUserData()->getLastUpdateMPass())) {
            throw new HelperException(__('Clave maestra actualizada') . '<br>' . __('Reinicie la sesión para cambiarla'));
        }

        return trim(Crypt::decrypt($accountData->getPass(), $accountData->getKey(), CryptSession::getSessionKey($this->context)));
    }

    /**
     * @param AccountPassData $accountData
     * @throws HelperException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function setTemplateVars(AccountPassData $accountData)
    {
        $this->view->addTemplate('viewpass');

        $this->view->assign('header', __('Clave de Cuenta'));
        $this->view->assign('login', $accountData->getLogin());

        $pass = $this->getPasswordClear($accountData);

        if ($this->configData->isAccountPassToImage()) {
            $this->view->assign('pass', ImageUtil::convertText($pass));
            $this->view->assign('isImage', 1);
        } else {
            $this->view->assign('pass', htmlentities($pass));
            $this->view->assign('isImage', 0);
        }

        $this->view->assign('sk', $this->context->generateSecurityKey());
    }
}