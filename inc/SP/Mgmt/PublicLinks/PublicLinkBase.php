<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Mgmt\PublicLinks;

defined('APP_ROOT') || die();

use SP\Account\Account;
use SP\Config\Config;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\PublicLinkBaseData;
use SP\Mgmt\ItemBaseInterface;
use SP\Mgmt\ItemBaseTrait;

/**
 * Class PublicLinks para la gestión de enlaces públicos
 *
 * @package SP
 * @property PublicLinkBaseData $itemData
 */
abstract class PublicLinkBase implements ItemBaseInterface
{
    use ItemBaseTrait;

    /**
     * Inicializar la clase
     *
     * @return void
     * @throws InvalidClassException
     */
    protected function init()
    {
        $this->setDataModel(PublicLinkBaseData::class);
    }

    /**
     * Devolver la clave y el IV para el enlace
     *
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    protected final function createLinkPass()
    {
        $key = Config::getConfig()->getPasswordSalt() . $this->createLinkHash();
        $securedKey = Crypt::makeSecuredKey($key);

        $this->itemData->setPass(Crypt::encrypt(CryptSession::getSessionKey(), $securedKey, $key));
        $this->itemData->setPassIV($securedKey);
    }

    /**
     * Generar el hash para el enlace
     *
     * @param bool $refresh Si es necesario regenerar el hash
     * @return string
     */
    protected final function createLinkHash($refresh = false)
    {
        if ($refresh === true
            || $this->itemData->getLinkHash() === ''
        ) {
            $hash = hash('sha256', uniqid('sysPassPublicLink', true));

            $this->itemData->setPublicLinkHash($hash);
            $this->itemData->setLinkHash($hash);
        }

        return $this->itemData->getLinkHash();
    }

    /**
     * Obtener los datos de una cuenta y encriptarlos para el enlace
     *
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    protected final function setLinkData()
    {
        // Obtener los datos de la cuenta
        $Account = new Account(new AccountExtData($this->itemData->getItemId()));
        $AccountData = $Account->getDataForLink();

        $key = CryptSession::getSessionKey();
        $securedKey = Crypt::unlockSecuredKey($AccountData->getAccountKey(), $key);
        $AccountData->setAccountPass(Crypt::decrypt($AccountData->getAccountPass(), $securedKey, $key));
        $AccountData->setAccountKey(null);

        // Encriptar los datos de la cuenta
        $linkKey = Config::getConfig()->getPasswordSalt() . $this->createLinkHash();
        $linkSecuredKey = Crypt::makeSecuredKey($linkKey);

        $this->itemData->setData(Crypt::encrypt(serialize($AccountData), $linkSecuredKey, $linkKey));
        $this->itemData->setPassIV($linkSecuredKey);
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     */
    protected final function calcDateExpire()
    {
        $this->itemData->setDateExpire(time() + (int)Config::getConfig()->getPublinksMaxTime());
    }

    /**
     * Actualizar la información de uso
     *
     * @param string $who Quién lo ha visto
     */
    protected final function updateUseInfo($who)
    {
        $this->itemData->addUseInfo(['who' => $who, 'time' => time()]);
    }
}