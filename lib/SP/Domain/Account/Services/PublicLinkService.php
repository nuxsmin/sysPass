<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Crypt\CryptInterface;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Domain\Account\Ports\AccountServiceInterface;
use SP\Domain\Account\Ports\PublicLinkRepositoryInterface;
use SP\Domain\Account\Ports\PublicLinkServiceInterface;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Config\Ports\ConfigInterface;
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

    public function __construct(
        Application $application,
        private PublicLinkRepositoryInterface $publicLinkRepository,
        private RequestInterface $request,
        private AccountServiceInterface $accountService,
        private CryptInterface $crypt
    ) {
        parent::__construct($application);
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

        return $this->publicLinkRepository
            ->refresh($this->buildPublicLink(PublicLinkData::buildFromSimpleModel($result->getData())));
    }

    /**
     * @param  int  $id
     *
     * @return \SP\DataModel\PublicLinkListData
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getById(int $id): PublicLinkListData
    {
        $result = $this->publicLinkRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        return PublicLinkListData::buildFromSimpleModel($result->getData());
    }

    /**
     * @param  \SP\DataModel\PublicLinkData  $publicLinkData
     *
     * @return \SP\DataModel\PublicLinkData
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\CryptException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    private function buildPublicLink(PublicLinkData $publicLinkData): PublicLinkData
    {
        $key = $this->getPublicLinkKey();

        $publicLinkDataClone = clone $publicLinkData;

        $publicLinkDataClone->setHash($key->getHash());
        $publicLinkDataClone->setData($this->getSecuredLinkData($publicLinkDataClone->getItemId(), $key));
        $publicLinkDataClone->setDateExpire(self::calcDateExpire($this->config));
        $publicLinkDataClone->setMaxCountViews($this->config->getConfigData()->getPublinksMaxViews());

        if ($publicLinkDataClone->getUserId() === null) {
            $publicLinkDataClone->setUserId($this->context->getUserData()->getId());
        }

        return $publicLinkDataClone;
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
     * @param  int  $itemId
     * @param  \SP\Domain\Account\Services\PublicLinkKey  $key
     *
     * @return string
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\CryptException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    private function getSecuredLinkData(int $itemId, PublicLinkKey $key): string
    {
        $accountData = $this->accountService->getDataForLink($itemId);

        $accountDataClone = $accountData->mutate([
            'pass' => $this->crypt->decrypt(
                $accountData['pass'],
                $accountData['key'],
                $this->getMasterKeyFromContext()
            ),
            'key'  => null,
        ]);

        return Vault::factory($this->crypt)->saveData(serialize($accountDataClone), $key->getKey())->getSerialized();
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
     * @return \SP\Domain\Account\Ports\PublicLinkServiceInterface
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
        return $this->publicLinkRepository->create($this->buildPublicLink($itemData))->getLastId();
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAllBasic(): array
    {
        throw new ServiceException(__u('Not implemented'));
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param  \SP\DataModel\PublicLinkData  $publicLinkData
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function addLinkView(PublicLinkData $publicLinkData): void
    {
        $useInfo = array();

        if (empty($publicLinkData->getHash())) {
            throw new ServiceException(__u('Public link hash not set'));
        }

        if (!empty($publicLinkData->getUseInfo())) {
            $publicLinkUseInfo = unserialize($publicLinkData->getUseInfo(), ['allowed_classes' => false]);

            if (is_array($publicLinkUseInfo)) {
                $useInfo = $publicLinkUseInfo;
            }
        }

        $useInfo[] = self::getUseInfo($publicLinkData->getHash(), $this->request);

        $publicLinkDataClone = clone $publicLinkData;
        $publicLinkDataClone->setUseInfo($useInfo);

        $this->publicLinkRepository->addLinkView($publicLinkDataClone);
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

        return PublicLinkData::buildFromSimpleModel($result->getData(Simple::class));
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param  int  $itemId
     *
     * @return \SP\DataModel\PublicLinkData
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getHashForItem(int $itemId): PublicLinkData
    {
        $result = $this->publicLinkRepository->getHashForItem($itemId);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        return PublicLinkData::buildFromSimpleModel($result->getData(Simple::class));
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
