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

use SP\Account\AccountUtil;
use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\DataModel\PublicLinkBaseData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemTrait;
use SP\Mgmt\Users\UserUtil;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Class PublicLink para la creación de enlaces públicos
 *
 * @package SP
 * @property PublicLinkBaseData $itemData
 */
class PublicLink extends PublicLinkBase implements ItemInterface
{
    use ItemTrait;

    /**
     * Tipos de enlaces
     */
    const TYPE_ACCOUNT = 1;

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @return bool
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addLinkView()
    {
        $this->itemData->addCountViews();
        $this->updateUseInfo(Util::getClientAddress(true));

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Ver Enlace Público', false));
        $LogMessage->addDescription(__('Enlace visualizado', false));
        $LogMessage->addDetails(__('Tipo', false), $this->itemData->getTypeId());
        $LogMessage->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($this->itemData->getItemId()));
        $LogMessage->addDetails(__('Usuario', false), UserUtil::getUserLoginById($this->itemData->getUserId()));
        $Log->writeLog();

        if ($this->itemData->isNotify()) {
            Email::sendEmail($LogMessage);
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
        $Data->setOnErrorMessage(__('Error al actualizar enlace', false));

        DB::getQuery($Data);

        return true;
    }

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_INFO, __('Enlace ya creado', false));
        }

        $this->itemData->setDateAdd(time());
        $this->itemData->setUserId(Session::getUserData()->getUserId());
        $this->itemData->setMaxCountViews(Config::getConfig()->getPublinksMaxViews());
        $this->calcDateExpire();
        $this->createLinkHash();
        $this->setLinkData();

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
        $Data->setOnErrorMessage(__('Error al crear enlace', false));

        DB::getQuery($Data);

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
     * @param $id int
     * @return $this
     * @throws SPException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM publicLinks WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar enlace', false));

        DB::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __('Enlace no encontrado', false));
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws SPException
     */
    public function refresh()
    {
        $this->itemData->setMaxCountViews($this->itemData->getMaxCountViews() + Config::getConfig()->getPublinksMaxViews());

        $this->calcDateExpire();
        $this->createLinkHash(true);
        $this->setLinkData();

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
        $Data->setOnErrorMessage(__('Error al renovar enlace', false));

        DB::getQuery($Data);

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

        /** @var PublicLinkBaseData $queryRes */
        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al obtener enlace', false));
        }

        /** @var $PublicLink PublicLinkData */
        $PublicLink = Util::castToClass($this->getDataModel(), $queryRes->getPublicLinkLinkData());
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

        /** @var PublicLinkData[] $queryRes */
        $queryRes = DB::getResultsArray($Data);

        $publicLinks = [];

        foreach ($queryRes as $PublicLinkListData) {
            /** @var PublicLinkData $PublicLinkData */
            $PublicLinkData = Util::castToClass($this->getDataModel(), $PublicLinkListData->getPublicLinkLinkData());
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
        $PublicLinkListData->setNotify($PublicLinkData->isNotify() ? __('ON') : __('OFF'));
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

        /** @var PublicLinkBaseData $queryRes */
        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al obtener enlace', false));
        } elseif (is_array($queryRes)) {
            return false;
        }

        /**
         * @var $PublicLink PublicLinkData
         */
        $PublicLink = Util::castToClass($this->getDataModel(), $queryRes->getPublicLinkLinkData());
        $PublicLink->setPublicLinkId($queryRes->getPublicLinkId());

        return $PublicLink;
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
            'SELECT publicLink_id, publicLink_hash FROM publicLinks WHERE publicLink_itemId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($itemId);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al obtener enlace', false));
        }

        return $queryRes;
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id,
            publicLink_hash
            FROM publicLinks WHERE publicLink_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DB::getResultsArray($Data);
    }
}