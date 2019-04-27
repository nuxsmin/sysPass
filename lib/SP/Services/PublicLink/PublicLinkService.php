<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Config\Config;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Http\Request;
use SP\Http\Uri;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\PublicLink\PublicLinkRepository;
use SP\Services\Account\AccountService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;

/**
 * Class PublicLinkService
 *
 * @package SP\Services\PublicLink
 */
final class PublicLinkService extends Service
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
     * @var Request
     */
    protected $request;

    /**
     * Returns an HTTP URL for given hash
     *
     * @param $baseUrl
     * @param $hash
     *
     * @return string
     */
    public static function getLinkForHash($baseUrl, $hash)
    {
        return (new Uri($baseUrl))->addParam('r', 'account/viewLink/' . $hash)->getUri();
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
     * @param string         $salt
     * @param PublicLinkData $publicLinkData
     *
     * @return string
     */
    public static function getKeyForHash($salt, PublicLinkData $publicLinkData)
    {
        return sha1($salt . $publicLinkData->getHash());
    }

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->publicLinkRepository->search($itemSearchData);
    }

    /**
     * @param $id
     *
     * @return PublicLinkListData
     * @throws SPException
     */
    public function getById($id)
    {
        $result = $this->publicLinkRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        return $result->getData();
    }

    /**
     * @param $id
     *
     * @return bool
     * @throws SPException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConstraintException
     * @throws QueryException
     */
    public function refresh($id)
    {
        $result = $this->publicLinkRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        $key = $this->getPublicLinkKey();

        /** @var PublicLinkData $publicLinkData */
        $publicLinkData = $result->getData();
        $publicLinkData->setHash($key->getHash());
        $publicLinkData->setData($this->getSecuredLinkData($publicLinkData->getItemId(), $key));
        $publicLinkData->setDateExpire(self::calcDateExpire($this->config));
        $publicLinkData->setMaxCountViews($this->config->getConfigData()->getPublinksMaxViews());

        return $this->publicLinkRepository->refresh($publicLinkData);
    }

    /**
     * @param string|null $hash
     *
     * @return PublicLinkKey
     * @throws EnvironmentIsBrokenException
     */
    public function getPublicLinkKey(string $hash = null)
    {
        return new PublicLinkKey($this->config->getConfigData()->getPasswordSalt(), $hash);
    }

    /**
     * Obtener los datos de una cuenta y encriptarlos para el enlace
     *
     * @param int           $itemId
     * @param PublicLinkKey $key
     *
     * @return Vault
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     */
    private function getSecuredLinkData($itemId, PublicLinkKey $key)
    {
        // Obtener los datos de la cuenta
        $accountData = $this->dic->get(AccountService::class)->getDataForLink($itemId);

        // Desencriptar la clave de la cuenta
        $accountData->setPass(Crypt::decrypt($accountData->getPass(), $accountData->getKey(), $this->getMasterKeyFromContext()));
        $accountData->setKey(null);

        return (new Vault())->saveData(serialize($accountData), $key->getKey())->getSerialized();
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
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        if ($this->publicLinkRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Link not found'), NoSuchItemException::INFO);
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->publicLinkRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while removing the links'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * @param PublicLinkData $itemData
     *
     * @return int
     * @throws SPException
     * @throws CryptoException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PublicLinkData $itemData)
    {
        $key = $this->getPublicLinkKey();

        $itemData->setHash($key->getHash());
        $itemData->setData($this->getSecuredLinkData($itemData->getItemId(), $key));
        $itemData->setDateExpire(self::calcDateExpire($this->config));
        $itemData->setMaxCountViews($this->config->getConfigData()->getPublinksMaxViews());
        $itemData->setUserId($this->context->getUserData()->getId());

        return $this->publicLinkRepository->create($itemData)->getLastId();
    }

    /**
     * Get all items from the service's repository
     *
     * @return PublicLinkListData[]
     * @throws ConstraintException
     * @throws QueryException
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
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData)
    {
        /** @var array $useInfo */
        $useInfo = unserialize($publicLinkData->getUseInfo());
        $useInfo[] = self::getUseInfo($publicLinkData->getHash(), $this->request);
        $publicLinkData->setUseInfo($useInfo);

        if ($this->publicLinkRepository->addLinkView($publicLinkData) === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }
    }

    /**
     * Actualizar la información de uso
     *
     * @param string  $hash
     *
     * @param Request $request
     *
     * @return array
     */
    public static function getUseInfo($hash, Request $request)
    {
        return [
            'who' => $request->getClientAddress(true),
            'time' => time(),
            'hash' => $hash,
            'agent' => $request->getHeader('User-Agent'),
            'https' => $request->isHttps()
        ];
    }

    /**
     * @param $hash string
     *
     * @return bool|PublicLinkData
     * @throws SPException
     */
    public function getByHash($hash)
    {
        $result = $this->publicLinkRepository->getByHash($hash);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        return $result->getData();
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     *
     * @return PublicLinkData
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getHashForItem($itemId)
    {
        $result = $this->publicLinkRepository->getHashForItem($itemId);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
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
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PublicLinkData $itemData)
    {
        return $this->publicLinkRepository->update($itemData);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->publicLinkRepository = $this->dic->get(PublicLinkRepository::class);
        $this->request = $this->dic->get(Request::class);
    }
}