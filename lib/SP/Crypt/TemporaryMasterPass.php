<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Crypt;

use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\Dto\ConfigRequest;
use SP\Services\Config\ConfigService;
use SP\Core\Crypt\Session as CryptSession;
use SP\Services\ServiceException;
use SP\Util\Util;

/**
 * Class MasterPass
 *
 * @package SP\Crypt
 */
class TemporaryMasterPass
{
    /**
     * Número máximo de intentos
     */
    const MAX_ATTEMPTS = 50;
    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    use InjectableTrait;

    /**
     * MasterPass constructor.
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * Comprueba si la clave temporal es válida
     *
     * @param string $pass clave a comprobar
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function check($pass)
    {
        $passMaxTime = (int)$this->configService->getByParam('tempmaster_maxtime');

        // Comprobar si el tiempo de validez o los intentos se han superado
        if ($passMaxTime === 0 || time() > $passMaxTime) {
            $this->expire();

            return false;
        }

        $passTime = (int)$this->configService->getByParam('tempmaster_passtime');
        $attempts = (int)$this->configService->getByParam('tempmaster_attempts');

        if ($attempts >= self::MAX_ATTEMPTS
            || (!empty($passTime) && time() > $passMaxTime)
        ) {
            $this->expire();

            return false;
        }

        $isValid = Hash::checkHashKey($pass, $this->configService->getByParam('tempmaster_passhash'));

        if (!$isValid) {
            $this->configService->save('tempmaster_attempts', $attempts + 1);
        }

        return $isValid;
    }

    /**
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    private function expire()
    {
        $configRequest = new ConfigRequest();
        $configRequest->add('tempmaster_pass', '');
        $configRequest->add('tempmaster_passkey', '');
        $configRequest->add('tempmaster_passhash', '');
        $configRequest->add('tempmaster_maxtime', 0);
        $configRequest->add('tempmaster_attempts', 0);

        // Guardar la configuración
        $this->configService->saveBatch($configRequest);

        $this->eventDispatcher->notifyEvent('temporaryMasterPass.expired', $this);

        // Log::writeNewLog(__FUNCTION__, __u('Clave temporal caducada'), Log::INFO);
    }

    /**
     * Devuelve la clave maestra que ha sido encriptada con la clave temporal
     *
     * @param $key string con la clave utilizada para encriptar
     * @return string con la clave maestra desencriptada
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    public function getUsingKey($key)
    {
        $securedKey = Crypt::unlockSecuredKey($this->configService->getByParam('tempmaster_passkey'), $key);

        return Crypt::decrypt($this->configService->getByParam('tempmaster_pass'), $securedKey, $key);
    }

    /**
     * @param ConfigService   $configService
     * @param Session         $session
     * @param EventDispatcher $eventDispatcher
     */
    public function inject(ConfigService $configService, Session $session, EventDispatcher $eventDispatcher)
    {
        $this->configService = $configService;
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Crea una clave temporal para encriptar la clave maestra y guardarla.
     *
     * @param int $maxTime El tiempo máximo de validez de la clave
     * @return string
     * @throws ServiceException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function create($maxTime = 14400)
    {
        // Encriptar la clave maestra con hash aleatorio generado
        $randomKey = Util::generateRandomBytes(32);
        $securedKey = Crypt::makeSecuredKey($randomKey);

        $configRequest = new ConfigRequest();
        $configRequest->add('tempmaster_pass', Crypt::encrypt(CryptSession::getSessionKey(), $securedKey, $randomKey));
        $configRequest->add('tempmaster_passkey', $securedKey);
        $configRequest->add('tempmaster_passhash', Hash::hashKey($randomKey));
        $configRequest->add('tempmaster_passtime', time());
        $configRequest->add('tempmaster_maxtime', time() + $maxTime);
        $configRequest->add('tempmaster_attempts', 0);

        // Guardar la configuración
        $this->configService->saveBatch($configRequest);

        // Guardar la clave temporal hasta que finalice la sesión
        $this->session->setTemporaryMasterPass($randomKey);

        return $randomKey;
    }
}