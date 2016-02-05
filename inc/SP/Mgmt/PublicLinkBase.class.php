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

namespace SP\Mgmt;

use SP\Config\Config;
use SP\Core\Crypt;
use SP\Core\SessionUtil;
use SP\Core\SPException;
use SP\Html\Html;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class PublicLinks para la gestión de enlaces públicos
 *
 * @package SP
 */
abstract class PublicLinkBase
{
    /**
     * @var int
     */
    protected $id = 0;
    /**
     * @var int
     */
    protected $itemId = 0;
    /**
     * @var int
     */
    protected $userId = 0;
    /**
     * @var string
     */
    protected $linkHash = '';
    /**
     * @var int
     */
    protected $typeId = 0;
    /**
     * @var bool
     */
    protected $notify = false;
    /**
     * @var int
     */
    protected $dateAdd = 0;
    /**
     * @var int
     */
    protected $dateExpire = 0;
    /**
     * @var string
     */
    protected $pass = '';
    /**
     * @var string
     */
    protected $passIV = '';
    /**
     * @var int
     */
    protected $countViews = 0;
    /**
     * @var int
     */
    protected $maxCountViews = 0;
    /**
     * @var array
     */
    private $useInfo = array();

    /**
     * @param int  $itemId El Id del elemento
     * @param int  $typeId El Id del tipo de link
     * @param bool $notify Si es necesario notificar
     */
    public function __construct($itemId, $typeId = 0, $notify = false)
    {
        $this->itemId = $itemId;
        $this->typeId = $typeId;
        $this->notify = $notify;
    }

    /**
     * @return int
     */
    public function getMaxCountViews()
    {
        return $this->maxCountViews;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getCountViews()
    {
        return $this->countViews;
    }

    /**
     * @return int
     */
    public function getDateExpire()
    {
        return $this->dateExpire;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @return string
     */
    public function getPassIV()
    {
        return $this->passIV;
    }

    /**
     * @return int
     */
    public function getDateAdd()
    {
        return $this->dateAdd;
    }

    /**
     * @return array
     */
    public function getUseInfo()
    {
        return $this->useInfo;
    }

    /**
     * @return boolean
     */
    public function isNotify()
    {
        return $this->notify;
    }

    /**
     * @param boolean $notify
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param int $typeId
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
    }

    /**
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @param int $itemId
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getLinkHash()
    {
        return $this->linkHash;
    }

    /**
     * Eliminar un enlace
     *
     * @throws SPException
     */
    public function deleteLink()
    {
        $query = 'DELETE FROM publicLinks WHERE  publicLink_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->id, 'id');

        try {
            DB::getQuery($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'), _('Revise el registro de eventos para más detalles'));
        }

        $Log = new Log(_('Eliminar Enlace'));
        $Log->addDescription(_('Enlace eliminado'));
        $Log->addDetails(Html::strongText(_('ID')), $this->itemId);
        $Log->writeLog();

        Email::sendEmail($Log);
    }

    /**
     * Crear un enlace público
     *
     * @throws SPException
     */
    protected function createLink()
    {
        $query = 'INSERT INTO publicLinks ' .
            'SET publicLink_hash = :hash, ' .
            'publicLink_itemId = :itemid, ' .
            'publicLink_linkData = :linkdata';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->createLinkHash(), 'hash');
        $Data->addParam($this->itemId, 'itemid');
        $Data->addParam(serialize($this), 'linkdata');

        try {
            DB::getQuery($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'), _('Revise el registro de eventos para más detalles'));
        }
    }

    /**
     * Generar el hash para el enlace
     *
     * @param bool $refresh Si es necesario regenerar el hash
     * @return string
     */
    protected function createLinkHash($refresh = false)
    {
        if (empty($this->linkHash) || $refresh === true) {
            $this->linkHash = hash('sha256', uniqid());
        }
        return $this->linkHash;
    }

    /**
     * Devolver la clave y el IV para el enlace
     *
     * @return array
     * @throws SPException
     */
    protected function createLinkPass()
    {
        $pass = Crypt::generateAesKey($this->createLinkHash());
        $cryptPass = Crypt::encryptData(SessionUtil::getSessionMPass(), $pass);

        $this->pass = $cryptPass['data'];
        $this->passIV = $cryptPass['iv'];
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     *
     * @return int
     */
    protected function calcDateExpire()
    {
        $this->dateExpire = time() + (int)Config::getConfig()->getPublinksMaxTime();
    }

    /**
     * Actualizar un enlace
     *
     * @return bool
     */
    protected function updateLink()
    {
        $query = 'UPDATE publicLinks ' .
            'SET publicLink_linkData = :linkdata, ' .
            'publicLink_hash = :hash ' .
            'WHERE publicLink_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->linkHash, 'hash');
        $Data->addParam($this->id, 'id');
        $Data->addParam(serialize($this), 'linkdata');

        return DB::getQuery($Data);
    }

    /**
     * Actualizar la información de uso
     *
     * @param string $who Quién lo ha visto
     */
    protected function updateUseInfo($who)
    {
        $info = array('who' => $who, 'time' => time());

        $this->setUseInfo($info);
    }

    /**
     * @param int $useInfo
     */
    private function setUseInfo($useInfo)
    {
        $this->useInfo[] = $useInfo;
    }
}