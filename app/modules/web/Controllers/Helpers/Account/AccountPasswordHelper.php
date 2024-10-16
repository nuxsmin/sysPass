<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Crypt\Session as CryptSession;
use SP\Domain\Account\Adapters\AccountPassItemWithIdAndName;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Image\Ports\ImageService;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Modules\Web\Controllers\Helpers\HelperException;
use SP\Mvc\View\TemplateInterface;

use function SP\__;
use function SP\__u;

/**
 * Class AccountPasswordHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class AccountPasswordHelper extends HelperBase
{

    public function __construct(
        Application                        $application,
        TemplateInterface                  $template,
        RequestService                     $request,
        private readonly AclInterface      $acl,
        private readonly ImageService      $imageUtil,
        private readonly MasterPassService $masterPassService,
        private readonly CryptInterface    $crypt
    ) {
        parent::__construct($application, $template, $request);
    }

    /**
     * @param AccountPassItemWithIdAndName $accountData
     *
     * @param bool $useImage
     *
     * @return array
     * @throws CryptException
     * @throws HelperException
     */
    public function getPasswordView(
        AccountPassItemWithIdAndName $accountData,
        bool                         $useImage
    ): array {
        $this->checkActionAccess();

        $this->view->addTemplate('viewpass');

        $this->view->assign('header', __('Account Password'));
        $this->view->assign('isImage', (int)$useImage);

        $pass = $this->getPasswordClear($accountData);

        if ($useImage) {
            $this->view->assign(
                'login',
                $this->imageUtil->convertText($accountData->getLogin())
            );
            $this->view->assign(
                'pass',
                $this->imageUtil->convertText($pass)
            );
        } else {
            $this->view->assign('login', $accountData->getLogin());
            $this->view->assign(
                'pass',
                htmlspecialchars($pass, ENT_COMPAT)
            );
        }

        return [
            'useimage' => $useImage,
            'html' => $this->view->render(),
        ];
    }

    /**
     * @throws HelperException
     */
    private function checkActionAccess(): void
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_VIEW_PASS)) {
            throw new HelperException(__u('You don\'t have permission to access this account'));
        }
    }

    /**
     * Returns account's password
     *
     * @param AccountPassItemWithIdAndName $accountPassItemWithIdAndName
     *
     * @return string
     * @throws HelperException
     * @throws CryptException
     */
    public function getPasswordClear(AccountPassItemWithIdAndName $accountPassItemWithIdAndName): string
    {
        $this->checkActionAccess();

        if (!$this->masterPassService->checkUserUpdateMPass($this->context->getUserData()->lastUpdateMPass)) {
            throw new HelperException(
                __('Master password updated')
                . '<br>'
                . __('Please, restart the session for update it')
            );
        }

        return trim(
            $this->crypt->decrypt(
                $accountPassItemWithIdAndName->getPass(),
                $accountPassItemWithIdAndName->getKey(),
                CryptSession::getSessionKey($this->context)
            )
        );
    }
}
