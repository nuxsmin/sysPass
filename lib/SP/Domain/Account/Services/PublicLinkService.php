<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Account\Services;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Core\Application;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Domain\Account\AccountServiceInterface;
use SP\Domain\Account\PublicLinkServiceInterface;
use SP\Domain\Account\Repositories\PublicLinkRepositoryInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Config\ConfigInterface;
use SP\Http\RequestInterface;
use SP\Http\Uri;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use function SP\__u;

/**
 * Class PublicLinkService
 *
 * @package SP\Domain\Common\Services\PublicLink
 */
final class PublicLinkService extends Service implements PublicLinkServiceInterface
{
    use ServiceItemTrait;

    /**
     * Tipos de enlaces
     */
    public const TYPE_ACCOUNT = 1;

    private PublicLinkRepositoryInterface $publicLinkRepository;
    private RequestInterface              $request;
    private AccountServiceInterface       $accountService;

    public function __construct(
        Application $application,
        PublicLinkRepositoryInterface $publicLinkRepository,
        RequestInterface $request,
        AccountServiceInterface $accountService
    ) {
        parent::__construct($application);

        $this->publicLinkRepository = $publicLinkRepository;
        $this->request = $request;
        $this->accountService = $accountService;
    }

    /**
     * Returns an HTTP URL for given hash
     */
    public static function getLinkForHash(string $baseUrl, string $hash): string
    {
        return (new Uri($baseUrl))->addParam('r', 'account/viewLink/'.$hash)->getUri();
    }

    /**
     * Generar el hash para el enlace
     */
    public static function createLinkHash(): string
    {
        return hash('sha256', uniqid('sysPassPublicLink', true));
    }

    public static function getKeyForHash(string $salt, PublicLinkData $publicLinkData): string
    {
        return sha1($salt.$publicLinkData->getHash());
    }

    /**
     * @param  \SP\DataModel\ItemSearchData  $itemSearchData
     *
     * @return \SP\Infrastructure\Database\QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->publicLinkRepository->search($itemSearchData);
    }

    /**
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
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
     * @param  int  $id
     *
     * @return \SP\DataModel\PublicLinkListData
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
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
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function getSecuredLinkData(int $itemId, PublicLinkKey $key): string
    {
        // Obtener los datos de la cuenta
        $accountData = $this->accountService->getDataForLink($itemId);

        // Desencriptar la clave de la cuenta
        $accountData->setPass(
            Crypt::decrypt(
                $accountData->getPass(),
                $accountData->getKey(),
                $this->getMasterKeyFromContext()
            )
        );
        $accountData->setKey(null);

        return (new Vault())->saveData(serialize($accountData), $key->getKey())->getSerialized();
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     */
    public static function calcDateExpire(ConfigInterface $config): int
    {
        return time() + $config->getConfigData()->getPublinksMaxTime();
    }

    /**
     * @param  int  $id
     *
     * @return \SP\Domain\Account\PublicLinkServiceInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete(int $id): PublicLinkServiceInterface
    {
        $this->publicLinkRepository->delete($id);

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->publicLinkRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(__u('Error while removing the links'), SPException::WARNING);
        }

        return $count;
    }

    /**
     * @throws SPException
     * @throws CryptoException
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
     */
    public function getAllBasic(): array
    {
        return $this->publicLinkRepository->getAll()->getDataAsArray();
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param  \SP\DataModel\PublicLinkData  $publicLinkData
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData): void
    {
        /** @var array $useInfo */
        $useInfo = unserialize($publicLinkData->getUseInfo(), false);
        $useInfo[] = self::getUseInfo($publicLinkData->getHash(), $this->request);
        $publicLinkData->setUseInfo($useInfo);

        $this->publicLinkRepository->addLinkView($publicLinkData);
    }

    /**
     * Actualizar la información de uso
     */
    public static function getUseInfo(string $hash, RequestInterface $request): array
    {
        return [
            'who'   => $request->getClientAddress(true),
            'time'  => time(),
            'hash'  => $hash,
            'agent' => $request->getHeader('User-Agent'),
            'https' => $request->isHttps(),
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
     * @param  int  $itemId
     *
     * @return \SP\DataModel\PublicLinkData
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
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
    public function update(PublicLinkData $itemData): void
    {
        $this->publicLinkRepository->update($itemData);
    }
}
