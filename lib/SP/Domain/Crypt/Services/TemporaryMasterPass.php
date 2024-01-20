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

namespace SP\Domain\Crypt\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Messages\MailMessage;
use SP\DataModel\Dto\ConfigRequest;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Crypt\Ports\TemporaryMasterPassService;
use SP\Domain\Notification\Ports\MailServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Util\PasswordUtil;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class TemporaryMasterPassService
 *
 * @package SP\Domain\Crypt\Services
 */
final class TemporaryMasterPass extends Service implements TemporaryMasterPassService
{
    /**
     * Número máximo de intentos
     */
    public const MAX_ATTEMPTS = 50;
    /**
     * Parámetros de configuración
     */
    private const PARAM_PASS     = 'tempmaster_pass';
    private const PARAM_KEY      = 'tempmaster_passkey';
    private const PARAM_HASH     = 'tempmaster_passhash';
    public const  PARAM_TIME     = 'tempmaster_passtime';
    public const  PARAM_MAX_TIME = 'tempmaster_maxtime';
    public const  PARAM_ATTEMPTS = 'tempmaster_attempts';

    private ?int $maxTime = null;

    public function __construct(
        Application                           $application,
        private readonly ConfigService        $configService,
        private readonly UserServiceInterface $userService,
        private readonly MailServiceInterface $mailService,
        private readonly CryptInterface       $crypt,
    ) {
        parent::__construct($application);
    }


    /**
     * Crea una clave temporal para encriptar la clave maestra y guardarla.
     *
     * @param int $maxTime El tiempo máximo de validez de la clave
     *
     * @return string
     * @throws ServiceException
     */
    public function create(int $maxTime = 14400): string
    {
        try {
            $this->maxTime = time() + $maxTime;

            // Encriptar la clave maestra con hash aleatorio generado
            $randomKey = PasswordUtil::generateRandomBytes(32);
            $secureKey = $this->crypt->makeSecuredKey($randomKey);

            $configRequest = new ConfigRequest();
            $configRequest->add(
                self::PARAM_PASS,
                $this->crypt->encrypt($this->getMasterKeyFromContext(), $secureKey, $randomKey)
            );
            $configRequest->add(self::PARAM_KEY, $secureKey);
            $configRequest->add(self::PARAM_HASH, Hash::hashKey($randomKey));
            $configRequest->add(self::PARAM_TIME, time());
            $configRequest->add(self::PARAM_MAX_TIME, $this->maxTime);
            $configRequest->add(self::PARAM_ATTEMPTS, 0);

            $this->configService->saveBatch($configRequest);

            // Guardar la clave temporal hasta que finalice la sesión
            $this->context->setTemporaryMasterPass($randomKey);

            $this->eventDispatcher->notify(
                'create.tempMasterPassword',
                new Event(
                    $this,
                    EventMessage::factory()->addDescription(__u('Generate temporary password'))
                )
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
    public function checkTempMasterPass(string $pass): bool
    {
        try {
            $passMaxTime = (int)$this->configService->getByParam(self::PARAM_MAX_TIME);

            // Comprobar si el tiempo de validez o los intentos se han superado
            if ($passMaxTime === 0) {
                $this->eventDispatcher->notify(
                    'check.tempMasterPassword',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Temporary password expired'))
                    )
                );

                return false;
            }

            $passTime = (int)$this->configService->getByParam(self::PARAM_TIME);
            $attempts = (int)$this->configService->getByParam(self::PARAM_ATTEMPTS);

            if ($attempts >= self::MAX_ATTEMPTS
                || (!empty($passTime) && time() > $passMaxTime)
            ) {
                $this->expire();

                return false;
            }

            $isValid = Hash::checkHashKey(
                $pass,
                $this->configService->getByParam(self::PARAM_HASH)
            );

            if (!$isValid) {
                $this->configService->save(
                    self::PARAM_ATTEMPTS,
                    $attempts + 1
                );
            }

            return $isValid;
        } catch (NoSuchItemException) {
            return false;
        } catch (Exception $e) {
            processException($e);

            throw new ServiceException(__u('Error while checking the temporary password'));
        }
    }

    /**
     * @throws ServiceException
     */
    protected function expire(): void
    {
        $configRequest = new ConfigRequest();
        $configRequest->add(self::PARAM_PASS, '');
        $configRequest->add(self::PARAM_KEY, '');
        $configRequest->add(self::PARAM_HASH, '');
        $configRequest->add(self::PARAM_TIME, 0);
        $configRequest->add(self::PARAM_MAX_TIME, 0);
        $configRequest->add(self::PARAM_ATTEMPTS, 0);

        $this->configService->saveBatch($configRequest);

        $this->eventDispatcher->notify(
            'expire.tempMasterPassword',
            new Event(
                $this,
                EventMessage::factory()->addDescription(__u('Temporary password expired'))
            )
        );
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendByEmailForGroup(int $groupId, string $key): void
    {
        $mailMessage = $this->getMessageForEmail($key);

        $emails = array_map(
            static function ($value) {
                return $value->email;
            },
            $this->userService->getUserEmailForGroup($groupId)
        );

        $this->mailService->sendBatch($mailMessage->getTitle(), $emails, $mailMessage);
    }

    private function getMessageForEmail(string $key): MailMessage
    {
        $mailMessage = new MailMessage();
        $mailMessage->setTitle(sprintf(__('%s Master Password'), AppInfoInterface::APP_NAME));
        $mailMessage->addDescription(
            __(
                'A new sysPass master password has been generated, so next time you log into the application it will be requested.'
            )
        );
        $mailMessage->addDescription(sprintf(__('The new Master Password is: %s'), $key));
        $mailMessage->addDescription(sprintf(__('This password will be valid until: %s'), date('r', $this->maxTime)));
        $mailMessage->addDescription(__('Please, don\'t forget to log in as soon as possible to save the changes.'));

        return $mailMessage;
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function sendByEmailForAllUsers(string $key): void
    {
        $mailMessage = $this->getMessageForEmail($key);

        $emails = array_map(
            static function ($value) {
                return $value->email;
            },
            $this->userService->getUserEmailForAll()
        );

        $this->mailService->sendBatch($mailMessage->getTitle(), $emails, $mailMessage);
    }

    /**
     * Devuelve la clave maestra que ha sido encriptada con la clave temporal
     *
     * @param $key string con la clave utilizada para encriptar
     *
     * @return string con la clave maestra desencriptada
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws CryptException
     */
    public function getUsingKey(string $key): string
    {
        return $this->crypt->decrypt(
            $this->configService->getByParam(self::PARAM_PASS),
            $this->configService->getByParam(self::PARAM_KEY),
            $key
        );
    }
}
