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

namespace SP\Auth;

use SP\Core\Init;
use SP\Core\Messages\LogMessage;
use SP\DataModel\UserData;
use SP\DataModel\UserPassRecoverData;
use SP\Html\Html;
use SP\Log\Email;
use SP\Mgmt\Users\UserPassRecover;
use SP\Util\Util;

/**
 * Class AuthUtil
 *
 * @package SP\Auth
 */
class AuthUtil
{
    /**
     * Proceso para la recuperación de clave.
     *
     * @param UserData $UserData
     * @return bool
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function mailPassRecover(UserData $UserData)
    {
        if (!$UserData->isUserIsDisabled()
            && !$UserData->isUserIsLdap()
            && !UserPassRecover::checkPassRecoverLimit($UserData)
        ) {
            $hash = Util::generateRandomBytes(16);

            $LogMessage = new LogMessage();
            $LogMessage->setAction(__('Cambio de Clave'));
            $LogMessage->addDescriptionHtml(__('Se ha solicitado el cambio de su clave de usuario.'));
            $LogMessage->addDescriptionLine();
            $LogMessage->addDescription(__('Para completar el proceso es necesario que acceda a la siguiente URL:'));
            $LogMessage->addDescriptionLine();
            $LogMessage->addDescription(Html::anchorText(Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time()));
            $LogMessage->addDescriptionLine();
            $LogMessage->addDescription(__('Si no ha solicitado esta acción, ignore este mensaje.'));

            $UserPassRecoverData = new UserPassRecoverData();
            $UserPassRecoverData->setUserpassrUserId($UserData->getUserId());
            $UserPassRecoverData->setUserpassrHash($hash);

            return (Email::sendEmail($LogMessage, $UserData->getUserEmail(), false) && UserPassRecover::getItem($UserPassRecoverData)->add());
        }

        return false;
    }

    /**
     * Devuelve el typo de autentificación del servidor web
     *
     * @return string
     */
    public static function getServerAuthType()
    {
        return strtoupper($_SERVER['AUTH_TYPE']);
    }
}