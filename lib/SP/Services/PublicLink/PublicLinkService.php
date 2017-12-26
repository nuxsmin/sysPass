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
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
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
     * Generar el hash para el enlace
     *
     * @return string
     */
    protected static function createLinkHash()
    {
        return hash('sha256', uniqid('sysPassPublicLink', true));
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
     * @return PublicLinkData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id, 
              publicLink_itemId,
              publicLink_hash,
              publicLink_data,
              publicLink_userId,
              publicLink_typeId,
              publicLink_notify,
              publicLink_dateAdd,
              publicLink_dateExpire,
              publicLink_countViews,
              publicLink_maxCountViews,
              publicLink_totalCountViews,
              publicLink_useInfo,
              user_name,
              user_login,
              account_name        
              FROM publicLinks
              INNER JOIN usrData ON user_id = publicLink_userId
              INNER JOIN accounts ON account_id = publicLink_itemId';

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkListData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return PublicLinkData[]
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id, 
              publicLink_itemId,
              publicLink_hash,
              publicLink_data,
              publicLink_userId,
              publicLink_typeId,
              publicLink_notify,
              publicLink_dateAdd,
              publicLink_dateExpire,
              publicLink_countViews,
              publicLink_maxCountViews,
              publicLink_totalCountViews,
              publicLink_useInfo,
              user_name,
              user_login,
              account_name                
              FROM publicLinks
              INNER JOIN usrData ON user_id = publicLink_userId
              INNER JOIN accounts ON account_id = publicLink_itemId
              WHERE publicLink_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkListData::class);
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
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkListData::class);
        $Data->setSelect('publicLink_id, 
              publicLink_itemId,
              publicLink_hash,
              publicLink_data,
              publicLink_userId,
              publicLink_typeId,
              publicLink_notify,
              publicLink_dateAdd,
              publicLink_dateExpire,
              publicLink_countViews,
              publicLink_maxCountViews,
              publicLink_totalCountViews,
              publicLink_useInfo,
              user_name,
              user_login,
              account_name');
        $Data->setFrom('publicLinks INNER JOIN usrData ON user_id = publicLink_userId INNER JOIN accounts ON account_id = publicLink_itemId');
        $Data->setOrder('publicLink_dateExpire DESC');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('user_login LIKE ? OR account_name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Creates an item
     *
     * @param PublicLinkData $itemData
     * @return int
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_INFO, __u('Enlace ya creado'));
        }

        $query = /** @lang SQL */
            'INSERT INTO publicLinks
            SET publicLink_itemId = ?,
            publicLink_hash = ?,
            publicLink_data = ?,
            publicLink_userId = ?,
            publicLink_typeId = ?,
            publicLink_notify = ?,
            publicLink_dateAdd = UNIX_TIMESTAMP(),
            publicLink_dateExpire = ?,
            publicLink_maxCountViews = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getPublicLinkItemId());
        $Data->addParam($itemData->getPublicLinkHash());
        $Data->addParam($this->getSecuredLinkData($itemData->getPublicLinkItemId(), self::getKeyForHash($this->config, $itemData)));
        $Data->addParam($this->session->getUserData()->getUserId());
        $Data->addParam($itemData->getPublicLinkTypeId());
        $Data->addParam((int)$itemData->isPublicLinkNotify());
        $Data->addParam(self::calcDateExpire($this->config));
        $Data->addParam($this->config->getConfigData()->getPublinksMaxViews());
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
     * Obtener los datos de una cuenta y encriptarlos para el enlace
     *
     * @param int    $itemId
     * @param string $linkKey
     * @return Vault
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    protected function getSecuredLinkData($itemId, $linkKey)
    {
        // Obtener los datos de la cuenta
        $accountService = new AccountService();
        $accountData = $accountService->getDataForLink($itemId);

        // Desencriptar la clave de la cuenta
        $key = CryptSession::getSessionKey();
        $securedKey = Crypt::unlockSecuredKey($accountData->getAccountKey(), $key);
        $accountData->setAccountPass(Crypt::decrypt($accountData->getAccountPass(), $securedKey, $key));
        $accountData->setAccountKey(null);

        $vault = new Vault();
        return serialize($vault->saveData(serialize($accountData), $linkKey));
    }

    /**
     * @param Config         $config
     * @param PublicLinkData $publicLinkData
     * @return string
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public static function getKeyForHash(Config $config, PublicLinkData $publicLinkData = null)
    {
        if (null !== $publicLinkData) {
            return $config->getConfigData()->getPasswordSalt() . $publicLinkData->getPublicLinkHash();
        }

        return $config->getConfigData()->getPasswordSalt() . Util::generateRandomBytes();
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     *
     * @param Config $config
     * @return int
     */
    protected static function calcDateExpire(Config $config)
    {
        return time() + $config->getConfigData()->getPublinksMaxTime();
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
     * @return void
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData)
    {
        $useInfo = $publicLinkData->getPublicLinkUseInfo();
        $useInfo[] = self::getUseInfo($publicLinkData->getPublicLinkHash());
        $publicLinkData->setPublicLinkUseInfo($useInfo);

        $query = /** @lang SQL */
            'UPDATE publicLinks
            SET publicLink_countViews = publicLink_countViews + 1,
            publicLink_totalCountViews = publicLink_totalCountViews + 1,
            publicLink_useInfo = ?
            WHERE publicLink_hash = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(serialize($publicLinkData->getPublicLinkUseInfo()));
        $Data->addParam($publicLinkData->getPublicLinkHash());
        $Data->setOnErrorMessage(__u('Error al actualizar enlace'));

        DbWrapper::getQuery($Data, $this->db);

        // FIXME
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__u('Ver Enlace Público'));
        $LogMessage->addDescription(__u('Enlace visualizado'));
        $LogMessage->addDetails(__u('Tipo'), $publicLinkData->getPublicLinkTypeId());
        $LogMessage->addDetails(__u('Cuenta'), AccountUtil::getAccountNameById($publicLinkData->getPublicLinkItemId()));
        $LogMessage->addDetails(__u('Usuario'), UserUtil::getUserLoginById($publicLinkData->getPublicLinkUserId()));
        $Log->writeLog();

        if ($publicLinkData->isPublicLinkNotify()) {
            Email::sendEmail($LogMessage);
        }
    }

    /**
     * Actualizar la información de uso
     *
     * @param $hash
     * @return array
     */
    protected static function getUseInfo($hash)
    {
        return [
            'who' => HttpUtil::getClientAddress(true),
            'time' => time(),
            'hash' => $hash,
            'agent' => Request::getRequestHeaders('HTTP_USER_AGENT'),
            'https' => Checks::httpsEnabled()
        ];
    }

    /**
     * Updates an item
     *
     * @param PublicLinkData $itemData
     * @return mixed
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $query = /** @lang SQL */
            'UPDATE publicLinks
            SET publicLink_hash = ?,
            publicLink_data = ?,
            publicLink_notify = ?,
            publicLink_dateExpire = ?,
            publicLink_maxCountViews = ?
            WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getPublicLinkHash());
        $Data->addParam($this->getSecuredLinkData($itemData->getPublicLinkItemId(), self::getKeyForHash($this->config, $itemData)));
        $Data->addParam((int)$itemData->isPublicLinkNotify());
        $Data->addParam(self::calcDateExpire($this->config));
        $Data->addParam($this->config->getConfigData()->getPublinksMaxViews());
        $Data->addParam($itemData->getPublicLinkId());
        $Data->setOnErrorMessage(__u('Error al actualizar enlace'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Refreshes a public link
     *
     * @param $id
     * @return $this
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refresh($id)
    {
        $publicLinkData = $this->getById($id);
        $key = self::getKeyForHash($this->config);

        $query = /** @lang SQL */
            'UPDATE publicLinks
            SET publicLink_hash = ?,
            publicLink_data = ?,
            publicLink_dateExpire = ?,
            publicLink_countViews = 0,
            publicLink_maxCountViews = ?
            WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(self::getHashForKey($key, $this->config));
        $Data->addParam($this->getSecuredLinkData($publicLinkData->getPublicLinkItemId(), $key));
        $Data->addParam(self::calcDateExpire($this->config));
        $Data->addParam($this->config->getConfigData()->getPublinksMaxViews());
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al renovar enlace'));

        DbWrapper::getQuery($Data, $this->db);

        return $this;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return PublicLinkData
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id, 
              publicLink_itemId,
              publicLink_hash,
              publicLink_data,
              publicLink_userId,
              publicLink_typeId,
              publicLink_notify,
              publicLink_dateAdd,
              publicLink_dateExpire,
              publicLink_countViews,
              publicLink_maxCountViews,
              publicLink_totalCountViews,
              publicLink_useInfo,
              user_name,
              user_login,
              account_name        
              FROM publicLinks
              INNER JOIN usrData ON user_id = publicLink_userId
              INNER JOIN accounts ON account_id = publicLink_itemId
              WHERE publicLink_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkListData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener enlace'));
        }

        return $queryRes;
    }

    /**
     * Returns the hash from a composed key
     *
     * @param string $key
     * @param Config $config
     * @return mixed
     */
    public static function getHashForKey($key, Config $config)
    {
        return str_replace($config->getConfigData()->getPasswordSalt(), '', $key);
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
              publicLink_itemId,
              publicLink_hash,
              publicLink_data,
              publicLink_userId,
              publicLink_typeId,
              publicLink_notify,
              publicLink_dateAdd,
              publicLink_dateExpire,
              publicLink_countViews,
              publicLink_maxCountViews,
              publicLink_totalCountViews,
              publicLink_useInfo,
              user_name,
              user_login,
              account_name        
              FROM publicLinks
              INNER JOIN usrData ON user_id = publicLink_userId
              INNER JOIN accounts ON account_id = publicLink_itemId
              WHERE publicLink_hash = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkData::class);
        $Data->setQuery($query);
        $Data->addParam($hash);

        /** @var PublicLinkData $queryRes */
        $queryRes = DbWrapper::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener enlace'));
        }

        return $queryRes;
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
        $Data->setMapClassName(PublicLinkData::class);
        $Data->setQuery($query);
        $Data->addParam($itemId);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener enlace'));
        }

        return $queryRes;
    }

}