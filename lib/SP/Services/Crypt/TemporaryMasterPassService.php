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

namespace SP\Services\Crypt;

use SP\Core\AppInfoInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
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
                    ->addDescription(__u('Generar clave temporal')))
            );

            return $randomKey;
        } catch (\Exception $e) {
            processException($e);

            throw new ServiceException(__u('Error al generar clave temporal'));
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
                    new Event($this, EventMessage::factory()->addDescription(__u('Clave temporal caducada')))
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
        } catch (\Exception $e) {
            processException($e);

            throw new ServiceException(__u('Error al comprobar clave temporal'));
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
                ->addDescription(__u('Clave temporal caducada')))
        );
    }

    /**
     * @param $groupId
     * @param $key
     *
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
     * @return MailMessage
     */
    private function getMessageForEmail($key)
    {
        $mailMessage = new MailMessage();
        $mailMessage->setTitle(sprintf(__('Clave Maestra %s'), AppInfoInterface::APP_NAME));
        $mailMessage->addDescription(__('Se ha generado una nueva clave para el acceso a sysPass y se solicitará en el siguiente inicio.'));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(sprintf(__('La nueva clave es: %s'), $key));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(sprintf(__('Esta clave estará activa hasta: %s'), date('r', $this->getMaxTime())));
        $mailMessage->addDescriptionLine();
        $mailMessage->addDescription(__('No olvide acceder lo antes posible para guardar los cambios.'));

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
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public function getUsingKey($key)
    {
        return Crypt::decrypt($this->configService->getByParam(self::PARAM_PASS),
            $this->configService->getByParam(self::PARAM_KEY),
            $key);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->configService = $this->dic->get(ConfigService::class);
    }
}