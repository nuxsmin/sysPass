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

namespace SP;

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
    protected $_id = 0;
    /**
     * @var int
     */
    protected $_itemId = 0;
    /**
     * @var int
     */
    protected $_userId = 0;
    /**
     * @var string
     */
    protected $_linkHash = '';
    /**
     * @var int
     */
    protected $_typeId = 0;
    /**
     * @var bool
     */
    protected $_notify = false;
    /**
     * @var int
     */
    protected $_dateAdd = 0;
    /**
     * @var int
     */
    protected $_dateExpire = 0;
    /**
     * @var string
     */
    protected $_pass = '';
    /**
     * @var string
     */
    protected $_passIV = '';
    /**
     * @var int
     */
    protected $_countViews = 0;
    /**
     * @var int
     */
    protected $_maxCountViews = 0;
    /**
     * @var array
     */
    private $_useInfo = array();

    /**
     * @param int  $itemId El Id del elemento
     * @param int  $typeId El Id del tipo de link
     * @param bool $notify Si es necesario notificar
     */
    public function __construct($itemId, $typeId = 0, $notify = false)
    {
        $this->_itemId = $itemId;
        $this->_typeId = $typeId;
        $this->_notify = $notify;
    }

    /**
     * @return int
     */
    public function getMaxCountViews()
    {
        return $this->_maxCountViews;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return int
     */
    public function getCountViews()
    {
        return $this->_countViews;
    }

    /**
     * @return int
     */
    public function getDateExpire()
    {
        return $this->_dateExpire;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->_pass;
    }

    /**
     * @return string
     */
    public function getPassIV()
    {
        return $this->_passIV;
    }

    /**
     * @return int
     */
    public function getDateAdd()
    {
        return $this->_dateAdd;
    }

    /**
     * @return array
     */
    public function getUseInfo()
    {
        return $this->_useInfo;
    }

    /**
     * @return boolean
     */
    public function isNotify()
    {
        return $this->_notify;
    }

    /**
     * @param boolean $notify
     */
    public function setNotify($notify)
    {
        $this->_notify = $notify;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->_typeId;
    }

    /**
     * @param int $typeId
     */
    public function setTypeId($typeId)
    {
        $this->_typeId = $typeId;
    }

    /**
     * @return int
     */
    public function getItemId()
    {
        return $this->_itemId;
    }

    /**
     * @param int $itemId
     */
    public function setItemId($itemId)
    {
        $this->_itemId = $itemId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }

    /**
     * @return string
     */
    public function getLinkHash()
    {
        return $this->_linkHash;
    }

    /**
     * Eliminar un enlace
     *
     * @throws SPException
     */
    public function deleteLink()
    {
        $query = 'DELETE FROM publicLinks WHERE  publicLink_id = :id LIMIT 1';

        $data['id'] = $this->_itemId;

        try {
            DB::getQuery($query, __FUNCTION__, $data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'), _('Revise el registro de eventos para más detalles'));
        }
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

        $data['hash'] = $this->createLinkHash();
        $data['itemid'] = $this->_itemId;
        $data['linkdata'] = serialize($this);

        try {
            DB::getQuery($query, __FUNCTION__, $data);
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
        if (empty($this->_linkHash) || $refresh === true) {
            $this->_linkHash = hash('sha256', uniqid());
        }
        return $this->_linkHash;
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

        $this->_pass = $cryptPass['data'];
        $this->_passIV = $cryptPass['iv'];
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     *
     * @return int
     */
    protected function calcDateExpire()
    {
        $this->_dateExpire = time() + (int)Config::getValue('publinks_maxtime', 600);
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

        $data['linkdata'] = serialize($this);
        $data['hash'] = $this->_linkHash;
        $data['id'] = $this->_id;

        return DB::getQuery($query, __FUNCTION__, $data);
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
        $this->_useInfo[] = $useInfo;
    }
}