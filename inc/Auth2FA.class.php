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

use SP\UserUtil;

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
    private $_timestamp = 0;
    /**
     * @var string
     */
    private $_initializationKey = '';
    /**
     * @var string
     */
    private $_totp = '';
    /**
     * @var int
     */
    private $_userId = 0;
    /**
     * @var string
     */
    private $_userLogin = '';

    public function __construct($userId, $userLogin = null)
    {
        $this->_userId = $userId;
        $this->_userLogin = $userLogin;
        $this->_initializationKey = $this->genUserInitializationKey();
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }

    /**
     * @param string $userLogin
     */
    public function setUserLogin($userLogin)
    {
        $this->_userLogin = $userLogin;
    }

    /**
     * Verificar el código de 2FA
     *
     * @param $key
     * @return bool
     */
    public function verifyKey($key)
    {
        return Google2FA::verify_key($this->_initializationKey, $key);
    }

    public function getUserQRUrl(){
        $qrUrl = 'https://www.google.com/chart?chs=150x150&chld=M|0&cht=qr&chl=';
        $qrUrl .= urlencode('otpauth://totp/sysPass:syspass/' . $this->_userLogin . '?secret=' . $this->_initializationKey . '&issuer=sysPass');

        return $qrUrl;
    }

    public function getUserQRCode()
    {
        $ch = curl_init($this->getUserQRUrl());

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "sysPass 2FA");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $data = curl_exec($ch);
        curl_close($ch);

        if ($data === false) {
            return false;
        }

        return base64_encode($data);
    }

    public function checkUserToken($userToken)
    {
        $timeStamp = Google2FA::get_timestamp();
        $secretkey = Google2FA::base32_decode($this->_initializationKey);
        $totp = Google2FA::oath_totp($secretkey, $timeStamp);

        error_log($totp . '/' . $userToken);

        return ($totp == $userToken);
    }

    private function genUserInitializationKey()
    {
        $userIV = UserUtil::getUserIVById($this->_userId);
        $base32 = new \Base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', false, true, true);
        $key = substr($base32->encode($userIV), 0, 16);

        return $key;
    }

}