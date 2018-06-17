<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Services\PublicLink;

use SP\Bootstrap;
use SP\Config\Config;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\Http\Request;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\PublicLink\PublicLinkRepository;
use SP\Services\Account\AccountService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;
use SP\Util\Checks;
use SP\Util\HttpUtil;
use SP\Util\Util;

/**
 * Class PublicLinkService
 *
 * @package SP\Services\PublicLink
 */
class PublicLinkService extends Service
{
    use ServiceItemTrait;

    /**
     * Tipos de enlaces
     */
    const TYPE_ACCOUNT = 1;
    /**
     * @var PublicLinkRepository
     */
    protected $publicLinkRepository;

    /**
     * Returns an HTTP URL for given hash
     *
     * @param $hash
     *
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
    public static function createLinkHash()
    {
        return hash('sha256', uniqid('sysPassPublicLink', true));
    }

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->publicLinkRepository->search($itemSearchData);
    }

    /**
     * @param $id
     *
     * @return \SP\DataModel\PublicLinkListData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getById($id)
    {
        return $this->publicLinkRepository->getById($id)->getData();
    }

    /**
     * @param $id
     *
     * @return bool
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refresh($id)
    {
        $salt = $this->config->getConfigData()->getPasswordSalt();
        $key = self::getNewKey($salt);

        $result = $this->publicLinkRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('El enlace no existe'));
        }

        /** @var PublicLinkData $publicLinkData */
        $publicLinkData = $result->getData();
        $publicLinkData->setHash(self::getHashForKey($key, $salt));
        $publicLinkData->setData($this->getSecuredLinkData($publicLinkData->getItemId(), $key));
        $publicLinkData->setDateExpire(self::calcDateExpire($this->config));
        $publicLinkData->setCountViews($this->config->getConfigData()->getPublinksMaxViews());

        return $this->publicLinkRepository->refresh($publicLinkData);
    }

    /**
     * @param string $salt
     *
     * @return string
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public static function getNewKey($salt)
    {
        return $salt . Util::generateRandomBytes();
    }

    /**
     * Returns the hash from a composed key
     *
     * @param string $key
     * @param string $salt
     *
     * @return mixed
     */
    public static function getHashForKey($key, $salt)
    {
        return str_replace($salt, '', $key);
    }

    /**
     * Obtener los datos de una cuenta y encriptarlos para el enlace
     *
     * @param int    $itemId
     * @param string $linkKey
     *
     * @return Vault
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws SPException
     */
    protected function getSecuredLinkData($itemId, $linkKey)
    {
        // Obtener los datos de la cuenta
        $accountData = $this->dic->get(AccountService::class)->getDataForLink($itemId);

        // Desencriptar la clave de la cuenta
        $accountData->setPass(Crypt::decrypt($accountData->getPass(), $accountData->getKey(), CryptSession::getSessionKey($this->context)));
        $accountData->setKey(null);

        $vault = new Vault();
        return serialize($vault->saveData(serialize($accountData), $linkKey));
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     *
     * @param Config $config
     *
     * @return int
     */
    public static function calcDateExpire(Config $config)
    {
        return time() + $config->getConfigData()->getPublinksMaxTime();
    }

    /**
     * @param $id
     *
     * @return $this
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if ($this->publicLinkRepository->delete($id) === 0) {
            throw new ServiceException(__u('Enlace no encontrado'), ServiceException::INFO);
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->publicLinkRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error al eliminar los enlaces'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * @param PublicLinkData $itemData
     *
     * @return int
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(PublicLinkData $itemData)
    {
        $itemData->setData($this->getSecuredLinkData($itemData->getItemId(), self::getKeyForHash($this->config->getConfigData()->getPasswordSalt(), $itemData)));
        $itemData->setDateExpire(self::calcDateExpire($this->config));
        $itemData->setMaxCountViews($this->config->getConfigData()->getPublinksMaxViews());
        $itemData->setUserId($this->context->getUserData()->getId());

        return $this->publicLinkRepository->create($itemData)->getLastId();
    }

    /**
     * @param string         $salt
     * @param PublicLinkData $publicLinkData
     *
     * @return string
     */
    public static function getKeyForHash($salt, PublicLinkData $publicLinkData)
    {
        return $salt . $publicLinkData->getHash();
    }

    /**
     * Get all items from the service's repository
     *
     * @return array
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAllBasic()
    {
        return $this->publicLinkRepository->getAll()->getDataAsArray();
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param PublicLinkData $publicLinkData
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData)
    {
        /** @var array $useInfo */
        $useInfo = unserialize($publicLinkData->getUseInfo());
        $useInfo[] = self::getUseInfo($publicLinkData->getHash());
        $publicLinkData->setUseInfo($useInfo);

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

        return $this->publicLinkRepository->addLinkView($publicLinkData);
    }

    /**
     * Actualizar la información de uso
     *
     * @param $hash
     *
     * @return array
     */
    public static function getUseInfo($hash)
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
     * @param $hash string
     *
     * @return bool|PublicLinkData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getByHash($hash)
    {
        $result = $this->publicLinkRepository->getByHash($hash);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('El enlace no existe'));
        }

        return $result->getData();
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     *
     * @return PublicLinkData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function getHashForItem($itemId)
    {
        $result = $this->publicLinkRepository->getHashForItem($itemId);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('El enlace no existe'));
        }

        return $result->getData();
    }

    /**
     * Updates an item
     *
     * @param PublicLinkData $itemData
     *
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(PublicLinkData $itemData)
    {
        return $this->publicLinkRepository->update($itemData);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->publicLinkRepository = $this->dic->get(PublicLinkRepository::class);
    }
}