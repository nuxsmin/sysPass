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
use SP\DataModel\UserData;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Notification\Ports\MailServiceInterface;
use SP\Domain\User\Ports\UserPassRecoverServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Domain\User\Services\UserPassRecoverService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Forms\UserForm;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class UserSaveBase
 */
abstract class UserSaveBase extends ControllerBase
{
    protected UserServiceInterface   $userService;
    protected CustomFieldDataService $customFieldService;
    protected UserForm               $form;
    private MailServiceInterface            $mailService;
    private UserPassRecoverServiceInterface $userPassRecoverService;

    public function __construct(
        Application            $application,
        WebControllerHelper    $webControllerHelper,
        UserServiceInterface   $userService,
        CustomFieldDataService $customFieldService,
        MailServiceInterface   $mailService,
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
     * @throws EnvironmentIsBrokenException
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
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
