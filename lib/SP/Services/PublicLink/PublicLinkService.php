<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
    public const TYPE_ACCOUNT = 1;

    protected ?PublicLinkRepository $publicLinkRepository = null;
    protected ?Request $request = null;

    /**
     * Returns an HTTP URL for given hash
     */
    public static function getLinkForHash(string $baseUrl, string $hash): string
    {
        return (new Uri($baseUrl))
            ->addParam('r', 'account/viewLink/' . $hash)
            ->getUri();
    }

    /**
     * Generar el hash para el enlace
     */
    public static function createLinkHash(): string
    {
        return hash('sha256', uniqid('sysPassPublicLink', true));
    }

    public static function getKeyForHash(
        string         $salt,
        PublicLinkData $publicLinkData
    ): string
    {
        return sha1($salt . $publicLinkData->getHash());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->publicLinkRepository->search($itemSearchData);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function getById(int $id): PublicLinkListData
    {
        $result = $this->publicLinkRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        return $result->getData();
    }

    /**
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Repositories\NoSuchItemException
     * @throws \SP\Services\ServiceException
     */
    public function refresh(int $id): bool
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
     * @throws EnvironmentIsBrokenException
     */
    public function getPublicLinkKey(?string $hash = null): PublicLinkKey
    {
        return new PublicLinkKey(
            $this->config->getConfigData()->getPasswordSalt(),
            $hash
        );
    }

    /**
     * Obtener los datos de una cuenta y encriptarlos para el enlace
     *
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     * @throws \SP\Services\ServiceException
     */
    private function getSecuredLinkData(int $itemId, PublicLinkKey $key): string
    {
        // Obtener los datos de la cuenta
        $accountData = $this->dic->get(AccountService::class)->getDataForLink($itemId);

        // Desencriptar la clave de la cuenta
        $accountData->setPass(Crypt::decrypt(
            $accountData->getPass(),
            $accountData->getKey(),
            $this->getMasterKeyFromContext())
        );
        $accountData->setKey(null);

        return (new Vault())->saveData(serialize($accountData), $key->getKey())->getSerialized();
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     */
    public static function calcDateExpire(Config $config): int
    {
        return time() + $config->getConfigData()->getPublinksMaxTime();
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function delete(int $id): PublicLinkService
    {
        if ($this->publicLinkRepository->delete($id) === 0) {
            throw new NoSuchItemException(
                __u('Link not found'),
                SPException::INFO
            );
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->publicLinkRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while removing the links'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * @throws SPException
     * @throws CryptoException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(PublicLinkData $itemData): int
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
    public function getAllBasic(): array
    {
        return $this->publicLinkRepository->getAll()->getDataAsArray();
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData): void
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
     */
    public static function getUseInfo(string $hash, Request $request): array
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
     * @throws SPException
     */
    public function getByHash(string $hash): PublicLinkData
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getHashForItem(int $itemId): PublicLinkData
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
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PublicLinkData $itemData): int
    {
        return $this->publicLinkRepository->update($itemData);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize(): void
    {
        $this->publicLinkRepository = $this->dic->get(PublicLinkRepository::class);
        $this->request = $this->dic->get(Request::class);
    }
}