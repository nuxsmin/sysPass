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

namespace SP\Modules\Web\Controllers\User;


use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPMailer\PHPMailer\Exception;
use SP\Core\Application;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\Ports\UserService;
use SP\Domain\User\Services\UserPassRecover;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Forms\UserForm;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class UserSaveBase
 */
abstract class UserSaveBase extends ControllerBase
{
    protected UserService $userService;
    protected CustomFieldDataService $customFieldService;
    protected UserForm  $form;
    private MailService            $mailService;
    private UserPassRecoverService $userPassRecoverService;

    public function __construct(
        Application            $application,
        WebControllerHelper    $webControllerHelper,
        UserService $userService,
        CustomFieldDataService $customFieldService,
        MailService            $mailService,
        UserPassRecoverService $userPassRecoverService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->userService = $userService;
        $this->customFieldService = $customFieldService;
        $this->mailService = $mailService;
        $this->userPassRecoverService = $userPassRecoverService;
        $this->form = new UserForm($application, $this->request);
    }

    /**
     * @param  int  $userId
     * @param User $userData
     *
     * @throws EnvironmentIsBrokenException
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    final protected function checkChangeUserPass(int $userId, User $userData): void
    {
        if ($userData->isChangePass()) {
            $hash = $this->userPassRecoverService->requestForUserId($userId);

            $this->mailService->send(
                __('Password Change'),
                $userData->getEmail(),
                UserPassRecover::getMailMessage($hash)
            );
        }
    }
}
