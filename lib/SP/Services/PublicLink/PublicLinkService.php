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

namespace SP\Services\PublicLink;

use SP\Account\AccountUtil;
use SP\Bootstrap;
use SP\Config\Config;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkBaseData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Http\Request;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Users\UserUtil;
use SP\Services\Account\AccountService;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\DateUtil;
use SP\Util\HttpUtil;
use SP\Util\Util;

/**
 * Class PublicLinkService
 *
 * @package SP\Services\PublicLink
 */
class PublicLinkService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

    /**
     * Returns an HTTP URL for given hash
     *
     * @param $hash
     * @return string
     */
    public static function getLinkForHash($hash)
    {
        return Bootstrap::$WEBURI . '/index.php?r=account/viewLink/' . $hash;
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM publicLinks WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar enlace'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Enlace no encontrado'));
        }

        return $this;
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id, publicLink_hash, publicLink_linkData FROM publicLinks';

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkBaseData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id,
            publicLink_hash
            FROM publicLinks WHERE publicLink_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkBaseData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'DELETE FROM publicLinks WHERE publicLink_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return bool
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     * @throws InvalidClassException
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkBaseData::class);
        $Data->setSelect('publicLink_id, publicLink_hash, publicLink_linkData');
        $Data->setFrom('publicLinks');
        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        /** @var PublicLinkListData[] $queryRes */
        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $filters = [
            ['method' => 'getAccountName', 'text' => $SearchData->getSeachString()],
            ['method' => 'getUserLogin', 'text' => $SearchData->getSeachString()]
        ];

        $items = self::mapItemsForList($queryRes, $filters);
        $items['count'] = $Data->getQueryNumRows();

        /*
        $publicLinks = [];
        $publicLinks['count'] = $Data->getQueryNumRows();

        foreach ($queryRes as $PublicLinkListData) {
                    $PublicLinkData = Util::castToClass(PublicLinkBaseData::class, $PublicLinkListData->getPublicLinkLinkData());

                    $PublicLinkListData->setAccountName(AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
                    $PublicLinkListData->setUserLogin(UserUtil::getUserLoginById($PublicLinkData->getUserId()));
                    $PublicLinkListData->setNotify(__($PublicLinkData->isNotify() ? 'ON' : 'OFF'));
                    $PublicLinkListData->setDateAdd(date('Y-m-d H:i', $PublicLinkData->getDateAdd()));
                    $PublicLinkListData->setDateExpire(date('Y-m-d H:i', $PublicLinkData->getDateExpire()));
                    $PublicLinkListData->setCountViews($PublicLinkData->getCountViews() . '/' . $PublicLinkData->getMaxCountViews());
                    $PublicLinkListData->setUseInfo($PublicLinkData->getUseInfo());

                    if ($SearchData->getSeachString() === ''
                        || mb_stripos($PublicLinkListData->getAccountName(), $SearchData->getSeachString()) !== false
                        || mb_stripos($PublicLinkListData->getUserLogin(), $SearchData->getSeachString()) !== false
                    ) {
                        $publicLinks[] = $PublicLinkListData;
                    }
                }
        */

        return $items;
    }

    /**
     * Devuelve los datos de un enlace para mostrarlo
     *
     * @param array $data
     * @param array $filters Array of ['method' => <string>, 'text' => <string>]
     * @return PublicLinkListData[]
     * @throws InvalidClassException
     */
    public static function mapItemsForList(array $data, array $filters = null)
    {
        $items = [];

        $publicLinkListData = new PublicLinkListData();

        foreach ($data as $publicLink) {
            if (!$publicLink instanceof PublicLinkBaseData) {
                throw new InvalidClassException(SPException::SP_ERROR, __u('Error interno'));
            }

            /** @var PublicLinkData $publicLinkData */
            $publicLinkData = Util::unserialize(PublicLinkData::class, $publicLink->getPublicLinkLinkData());

            if ($filters !== null) {
                foreach ($filters as $filter) {
                    if ($filter['text'] !== ''
                        && method_exists($publicLinkData, $filter['method'])
                        && mb_stripos($publicLinkData->{$filter['method']}(), $filter['text']) === false
                    ) {
                        continue 2;
                    }
                }
            }

            $publicLinkData->setPublicLinkId($publicLink->getPublicLinkId());

            $item = clone $publicLinkListData;
            $item->setPublicLinkLinkData($publicLinkData);
            $item->setPublicLinkId($publicLinkData->getPublicLinkId());
            $item->setPublicLinkItemId($publicLinkData->getPublicLinkItemId());
            $item->setPublicLinkHash($publicLinkData->getLinkHash());
            $item->setAccountName(AccountUtil::getAccountNameById($publicLinkData->getItemId()));
            $item->setUserLogin(UserUtil::getUserLoginById($publicLinkData->getUserId()));
            $item->setNotify($publicLinkData->isNotify() ? __u('ON') : __u('OFF'));
            $item->setDateAdd(DateUtil::getDateFromUnix($publicLinkData->getDateAdd()));
            $item->setDateExpire(DateUtil::getDateFromUnix($publicLinkData->getDateExpire()));
            $item->setCountViews(sprintf('%d/%d/%d', $publicLinkData->getCountViews(), $publicLinkData->getMaxCountViews(), $publicLinkData->getTotalCountViews()));
            $item->setUseInfo($publicLinkData->getUseInfo());

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Creates an item
     *
     * @param PublicLinkData $itemData
     * @return int
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_INFO, __u('Enlace ya creado'));
        }

        $itemData->setDateAdd(time());
        $itemData->setUserId($this->session->getUserData()->getUserId());
        $itemData->setMaxCountViews($this->config->getConfigData()->getPublinksMaxViews());

        self::calcDateExpire($itemData, $this->config);
        self::createLinkHash($itemData);
        self::setLinkData($itemData, $this->config);

        $query = /** @lang SQL */
            'INSERT INTO publicLinks
            SET publicLink_hash = ?,
            publicLink_itemId = ?,
            publicLink_linkData = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getPublicLinkHash());
        $Data->addParam($itemData->getPublicLinkItemId());
        $Data->addParam(serialize($itemData));
        $Data->setOnErrorMessage(__u('Error al crear enlace'));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return bool
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id FROM publicLinks WHERE publicLink_itemId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getPublicLinkItemId());

        DbWrapper::getResults($Data, $this->db);

        return ($Data->getQueryNumRows() === 1);
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     *
     * @param PublicLinkData $publicLinkData
     * @param Config         $config
     */
    protected static function calcDateExpire(PublicLinkData $publicLinkData, Config $config)
    {
        $publicLinkData->setDateExpire(time() + $config->getConfigData()->getPublinksMaxTime());
    }

    /**
     * Generar el hash para el enlace
     *
     * @param PublicLinkData $publicLinkData
     * @param bool           $refresh Si es necesario regenerar el hash
     * @return string
     */
    protected static function createLinkHash(PublicLinkData $publicLinkData, $refresh = false)
    {
        if ($refresh === true
            || $publicLinkData->getLinkHash() === ''
        ) {
            $hash = hash('sha256', uniqid('sysPassPublicLink', true));

            $publicLinkData->setPublicLinkHash($hash);
            $publicLinkData->setLinkHash($hash);
        }

        return $publicLinkData->getLinkHash();
    }

    /**
     * Obtener los datos de una cuenta y encriptarlos para el enlace
     *
     * @param PublicLinkData $publicLinkData
     * @param Config         $config
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    protected static function setLinkData(PublicLinkData $publicLinkData, Config $config)
    {
        // Obtener los datos de la cuenta
        $accountService = new AccountService();
        $accountData = $accountService->getDataForLink($publicLinkData->getItemId());

        $key = CryptSession::getSessionKey();
        $securedKey = Crypt::unlockSecuredKey($accountData->getAccountKey(), $key);
        $accountData->setAccountPass(Crypt::decrypt($accountData->getAccountPass(), $securedKey, $key));
        $accountData->setAccountKey(null);

        // Encriptar los datos de la cuenta
        $linkKey = $config->getConfigData()->getPasswordSalt() . self::createLinkHash($publicLinkData);
        $linkSecuredKey = Crypt::makeSecuredKey($linkKey);

        $publicLinkData->setData(Crypt::encrypt(serialize($accountData), $linkSecuredKey, $linkKey));
        $publicLinkData->setPassIV($linkSecuredKey);
    }

    /**
     * Obtener los datos de una cuenta y encriptarlos para el enlace
     *
     * @param PublicLinkData $publicLinkData
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function getLinkData(PublicLinkData $publicLinkData)
    {
        // Obtener los datos de la cuenta
        $accountService = new AccountService();
        $accountData = $accountService->getDataForLink($publicLinkData->getItemId());

        $key = CryptSession::getSessionKey();
        $securedKey = Crypt::unlockSecuredKey($accountData->getAccountKey(), $key);
        $accountData->setAccountPass(Crypt::decrypt($accountData->getAccountPass(), $securedKey, $key));
        $accountData->setAccountKey(null);

        // Encriptar los datos de la cuenta
        $linkKey = $this->config->getConfigData()->getPasswordSalt() . self::createLinkHash($publicLinkData);
        $linkSecuredKey = Crypt::makeSecuredKey($linkKey);

        $publicLinkData->setData(Crypt::encrypt(serialize($accountData), $linkSecuredKey, $linkKey));
        $publicLinkData->setPassIV($linkSecuredKey);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     * @return bool
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param PublicLinkData $publicLinkData
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData)
    {
        $publicLinkData->addUseInfo(self::getUseInfo($publicLinkData));
        $publicLinkData->addCountViews();
        $publicLinkData->addTotalCountViews();

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__u('Ver Enlace Público'));
        $LogMessage->addDescription(__u('Enlace visualizado'));
        $LogMessage->addDetails(__u('Tipo'), $publicLinkData->getTypeId());
        $LogMessage->addDetails(__u('Cuenta'), AccountUtil::getAccountNameById($publicLinkData->getItemId()));
        $LogMessage->addDetails(__u('Usuario'), UserUtil::getUserLoginById($publicLinkData->getUserId()));
        $Log->writeLog();

        if ($publicLinkData->isNotify()) {
            Email::sendEmail($LogMessage);
        }

        return $this->update($publicLinkData);
    }

    /**
     * Actualizar la información de uso
     *
     * @param PublicLinkData $publicLinkData
     * @return array
     */
    protected static function getUseInfo(PublicLinkData $publicLinkData)
    {
        return [
            'who' => HttpUtil::getClientAddress(true),
            'time' => time(),
            'hash' => $publicLinkData->getLinkHash(),
            'agent' => Request::getRequestHeaders('HTTP_USER_AGENT'),
            'https' => Checks::httpsEnabled()
        ];
    }

    /**
     * Updates an item
     *
     * @param PublicLinkData $itemData
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $query = /** @lang SQL */
            'UPDATE publicLinks
            SET publicLink_linkData = ?,
            publicLink_hash = ?
            WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(serialize($itemData));
        $Data->addParam($itemData->getLinkHash());
        $Data->addParam($itemData->getPublicLinkId());
        $Data->setOnErrorMessage(__u('Error al actualizar enlace'));

        DbWrapper::getQuery($Data, $this->db);

        return true;
    }

    /**
     * Refreshes a public link
     *
     * @param $id
     * @return $this
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refresh($id)
    {
        /** @var PublicLinkData $publicLinkData */
        $publicLinkData = Util::unserialize(PublicLinkData::class, $this->getById($id)->getPublicLinkLinkData());
        $publicLinkData->setCountViews(0);
        $publicLinkData->setMaxCountViews($this->config->getConfigData()->getPublinksMaxViews());

        self::calcDateExpire($publicLinkData, $this->config);
        self::createLinkHash($publicLinkData, true);
        self::setLinkData($publicLinkData, $this->config);

        $query = /** @lang SQL */
            'UPDATE publicLinks
            SET publicLink_linkData = ?,
            publicLink_hash = ?
            WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(serialize($publicLinkData));
        $Data->addParam($publicLinkData->getPublicLinkHash());
        $Data->addParam($publicLinkData->getPublicLinkId());
        $Data->setOnErrorMessage(__u('Error al renovar enlace'));

        DbWrapper::getQuery($Data, $this->db);

        return $this;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return PublicLinkBaseData
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
        $Data->setMapClassName(PublicLinkBaseData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener enlace'));
        }

        return $queryRes;
    }

    /**
     * @param $hash string
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
        $Data->setMapClassName(PublicLinkBaseData::class);
        $Data->setQuery($query);
        $Data->addParam($hash);

        /** @var PublicLinkBaseData $queryRes */
        $queryRes = DbWrapper::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener enlace'));
        }

        /**
         * @var $publicLinkData PublicLinkData
         */
        $publicLinkData = Util::unserialize(PublicLinkData::class, $queryRes->getPublicLinkLinkData());
        $publicLinkData->setPublicLinkId($queryRes->getPublicLinkId());

        return $publicLinkData;
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
        $Data->setMapClassName(PublicLinkBaseData::class);
        $Data->setQuery($query);
        $Data->addParam($itemId);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener enlace'));
        }

        return $queryRes;
    }

    /**
     * Devolver la clave y el IV para el enlace
     *
     * @param PublicLinkData $publicLinkData
     * @param Config         $config
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    protected function createLinkPass(PublicLinkData $publicLinkData, Config $config)
    {
        $key = $config->getConfigData()->getPasswordSalt() . self::createLinkHash($publicLinkData);
        $securedKey = Crypt::makeSecuredKey($key);

        $publicLinkData->setPass(Crypt::encrypt(CryptSession::getSessionKey(), $securedKey, $key));
        $publicLinkData->setPassIV($securedKey);
    }
}