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

namespace SP\Domain\User\Services;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Core\Application;
use SP\Core\Messages\MailMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\UserPassRecover as UserPassRecoverModel;
use SP\Domain\User\Ports\UserPassRecoverRepository;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Html\Html;
use SP\Util\Password;

use function SP\__;
use function SP\__u;

/**
 * Class UserPassRecover
 */
final class UserPassRecover extends Service implements UserPassRecoverService
{
    /**
     * Tiempo máximo para recuperar la clave
     */
    private const MAX_PASS_RECOVER_TIME = 3600;
    /**
     * Número de intentos máximos para recuperar la clave
     */
    public const MAX_PASS_RECOVER_LIMIT = 3;

    public function __construct(
        Application                                $application,
        private readonly UserPassRecoverRepository $userPassRecoverRepository
    ) {
        parent::__construct($application);
    }

    public static function getMailMessage(string $hash, string $baseUri): MailMessage
    {
        $mailMessage = new MailMessage();
        $mailMessage->setTitle(__('Password Change'));
        $mailMessage->addDescription(__('A request for changing your user password has been done.'));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(__('In order to complete the process, please go to this URL:'));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(
            Html::anchorText(sprintf('%s/index.php?r=userPassReset/reset/%s', $baseUri, $hash))
        );
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(__('If you have not requested this action, please dismiss this message.'));

        return $mailMessage;
    }

    /**
     * @throws SPException
     * @throws ServiceException
     */
    public function toggleUsedByHash(string $hash): void
    {
        $time = time() - self::MAX_PASS_RECOVER_TIME;

        if ($this->userPassRecoverRepository->toggleUsedByHash($hash, $time) === 0) {
            throw ServiceException::info(__u('Wrong hash or expired'));
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     * @throws EnvironmentIsBrokenException
     */
    public function requestForUserId(int $id): string
    {
        if ($this->checkAttemptsByUserId($id)) {
            throw ServiceException::warning(__u('Attempts exceeded'));
        }

        $hash = Password::generateRandomBytes(16);

        $this->add($id, $hash);

        return $hash;
    }

    /**
     * Comprobar el límite de recuperaciones de clave.
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkAttemptsByUserId(int $userId): bool
    {
        $time = time() - self::MAX_PASS_RECOVER_TIME;

        return $this->userPassRecoverRepository->getAttemptsByUserId($userId, $time) >= self::MAX_PASS_RECOVER_LIMIT;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $userId, string $hash): void
    {
        $this->userPassRecoverRepository->add($userId, $hash);
    }

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @param string $hash
     * @return int
     * @throws ServiceException
     */
    public function getUserIdForHash(string $hash): int
    {
        $time = time() - self::MAX_PASS_RECOVER_TIME;
        $result = $this->userPassRecoverRepository->getUserIdForHash($hash, $time);

        if ($result->getNumRows() === 0) {
            throw ServiceException::info(__u('Wrong hash or expired'));
        }

        return $result->getData(UserPassRecoverModel::class)->getUserId();
    }
}
