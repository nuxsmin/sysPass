<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories\PublicLink;

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
use SP\Repositories\Account\AccountRepository;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\HttpUtil;
use SP\Util\Util;

/**
 * Class PublicLinkRepository
 *
 * @package SP\Repositories\PublicLink
 */
class PublicLinkRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

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
     * @return int
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM PublicLink WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar enlace'));

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows();
    }

    /**
     * Returns all the items
     *
     * @return PublicLinkData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.dateUpdate,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName        
              FROM PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON itemId = A.id';

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
            'SELECT PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.dateUpdate,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName        
              FROM PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON itemId = A.id
              WHERE PL.id IN (' . $this->getParamsFromArray($ids) . ')';

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
            'DELETE FROM PublicLink WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return void
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Unimplemented');
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
        $Data->setSelect('PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.dateUpdate,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName');
        $Data->setFrom('PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON itemId = A.id');
        $Data->setOrder('PL.dateExpire DESC');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('U.login LIKE ? OR A.name LIKE ?');

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
            'INSERT INTO PublicLink
            SET itemId = ?,
            `hash` = ?,
            data = ?,
            userId = ?,
            typeId = ?,
            notify = ?,
            dateAdd = UNIX_TIMESTAMP(),
            dateExpire = ?,
            maxCountViews = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getItemId());
        $Data->addParam($itemData->getHash());
        $Data->addParam($this->getSecuredLinkData($itemData->getItemId(), self::getKeyForHash($this->config, $itemData)));
        $Data->addParam($this->session->getUserData()->getId());
        $Data->addParam($itemData->getTypeId());
        $Data->addParam((int)$itemData->isNotify());
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
            'SELECT id FROM PublicLink WHERE itemId = ? LIMIT 1';

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
        $accountService = new AccountRepository();
        $accountData = $accountService->getDataForLink($itemId);

        // Desencriptar la clave de la cuenta
        $key = CryptSession::getSessionKey();
        $securedKey = Crypt::unlockSecuredKey($accountData->getKey(), $key);
        $accountData->setPass(Crypt::decrypt($accountData->getPass(), $securedKey, $key));
        $accountData->setKey(null);

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
            return $config->getConfigData()->getPasswordSalt() . $publicLinkData->getHash();
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
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param PublicLinkData $publicLinkData
     * @return void
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData)
    {
        $useInfo = $publicLinkData->getUseInfo();
        $useInfo[] = self::getUseInfo($publicLinkData->getHash());
        $publicLinkData->setUseInfo($useInfo);

        $query = /** @lang SQL */
            'UPDATE PublicLink
            SET countViews = countViews + 1,
            totalCountViews = totalCountViews + 1,
            useInfo = ?
            WHERE `hash` = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(serialize($publicLinkData->getUseInfo()));
        $Data->addParam($publicLinkData->getHash());
        $Data->setOnErrorMessage(__u('Error al actualizar enlace'));

        DbWrapper::getQuery($Data, $this->db);

        // FIXME
//        $Log = new Log();
//        $LogMessage = $Log->getLogMessage();
//        $LogMessage->setAction(__u('Ver Enlace Público'));
//        $LogMessage->addDescription(__u('Enlace visualizado'));
//        $LogMessage->addDetails(__u('Tipo'), $publicLinkData->getPublicLinkTypeId());
//        $LogMessage->addDetails(__u('Cuenta'), AccountUtil::getAccountNameById($publicLinkData->getPublicLinkItemId()));
//        $LogMessage->addDetails(__u('Usuario'), UserUtil::getUserLoginById($publicLinkData->getPublicLinkUserId()));
//        $Log->writeLog();
//
//        if ($publicLinkData->isPublicLinkNotify()) {
//            Email::sendEmail($LogMessage);
//        }
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
            'UPDATE PublicLink
            SET `hash` = ?,
            data = ?,
            notify = ?,
            dateExpire = ?,
            maxCountViews = ?
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getHash());
        $Data->addParam($this->getSecuredLinkData($itemData->getItemId(), self::getKeyForHash($this->config, $itemData)));
        $Data->addParam((int)$itemData->isNotify());
        $Data->addParam(self::calcDateExpire($this->config));
        $Data->addParam($this->config->getConfigData()->getPublinksMaxViews());
        $Data->addParam($itemData->getId());
        $Data->setOnErrorMessage(__u('Error al actualizar enlace'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Refreshes a public link
     *
     * @param $id
     * @return bool
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refresh($id)
    {
        $publicLinkData = $this->getById($id);
        $key = self::getKeyForHash($this->config);

        $query = /** @lang SQL */
            'UPDATE PublicLink
            SET `hash` = ?,
            data = ?,
            dateExpire = ?,
            countViews = 0,
            maxCountViews = ?
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(self::getHashForKey($key, $this->config));
        $Data->addParam($this->getSecuredLinkData($publicLinkData->getItemId(), $key));
        $Data->addParam(self::calcDateExpire($this->config));
        $Data->addParam($this->config->getConfigData()->getPublinksMaxViews());
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al renovar enlace'));

        return DbWrapper::getQuery($Data, $this->db);
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
            'SELECT PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName        
              FROM PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON PL.itemId = A.id
              WHERE PL.id = ? LIMIT 1';

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
            'SELECT PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.dateUpdate,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName        
              FROM PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON itemId = A.id
              WHERE PL.hash = ? LIMIT 1';

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
            'SELECT id, `hash` FROM PublicLink WHERE itemId = ? LIMIT 1';

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