<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Crypt\Vault;
use SP\Domain\Account\Dtos\PublicLinkKey;
use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Domain\Account\Models\PublicLinkList;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\PublicLinkRepository;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Http\Providers\Uri;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class PublicLink
 */
final class PublicLink extends Service implements PublicLinkService
{
    /**
     * Tipos de enlaces
     */
    public const TYPE_ACCOUNT = 1;

    public function __construct(
        Application                           $application,
        private readonly PublicLinkRepository $publicLinkRepository,
        private readonly RequestService $request,
        private readonly AccountService       $accountService,
        private readonly CryptInterface       $crypt
    ) {
        parent::__construct($application);
    }

    /**
     * Returns an HTTP URL for given hash
     */
    public static function getLinkForHash(string $baseUrl, string $hash): string
    {
        return (new Uri($baseUrl))->addParam('r', 'account/viewLink/' . $hash)->getUri();
    }

    /**
     * Generar el hash para el enlace
     */
    public static function createLinkHash(): string
    {
        return hash('sha256', uniqid('sysPassPublicLink', true));
    }

    /**
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->publicLinkRepository->search($itemSearchData);
    }

    /**
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function refresh(int $id): bool
    {
        $result = $this->publicLinkRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        return $this->publicLinkRepository
            ->refresh($this->buildPublicLink(PublicLinkModel::buildFromSimpleModel($result->getData())));
    }

    /**
     * @param int $id
     *
     * @return PublicLinkList
     * @throws SPException
     * @throws NoSuchItemException
     */
    public function getById(int $id): PublicLinkList
    {
        $result = $this->publicLinkRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        return PublicLinkList::buildFromSimpleModel($result->getData());
    }

    /**
     * @param PublicLinkModel $publicLink
     *
     * @return PublicLinkModel
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws CryptException
     * @throws QueryException
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    private function buildPublicLink(PublicLinkModel $publicLink): PublicLinkModel
    {
        $key = $this->getPublicLinkKey();

        return $publicLink->mutate(
            [
                'hash' => $key->getHash(),
                'data' => $this->getSecuredLinkData($publicLink->getItemId(), $key),
                'dateExpire' => self::calcDateExpire($this->config),
                'maxCountViews' => $this->config->getConfigData()->getPublinksMaxViews(),
                'userId' => $publicLink->getUserId() ?? $this->context->getUserData()->getId()
            ]
        );
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
     * @param int $itemId
     * @param PublicLinkKey $key
     *
     * @return string
     * @throws ConstraintException
     * @throws CryptException
     * @throws QueryException
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    private function getSecuredLinkData(int $itemId, PublicLinkKey $key): string
    {
        $account = $this->accountService->getDataForLink($itemId);

        $properties = [
            'pass' => $this->crypt->decrypt(
                $account['pass'],
                $account['key'],
                $this->getMasterKeyFromContext()
            ),
            'key' => null,
        ];

        return Vault::factory($this->crypt)
            ->saveData(Serde::serialize($account->mutate($properties)), $key->getKey())
                    ->getSerialized();
    }

    /**
     * Devolver el tiempo de caducidad del enlace
     */
    public static function calcDateExpire(ConfigFileService $config): int
    {
        return time() + $config->getConfigData()->getPublinksMaxTime();
    }

    /**
     * @param int $id
     *
     * @return PublicLinkService
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): PublicLinkService
    {
        $this->publicLinkRepository->delete($id);

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
    public function create(PublicLinkModel $itemData): int
    {
        return $this->publicLinkRepository->create($this->buildPublicLink($itemData))->getLastId();
    }

    /**
     * @throws SPException
     */
    public function getAll(): array
    {
        throw new ServiceException(__u('Not implemented'));
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param PublicLinkModel $publicLink
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function addLinkView(PublicLinkModel $publicLink): void
    {
        $useInfo = array();

        if (empty($publicLink->getHash())) {
            throw new ServiceException(__u('Public link hash not set'));
        }

        if (!empty($publicLink->getUseInfo())) {
            $publicLinkUseInfo = unserialize($publicLink->getUseInfo(), ['allowed_classes' => false]);

            if (is_array($publicLinkUseInfo)) {
                $useInfo = $publicLinkUseInfo;
            }
        }

        $useInfo[] = self::getUseInfo($publicLink->getHash(), $this->request);

        $this->publicLinkRepository->addLinkView($publicLink->mutate(['useInfo' => Serde::serialize($useInfo)]));
    }

    /**
     * Actualizar la información de uso
     */
    public static function getUseInfo(string $hash, RequestService $request): array
    {
        return [
            'who' => $request->getClientAddress(true),
            'time' => time(),
            'hash' => $hash,
            'agent' => $request->getHeader('User-Agent'),
            'https' => $request->isHttps(),
        ];
    }

    /**
     * @throws SPException
     */
    public function getByHash(string $hash): PublicLinkModel
    {
        $result = $this->publicLinkRepository->getByHash($hash);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        return PublicLinkModel::buildFromSimpleModel($result->getData(Simple::class));
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     *
     * @return PublicLinkModel
     * @throws SPException
     * @throws NoSuchItemException
     */
    public function getHashForItem(int $itemId): PublicLinkModel
    {
        $result = $this->publicLinkRepository->getHashForItem($itemId);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Link not found'));
        }

        return PublicLinkModel::buildFromSimpleModel($result->getData(Simple::class));
    }

    /**
     * Updates an item
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PublicLinkModel $itemData): void
    {
        $this->publicLinkRepository->update($itemData);
    }
}
