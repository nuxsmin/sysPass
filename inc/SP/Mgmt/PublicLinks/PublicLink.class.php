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

use SP\Account\AccountUtil;
use SP\Config\Config;
use SP\DataModel\PublicLinkBaseData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Html\Html;
use SP\Log\Email;
use SP\Log\Log;
use SP\Core\Session;
use SP\Core\Exceptions\SPException;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
use SP\Mgmt\Users\UserUtil;
use SP\Storage\QueryData;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class PublicLink para la creación de enlaces públicos
 *
 * @package SP
 */
class PublicLink extends PublicLinkBase implements ItemInterface
{
    /**
     * Tipos de enlaces
     */
    const TYPE_ACCOUNT = 1;

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addLinkView()
    {
        $this->itemData->addCountViews();
        $this->updateUseInfo($_SERVER['REMOTE_ADDR']);

        $Log = new Log(_('Ver Enlace Público'));
        $Log->addDescription(_('Enlace visualizado'));
        $Log->addDetails(Html::strongText(_('Tipo')), $this->itemData->getTypeId());
        $Log->addDetails(Html::strongText(_('Cuenta')), AccountUtil::getAccountNameById($this->itemData->getItemId()));
        $Log->addDetails(Html::strongText(_('Usuario')), UserUtil::getUserLoginById($this->itemData->getUserId()));
        $Log->writeLog();

        if ($this->itemData->isNotify()) {
            Email::sendEmail($Log);
        }

        return $this->update();
    }

    /**
     * @return bool
     * @throws SPException
     */
    public function update()
    {
        $query = /** @lang SQL */
            'UPDATE publicLinks
            SET publicLink_linkData = ?,
            publicLink_hash = ?
            WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(serialize($this->itemData));
        $Data->addParam($this->itemData->getLinkHash());
        $Data->addParam($this->itemData->getPublicLinkId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al actualizar enlace'));
        }

        return true;
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_INFO, _('Enlace ya creado'));
        }

        $this->itemData->setDateAdd(time());
        $this->itemData->setUserId(Session::getUserData()->getUserId());
        $this->itemData->setMaxCountViews(Config::getConfig()->getPublinksMaxViews());
        $this->calcDateExpire();
        $this->createLinkHash();
        $this->createLinkPass();

        $query = /** @lang SQL */
            'INSERT INTO publicLinks
            SET publicLink_hash = ?,
            publicLink_itemId = ?,
            publicLink_linkData = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getPublicLinkHash());
        $Data->addParam($this->itemData->getPublicLinkItemId());
        $Data->addParam(serialize($this->itemData));

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al crear enlace'));
        }

        $Log = new Log(_('Nuevo Enlace'));
        $Log->addDescription(_('Enlace creado'));
        $Log->addDetails(Html::strongText(_('Tipo')), $this->itemData->getTypeId());
        $Log->addDetails(Html::strongText(_('Cuenta')), AccountUtil::getAccountNameById($this->itemData->getItemId()));
        $Log->addDetails(Html::strongText(_('Usuario')), UserUtil::getUserLoginById($this->itemData->getUserId()));
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id FROM publicLinks WHERE publicLink_itemId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getPublicLinkItemId());

        DB::getResults($Data);

        return ($Data->getQueryNumRows() === 1);
    }

    /**
     * @param $id int|array
     * @return $this
     * @throws SPException
     */
    public function delete($id)
    {
        if (is_array($id)) {
            foreach ($id as $itemId){
                $this->delete($itemId);
            }

            return $this;
        }

        $query = /** @lang SQL */
            'DELETE FROM publicLinks WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al eliminar enlace'));
        }

        $Log = new Log(_('Eliminar Enlace'));
        $Log->addDescription(_('Enlace eliminado'));
        $Log->addDetails(Html::strongText(_('ID')), $this->itemData->getPublicLinkId());
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function refresh()
    {
        $this->itemData->setMaxCountViews($this->itemData->getMaxCountViews() + Config::getConfig()->getPublinksMaxViews());

        $this->calcDateExpire();
        $this->createLinkHash(true);
        $this->createLinkPass();

        $query = /** @lang SQL */
            'UPDATE publicLinks
            SET publicLink_linkData = ?,
            publicLink_hash = ?
            WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(serialize($this->itemData));
        $Data->addParam($this->itemData->getPublicLinkHash());
        $Data->addParam($this->itemData->getPublicLinkId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al renovar enlace'));
        }

        $Log = new Log(_('Actualizar Enlace'));
        $Log->addDescription(_('Enlace actualizado'));
        $Log->addDetails(Html::strongText(_('Tipo')), $this->itemData->getTypeId());
        $Log->addDetails(Html::strongText(_('Cuenta')), AccountUtil::getAccountNameById($this->itemData->getItemId()));
        $Log->addDetails(Html::strongText(_('Usuario')), UserUtil::getUserLoginById($this->itemData->getUserId()));
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @param $id int
     * @return PublicLinkData
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id,
            publicLink_hash,
            publicLink_linkData
            FROM publicLinks WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener enlace'));
        }

        /**
         * @var $queryRes   PublicLinkBaseData
         * @var $PublicLink PublicLinkData
         */
        $PublicLink = unserialize($queryRes->getPublicLinkLinkData());

        if (get_class($PublicLink) === '__PHP_Incomplete_Class') {
            $PublicLink = Util::castToClass($this->getDataModel(), $PublicLink);
        }

        $PublicLink->setPublicLinkId($id);

        return $PublicLink;
    }

    /**
     * @return mixed
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id, publicLink_hash, publicLink_linkData FROM publicLinks';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);

        $publicLinks = [];

        foreach (DB::getResultsArray($Data) as $PublicLinkListData) {
            /**
             * @var PublicLinkData     $PublicLinkData
             * @var PublicLinkListData $PublicLinkListData
             */

            $PublicLinkData = unserialize($PublicLinkListData->getPublicLinkLinkData());

            if (get_class($PublicLinkData) === '__PHP_Incomplete_Class') {
                $PublicLinkData = Util::castToClass($this->getDataModel(), $PublicLinkData);
            }

            $PublicLinkData->setPublicLinkId($PublicLinkListData->getPublicLinkId());

            $publicLinks[] = $this->getItemForList($PublicLinkData);
        }

        return $publicLinks;
    }

    /**
     * Devuelve los datos de un enlace para mostrarlo
     *
     * @param PublicLinkData $PublicLinkData
     * @return PublicLinkListData
     */
    public function getItemForList(PublicLinkData $PublicLinkData)
    {
        $PublicLinkListData = new PublicLinkListData();
        $PublicLinkListData->setPublicLinkId($PublicLinkData->getPublicLinkId());
        $PublicLinkListData->setPublicLinkHash($PublicLinkData->getLinkHash());
        $PublicLinkListData->setAccountName(AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
        $PublicLinkListData->setUserLogin(UserUtil::getUserLoginById($PublicLinkData->getUserId()));
        $PublicLinkListData->setNotify($PublicLinkData->isNotify() ? _('ON') : _('OFF'));
        $PublicLinkListData->setDateAdd(date('Y-m-d H:i', $PublicLinkData->getDateAdd()));
        $PublicLinkListData->setDateExpire(date('Y-m-d H:i', $PublicLinkData->getDateExpire()));
        $PublicLinkListData->setCountViews($PublicLinkData->getCountViews() . '/' . $PublicLinkData->getMaxCountViews());
        $PublicLinkListData->setUseInfo($PublicLinkData->getUseInfo());

        return $PublicLinkListData;
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * @param $hash int
     * @return bool|PublicLinkData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getByHash($hash)
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id,
            publicLink_hash,
            publicLink_linkData
            FROM publicLinks WHERE publicLink_hash = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($hash);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener enlace'));
        } elseif (count($queryRes) > 0) {
            /**
             * @var $queryRes   PublicLinkBaseData
             * @var $PublicLink PublicLinkData
             */
            $PublicLink = unserialize($queryRes->getPublicLinkLinkData());

            if (get_class($PublicLink) === '__PHP_Incomplete_Class') {
                $PublicLink = Util::castToClass($this->getDataModel(), $PublicLink);
            }

            $PublicLink->setPublicLinkId($queryRes->getPublicLinkId());

            return $PublicLink;
        }

        return false;
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     * @return PublicLinkData
     * @throws SPException
     */
    public function getHashForItem($itemId)
    {
        $query = /** @lang SQL */
            'SELECT publicLink_hash FROM publicLinks WHERE publicLink_itemId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($itemId);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener enlace'));
        }

        return $queryRes;
    }
}