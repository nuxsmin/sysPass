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

namespace SP\Services\Crypt;

use Defuse\Crypto\Exception\CryptoException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\AppInfoInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Messages\MailMessage;
use SP\DataModel\Dto\ConfigRequest;
use SP\Repositories\NoSuchItemException;
use SP\Services\Config\ConfigService;
use SP\Services\Mail\MailService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\User\UserService;
use SP\Util\PasswordUtil;

/**
 * Class TemporaryMasterPassService
 *
 * @package SP\Services\Crypt
 */
final class TemporaryMasterPassService extends Service
{
    /**
     * Número máximo de intentos
     */
    const MAX_ATTEMPTS = 50;
    /**
     * Parámetros de configuración
     */
    const PARAM_PASS = 'tempmaster_pass';
    const PARAM_KEY = 'tempmaster_passkey';
    const PARAM_HASH = 'tempmaster_passhash';
    const PARAM_TIME = 'tempmaster_passtime';
    const PARAM_MAX_TIME = 'tempmaster_maxtime';
    const PARAM_ATTEMPTS = 'tempmaster_attempts';
    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var int
     */
    protected $maxTime;

    /**
     * Crea una clave temporal para encriptar la clave maestra y guardarla.
     *
     * @param int $maxTime El tiempo máximo de validez de la clave
     *
     * @return string
     * @throws ServiceException
     */
    public function create($maxTime = 14400)
    {
        try {
            $this->maxTime = time() + $maxTime;

            // Encriptar la clave maestra con hash aleatorio generado
            $randomKey = PasswordUtil::generateRandomBytes(32);
            $secureKey = Crypt::makeSecuredKey($randomKey);

            $configRequest = new ConfigRequest();
            $configRequest->add(self::PARAM_PASS, Crypt::encrypt($this->getMasterKeyFromContext(), $secureKey, $randomKey));
            $configRequest->add(self::PARAM_KEY, $secureKey);
            $configRequest->add(self::PARAM_HASH, Hash::hashKey($randomKey));
            $configRequest->add(self::PARAM_TIME, time());
            $configRequest->add(self::PARAM_MAX_TIME, $this->maxTime);
            $configRequest->add(self::PARAM_ATTEMPTS, 0);

            $this->configService->saveBatch($configRequest);

            // Guardar la clave temporal hasta que finalice la sesión
            $this->context->setTemporaryMasterPass($randomKey);

            $this->eventDispatcher->notifyEvent('create.tempMasterPassword',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Generate temporary password')))
            );

            return $randomKey;
        } catch (Exception $e) {
            processException($e);

            throw new ServiceException(__u('Error while generating the temporary password'));
        }
    }

    /**
     * Comprueba si la clave temporal es válida
     *
     * @param string $pass clave a comprobar
     *
     * @return bool
     * @throws ServiceException
     */
    public function checkTempMasterPass($pass)
    {
        try {
            $isValid = false;
            $passMaxTime = (int)$this->configService->getByParam(self::PARAM_MAX_TIME);

            // Comprobar si el tiempo de validez o los intentos se han superado
            if ($passMaxTime === 0) {
                $this->eventDispatcher->notifyEvent('check.tempMasterPassword',
                    new Event($this, EventMessage::factory()->addDescription(__u('Temporary password expired')))
                );

                return $isValid;
            }

            $passTime = (int)$this->configService->getByParam(self::PARAM_TIME);
            $attempts = (int)$this->configService->getByParam(self::PARAM_ATTEMPTS);

            if ((!empty($passTime) && time() > $passMaxTime)
                || $attempts >= self::MAX_ATTEMPTS
            ) {
                $this->expire();

                return $isValid;
            }

            $isValid = Hash::checkHashKey($pass, $this->configService->getByParam(self::PARAM_HASH));

            if (!$isValid) {
                $this->configService->save(self::PARAM_ATTEMPTS, $attempts + 1);
            }

            return $isValid;
        } catch (NoSuchItemException $e) {
            return false;
        } catch (Exception $e) {
            processException($e);

            throw new ServiceException(__u('Error while checking the temporary password'));
        }
    }

    /**
     * @throws ServiceException
     */
    protected function expire()
    {
        $configRequest = new ConfigRequest();
        $configRequest->add(self::PARAM_PASS, '');
        $configRequest->add(self::PARAM_KEY, '');
        $configRequest->add(self::PARAM_HASH, '');
        $configRequest->add(self::PARAM_TIME, 0);
        $configRequest->add(self::PARAM_MAX_TIME, 0);
        $configRequest->add(self::PARAM_ATTEMPTS, 0);

        $this->configService->saveBatch($configRequest);

        $this->eventDispatcher->notifyEvent('expire.tempMasterPassword',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Temporary password expired')))
        );
    }

    /**
     * @param $groupId
     * @param $key
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function sendByEmailForGroup($groupId, $key)
    {
        $mailMessage = $this->getMessageForEmail($key);

        $emails = array_map(function ($value) {
            return $value->email;
        }, $this->dic->get(UserService::class)->getUserEmailForGroup($groupId));

        $this->dic->get(MailService::class)
            ->sendBatch($mailMessage->getTitle(), $emails, $mailMessage);
    }

    /**
     * @param $key
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function sendByEmailForAllUsers($key)
    {
        $mailMessage = $this->getMessageForEmail($key);

        $emails = array_map(function ($value) {
            return $value->email;
        }, $this->dic->get(UserService::class)->getUserEmailForAll());

        $this->dic->get(MailService::class)
            ->sendBatch($mailMessage->getTitle(), $emails, $mailMessage);
    }

    /**
     * @param $key
     *
     * @return MailMessage
     */
    private function getMessageForEmail($key)
    {
        $mailMessage = new MailMessage();
        $mailMessage->setTitle(sprintf(__('%s Master Password'), AppInfoInterface::APP_NAME));
        $mailMessage->addDescription(__('A new sysPass master password has been generated, so next time you log into the application it will be requested.'));
        $mailMessage->addDescription(sprintf(__('The new Master Password is: %s'), $key));
        $mailMessage->addDescription(sprintf(__('This password will be valid until: %s'), date('r', $this->getMaxTime())));
        $mailMessage->addDescription(__('Please, don\'t forget to log in as soon as possible to save the changes.'));

        return $mailMessage;
    }

    /**
     * @return int
     */
    public function getMaxTime()
    {
        return $this->maxTime;
    }

    /**
     * Devuelve la clave maestra que ha sido encriptada con la clave temporal
     *
     * @param $key string con la clave utilizada para encriptar
     *
     * @return string con la clave maestra desencriptada
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws CryptoException
     */
    public function getUsingKey($key)
    {
        return Crypt::decrypt($this->configService->getByParam(self::PARAM_PASS),
            $this->configService->getByParam(self::PARAM_KEY),
            $key);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->configService = $this->dic->get(ConfigService::class);
    }
}