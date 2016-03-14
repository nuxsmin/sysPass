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

namespace SP\Mgmt\PublicLinks;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Config\Config;
use SP\Core\Crypt;
use SP\Core\SessionUtil;
use SP\Core\SPException;
use SP\DataModel\PublicLinkData;

/**
 * Class PublicLinks para la gestión de enlaces públicos
 *
 * @package SP
 */
abstract class PublicLinkBase
{
    /** @var PublicLinkData */
    protected $itemData;

    /**
     * Category constructor.
     *
     * @param PublicLinkData $itemData
     */
    public function __construct(PublicLinkData $itemData = null)
    {
        $this->itemData = (!is_null($itemData)) ? $itemData : new PublicLinkData();
    }

    /**
     * @param PublicLinkData $itemData
     * @return static
     */
    public static function getItem($itemData = null)
    {
        return new static($itemData);
    }

    /**
     * @return PublicLinkData
     */
    public function getItemData()
    {
        return $this->itemData;
    }

    /**
     * @param PublicLinkData $itemData
     * @return $this
     */
    public function setItemData($itemData)
    {
        $this->itemData = $itemData;
        return $this;
    }

    /**
     * Devolver la clave y el IV para el enlace
     *
     * @throws SPException
     */
    protected function createLinkPass()
    {
        $pass = Crypt::generateAesKey($this->createLinkHash());
        $cryptPass = Crypt::encryptData(SessionUtil::getSessionMPass(), $pass);

        $this->itemData->setPass($cryptPass['data']);
        $this->itemData->setPassIV($cryptPass['iv']);
    }

    /**
     * Generar el hash para el enlace
     *
     * @param bool $refresh Si es necesario regenerar el hash
     * @return string
     */
    protected function createLinkHash($refresh = false)
    {
        if ($this->itemData->getLinkHash() === ''
            || $refresh === true
        ) {
            $this->itemData->setLinkHash(hash('sha256', uniqid()));
        }

        return $this->itemData->getLinkHash();
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     *
     * @return int
     */
    protected function calcDateExpire()
    {
        $this->itemData->setDateExpire(time() + (int)Config::getConfig()->getPublinksMaxTime());
    }

    /**
     * Actualizar la información de uso
     *
     * @param string $who Quién lo ha visto
     */
    protected function updateUseInfo($who)
    {
        $this->itemData->addUseInfo(['who' => $who, 'time' => time()]);
    }
}