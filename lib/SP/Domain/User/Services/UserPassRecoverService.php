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

namespace SP\Domain\User\Services;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Core\Messages\MailMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\User\In\UserPassRecoverRepositoryInterface;
use SP\Domain\User\UserPassRecoverServiceInterface;
use SP\Html\Html;
use SP\Infrastructure\User\Repositories\UserPassRecoverRepository;
use SP\Util\PasswordUtil;

/**
 * Class UserPassRecoverService
 *
 * @package SP\Domain\Common\Services\UserPassRecover
 */
final class UserPassRecoverService extends Service implements UserPassRecoverServiceInterface
{
    /**
     * Tiempo máximo para recuperar la clave
     */
    private const MAX_PASS_RECOVER_TIME = 3600;
    /**
     * Número de intentos máximos para recuperar la clave
     */
    public const MAX_PASS_RECOVER_LIMIT = 3;

    protected UserPassRecoverRepository $userPassRecoverRepository;

    public function __construct(Application $application, UserPassRecoverRepositoryInterface $userPassRecoverRepository)
    {
        parent::__construct($application);

        $this->userPassRecoverRepository = $userPassRecoverRepository;
    }

    public static function getMailMessage(string $hash): MailMessage
    {
        $mailMessage = new MailMessage();
        $mailMessage->setTitle(__('Password Change'));
        $mailMessage->addDescription(__('A request for changing your user password has been done.'));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(__('In order to complete the process, please go to this URL:'));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(
            Html::anchorText(BootstrapBase::$WEBURI.'/index.php?r=userPassReset/reset/'.$hash)
        );
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(__('If you have not requested this action, please dismiss this message.'));

        return $mailMessage;
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function toggleUsedByHash(string $hash): void
    {
        if ($this->userPassRecoverRepository->toggleUsedByHash(
                $hash,
                time() - self::MAX_PASS_RECOVER_TIME
            ) === 0
        ) {
            throw new ServiceException(__u('Wrong hash or expired'), SPException::INFO);
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws EnvironmentIsBrokenException
     */
    public function requestForUserId(int $id): string
    {
        if ($this->checkAttemptsByUserId($id)) {
            throw new ServiceException(__u('Attempts exceeded'), SPException::WARNING);
        }

        $hash = PasswordUtil::generateRandomBytes(16);

        $this->add($id, $hash);

        return $hash;
    }

    /**
     * Comprobar el límite de recuperaciones de clave.
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkAttemptsByUserId(int $userId): bool
    {
        return $this->userPassRecoverRepository->getAttemptsByUserId(
                $userId,
                time() - self::MAX_PASS_RECOVER_TIME
            ) >= self::MAX_PASS_RECOVER_LIMIT;
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(int $userId, string $hash): bool
    {
        return $this->userPassRecoverRepository->add($userId, $hash);
    }

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserIdForHash(string $hash): int
    {
        $result = $this->userPassRecoverRepository->getUserIdForHash(
            $hash,
            time() - self::MAX_PASS_RECOVER_TIME
        );

        if ($result->getNumRows() === 0) {
            throw new ServiceException(__u('Wrong hash or expired'), SPException::INFO);
        }

        return (int)$result->getData()->userId;
    }
}