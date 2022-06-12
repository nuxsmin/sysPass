<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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


use SP\Core\Application;
use SP\DataModel\UserData;
use SP\Domain\CustomField\CustomFieldServiceInterface;
use SP\Domain\Notification\MailServiceInterface;
use SP\Domain\User\Services\UserPassRecoverService;
use SP\Domain\User\UserPassRecoverServiceInterface;
use SP\Domain\User\UserServiceInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Forms\UserForm;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class UserSaveBase
 */
abstract class UserSaveBase extends ControllerBase
{
    protected UserServiceInterface          $userService;
    protected CustomFieldServiceInterface   $customFieldService;
    protected UserForm                      $form;
    private MailServiceInterface            $mailService;
    private UserPassRecoverServiceInterface $userPassRecoverService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        UserServiceInterface $userService,
        CustomFieldServiceInterface $customFieldService,
        MailServiceInterface $mailService,
        UserPassRecoverServiceInterface $userPassRecoverService
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
     * @param  UserData  $userData
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    final protected function checkChangeUserPass(int $userId, UserData $userData): void
    {
        if ($userData->isChangePass()) {
            $hash = $this->userPassRecoverService->requestForUserId($userId);

            $this->mailService->send(
                __('Password Change'),
                $userData->getEmail(),
                UserPassRecoverService::getMailMessage($hash)
            );
        }
    }
}