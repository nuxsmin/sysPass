<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Auth;

use Exts\Google2FA;
use Exts\Base2n;
use SP\Core\Exceptions\SPException;
use SP\Mgmt\Users\UserPass;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class Auth2FA
 *
 * @package SP\Auth
 */
class Auth2FA
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
     * @throws \InvalidArgumentException
     */
    public function __construct($userId, $userLogin = null)
    {
        $this->userId = $userId;
        $this->userLogin = $userLogin;
        $this->initializationKey = $this->genUserInitializationKey();
    }

    /**
     * Generar una clave de inicialización codificada en Base32
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    private function genUserInitializationKey()
    {
        $userIV = UserPass::getUserIVById($this->userId);
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

        error_log($totp . '/' . $userToken);

        return ($totp == $userToken);
    }
}