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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\FileNotFoundException;
use SP\DataModel\AccountPassData;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Modules\Web\Controllers\Helpers\HelperException;
use SP\Repositories\NoSuchItemException;
use SP\Services\Crypt\MasterPassService;
use SP\Services\ServiceException;
use SP\Util\ImageUtil;

/**
 * Class AccountPasswordHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class AccountPasswordHelper extends HelperBase
{
    /**
     * @var Acl
     */
    private $acl;

    /**
     * @param AccountPassData $accountData
     *
     * @param bool            $useImage
     *
     * @return array
     * @throws HelperException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws CryptoException
     * @throws FileNotFoundException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function getPasswordView(AccountPassData $accountData, bool $useImage)
    {
        $this->checkActionAccess();

        $this->view->addTemplate('viewpass');

        $this->view->assign('header', __('Account Password'));
        $this->view->assign('isImage', (int)$useImage);

        $pass = $this->getPasswordClear($accountData);

        if ($useImage) {
            $imageUtil = $this->dic->get(ImageUtil::class);

            $this->view->assign('login', $imageUtil->convertText($accountData->getLogin()));
            $this->view->assign('pass', $imageUtil->convertText($pass));
        } else {
            $this->view->assign('login', $accountData->getLogin());
            $this->view->assign('pass', htmlspecialchars($pass, ENT_COMPAT));
        }

        return [
            'useimage' => $useImage,
            'html' => $this->view->render()
        ];
    }

    /**
     * @throws HelperException
     */
    private function checkActionAccess()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::ACCOUNT_VIEW_PASS)) {
            throw new HelperException(__u('You don\'t have permission to access this account'));
        }
    }

    /**
     * Returns account's password
     *
     * @param AccountPassData $accountData
     *
     * @return string
     * @throws HelperException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws CryptoException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function getPasswordClear(AccountPassData $accountData)
    {
        $this->checkActionAccess();

        if (!$this->dic->get(MasterPassService::class)->checkUserUpdateMPass($this->context->getUserData()->getLastUpdateMPass())) {
            throw new HelperException(__('Master password updated') . '<br>' . __('Please, restart the session for update it'));
        }

        return trim(Crypt::decrypt($accountData->getPass(), $accountData->getKey(), CryptSession::getSessionKey($this->context)));
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function initialize()
    {
        $this->acl = $this->dic->get(Acl::class);
    }
}