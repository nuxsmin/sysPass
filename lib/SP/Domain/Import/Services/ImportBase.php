<?php
/*
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

namespace SP\Domain\Import\Services;

use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Models\Category;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Models\Client;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Ports\ImportParams;
use SP\Domain\Import\Ports\ImportService;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagServiceInterface;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__u;

/**
 * Class ImportBase
 */
abstract class ImportBase extends Service implements ImportService
{
    protected const ITEM_CATEGORY         = 'category';
    protected const ITEM_CLIENT           = 'client';
    protected const ITEM_TAG              = 'tag';
    protected const ITEM_MASTER_PASS_HASH = 'masterpasshash';
    protected int $version = 0;
    protected int $counter = 0;
    protected readonly AccountService      $accountService;
    protected readonly CategoryService     $categoryService;
    protected readonly ClientService       $clientService;
    protected readonly TagServiceInterface $tagService;
    protected readonly ConfigService       $configService;
    private array                          $items;
    private array $cache;

    public function __construct(
        Application                       $application,
        ImportHelper                      $importHelper,
        protected readonly CryptInterface $crypt
    ) {
        parent::__construct($application);

        $this->accountService = $importHelper->getAccountService();
        $this->categoryService = $importHelper->getCategoryService();
        $this->clientService = $importHelper->getClientService();
        $this->tagService = $importHelper->getTagService();
        $this->configService = $importHelper->getConfigService();
        $this->cache = [];
    }


    /**
     * @return int
     */
    public function getCounter(): int
    {
        return $this->counter;
    }

    /**
     * @throws ConstraintException
     * @throws ImportException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws SPException
     * @throws CryptException
     */
    final protected function addAccount(AccountCreateDto $accountCreateDto, ImportParams $importParams): void
    {
        if (empty($accountCreateDto->getCategoryId())) {
            throw new ImportException(__u('Category Id not set. Unable to import account.'));
        }

        if (empty($accountCreateDto->getClientId())) {
            throw new ImportException(__u('Client Id not set. Unable to import account.'));
        }

        $hasValidHash = $this->getOrSetCache(
            self::ITEM_MASTER_PASS_HASH,
            '',
            fn() => $this->validateHash($importParams)
        );

        $dto = $accountCreateDto
            ->set('userId', $importParams->getDefaultUser())
            ->set('userGroupId', $importParams->getDefaultGroup());

        if ($hasValidHash === false && !empty($importParams->getMasterPassword())) {
            if ($this->version >= 210) {
                $pass = $this->crypt->decrypt(
                    $accountCreateDto->getPass(),
                    $accountCreateDto->getKey(),
                    $importParams->getMasterPassword()
                );

                $dto = $accountCreateDto->set('pass', $pass)->set('key', '');
            } else {
                throw ImportException::error(__u('The file was exported with an old sysPass version (<= 2.10).'));
            }
        }

        $this->accountService->create($dto);
        $this->counter++;
    }

    final protected function getOrSetCache(string $type, int|string $key, callable $value = null): mixed
    {
        $hash = sha1($type . $key);

        if (isset($this->cache[$hash])) {
            return $this->cache[$hash];
        }

        if (null !== $value) {
            $this->cache[$hash] = $value();

            return $this->cache[$hash];
        }

        return null;
    }

    /**
     * @throws ServiceException
     */
    private function validateHash(ImportParams $importParams): bool
    {
        if (!empty($importParams->getMasterPassword())) {
            try {
                return Hash::checkHashKey(
                    $importParams->getMasterPassword(),
                    $this->configService->getByParam('masterPwd')
                );
            } catch (NoSuchItemException $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws DuplicatedItemException
     * @throws SPException
     */
    protected function addCategory(Category $category): int
    {
        return $this->getOrSetCache(
            self::ITEM_CATEGORY,
            $category->getName(),
            fn(): ?int => $this->categoryService->getByName($category->getName())?->getId()
                ?: $this->categoryService->create($category)
        );
    }

    /**
     * @throws DuplicatedItemException
     * @throws SPException
     */
    protected function addClient(Client $client): int
    {
        $clientId = $this->getOrSetCache(
            self::ITEM_CLIENT,
            $client->getName(),
            fn(): ?int => $this->clientService->getByName($client->getName())?->getId()
                ?: $this->clientService->create($client)
        );

        return $clientId ?? $this->clientService->create($client);
    }

    /**
     * @throws SPException
     */
    protected function addTag(Tag $tag): int
    {
        return $this->getOrSetCache(
            self::ITEM_TAG,
            $tag->getId(),
            fn(): ?int => $this->tagService->getByName($tag->getName())?->getId()
                ?: $this->tagService->create($tag)
        );
    }
}
