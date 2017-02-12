<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace Plugins\Authenticator;

use Exts\Base2n;
use SP\Core\Exceptions\SPException;
use SP\Mgmt\Users\UserPass;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Class Auth2FA
 *
 * @package SP\Auth
 */
class Authenticator
{
    /**
     * @var int
     */
    private $timestamp = 0;
    /**
     * @var string
     */
    private $initializationKey = '';
    /**
     * @var string
     */
    private $totp = '';
    /**
     * @var int
     */
    private $userId = 0;
    /**
     * @var string
     */
    private $userLogin = '';

    /**
     * @param int    $userId    El Id de usuario
     * @param string $userLogin El login de usuario
     * @param string $IV
     * @throws \InvalidArgumentException
     */
    public function __construct($userId, $userLogin = null, $IV = null)
    {
        $this->userId = $userId;
        $this->userLogin = $userLogin;
        $this->initializationKey = $this->genUserInitializationKey($IV);
    }

    /**
     * Generar una clave de inicialización codificada en Base32
     *
     * @param string $IV
     * @return string
     * @throws \InvalidArgumentException
     */
    private function genUserInitializationKey($IV = null)
    {
        $userIV = $IV === null ? UserPass::getUserIVById($this->userId) : $IV;
        $base32 = new Base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', false, true, true);

        return substr($base32->encode($userIV), 0, 16);
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @param string $userLogin
     */
    public function setUserLogin($userLogin)
    {
        $this->userLogin = $userLogin;
    }

    /**
     * Verificar el código de 2FA
     *
     * @param $key
     * @return bool
     * @throws \Exception
     */
    public function verifyKey($key)
    {
        return Google2FA::verify_key($this->initializationKey, $key);
    }

    /**
     * Devolver el código QR de la peticíón HTTP en base64
     *
     * @return bool|string
     */
    public function getUserQRCode()
    {
        try {
            $data = Util::getDataFromUrl($this->getUserQRUrl());
            return base64_encode($data);
        } catch (SPException $e) {
            return false;
        }
    }

    /**
     * Devolver la cadena con la URL para solicitar el código QR
     *
     * @return string
     */
    public function getUserQRUrl()
    {
        $qrUrl = 'https://www.google.com/chart?chs=150x150&chld=M|0&cht=qr&chl=';
        $qrUrl .= urlencode('otpauth://totp/sysPass:syspass/' . $this->userLogin . '?secret=' . $this->initializationKey . '&issuer=sysPass');

        return $qrUrl;
    }

    /**
     * Comprobar el token del usuario
     *
     * @param int $userToken EL código del usuario
     * @return bool
     * @throws \Exception
     */
    public function checkUserToken($userToken)
    {
        $timeStamp = Google2FA::get_timestamp();
        $secretkey = Google2FA::base32_decode($this->initializationKey);
        $totp = Google2FA::oath_totp($secretkey, $timeStamp);

        return ($totp === $userToken);
    }

    /**
     * @return string
     */
    public function getInitializationKey()
    {
        return $this->initializationKey;
    }
}