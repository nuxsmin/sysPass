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

namespace SP\Services\UserPassRecover;

use SP\Bootstrap;
use SP\Core\Messages\MailMessage;
use SP\Html\Html;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Util\Util;

/**
 * Class UserPassRecoverService
 *
 * @package SP\Services\UserPassRecover
 */
class UserPassRecoverService extends Service
{
    /**
     * Tiempo máximo para recuperar la clave
     */
    const MAX_PASS_RECOVER_TIME = 3600;
    /**
     * Número de intentos máximos para recuperar la clave
     */
    const MAX_PASS_RECOVER_LIMIT = 3;
    const USER_LOGIN_EXIST = 1;
    const USER_MAIL_EXIST = 2;

    /**
     * @var \SP\Repositories\User\UserPassRecoverRepository
     */
    protected $userPassRecoverRepository;

    /**
     * @param $hash
     *
     * @return MailMessage
     */
    public static function getMailMessage($hash)
    {
        $mailMessage = new MailMessage();
        $mailMessage->setTitle(__('Cambio de Clave'));
        $mailMessage->addDescription(__('Se ha solicitado el cambio de su clave de usuario.'));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(__('Para completar el proceso es necesario que acceda a la siguiente URL:'));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(Html::anchorText(Bootstrap::$WEBURI . '/index.php?r=userPassReset/reset/' . $hash));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(__('Si no ha solicitado esta acción, ignore este mensaje.'));

        return $mailMessage;
    }

    /**
     * @param $hash
     *
     * @return void
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function toggleUsedByHash($hash)
    {
        if ($this->userPassRecoverRepository->toggleUsedByHash($hash, time() - self::MAX_PASS_RECOVER_TIME) === 0) {
            throw new ServiceException(__u('Hash inválido o expirado'), ServiceException::INFO);
        }
    }

    /**
     * @param int $id
     *
     * @return string
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws ServiceException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function requestForUserId($id)
    {
        if ($this->checkAttemptsByUserId($id)) {
            throw new ServiceException(__u('Intentos excedidos'), ServiceException::WARNING);
        }

        $hash = Util::generateRandomBytes(16);

        $this->add($id, $hash);

        return $hash;
    }

    /**
     * Comprobar el límite de recuperaciones de clave.
     *
     * @param int $userId
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkAttemptsByUserId($userId)
    {
        return $this->userPassRecoverRepository->getAttemptsByUserId($userId, time() - self::MAX_PASS_RECOVER_TIME) >= self::MAX_PASS_RECOVER_LIMIT;
    }

    /**
     * @param $userId
     * @param $hash
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add($userId, $hash)
    {
        return $this->userPassRecoverRepository->add($userId, $hash);
    }

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @param string $hash
     *
     * @return int
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUserIdForHash($hash)
    {
        $result = $this->userPassRecoverRepository->getUserIdForHash($hash, time() - self::MAX_PASS_RECOVER_TIME);

        if ($result->getNumRows() === 0) {
            throw new ServiceException(__u('Hash inválido o expirado'), ServiceException::INFO);
        }

        return (int)$result->getData()->userId;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->userPassRecoverRepository = $this->dic->get(\SP\Repositories\User\UserPassRecoverRepository::class);
    }
}