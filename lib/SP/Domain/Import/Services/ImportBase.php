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
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Ports\ImportParams;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagServiceInterface;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;

use function SP\__u;

/**
 * Class ImportBase
 */
abstract class ImportBase extends Service implements Import
{
    protected int $version = 0;
    /**
     * @var bool Indica si el hash de la clave suministrada es igual a la actual
     */
    protected bool                         $mPassValidHash = false;
    protected int                          $counter        = 0;
    protected readonly AccountService      $accountService;
    protected readonly CategoryService     $categoryService;
    protected readonly ClientService       $clientService;
    protected readonly TagServiceInterface $tagService;
    protected readonly ConfigService       $configService;
    private array                          $items;

    public function __construct(
        Application                     $application,
        ImportHelper                    $importHelper,
        private readonly CryptInterface $crypt
    ) {
        parent::__construct($application);

        $this->accountService = $importHelper->getAccountService();
        $this->categoryService = $importHelper->getCategoryService();
        $this->clientService = $importHelper->getClientService();
        $this->tagService = $importHelper->getTagService();
        $this->configService = $importHelper->getConfigService();
    }


    /**
     * @return int
     */
    public function getCounter(): int
    {
        return $this->counter;
    }

    /**
     * Añadir una cuenta desde un archivo importado.
     *
     * @param AccountCreateDto $accountCreateDto
     * @param ImportParams $importParams
     * @throws ConstraintException
     * @throws ImportException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws SPException
     * @throws CryptException
     */
    protected function addAccount(AccountCreateDto $accountCreateDto, ImportParams $importParams): void
    {
        if (empty($accountCreateDto->getCategoryId())) {
            throw new ImportException(__u('Category Id not set. Unable to import account.'));
        }

        if (empty($accountCreateDto->getClientId())) {
            throw new ImportException(__u('Client Id not set. Unable to import account.'));
        }

        $hasValidHash = $this->validateHash($importParams);

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

    private function validateHash(ImportParams $importParams): bool
    {
        if (!empty($importParams->getMasterPassword())) {
            return Hash::checkHashKey(
                $importParams->getMasterPassword(),
                $this->configService->getByParam('masterPwd')
            );
        }

        return true;
    }

    /**
     * Añadir una categoría y devolver el Id
     *
     * @param Category $category
     *
     * @return int
     * @throws DuplicatedItemException
     * @throws SPException
     */
    protected function addCategory(Category $category): int
    {
        try {
            $categoryId = $this->getWorkingItem('category', $category->getName());

            return $categoryId ?? $this->categoryService->create($category);
        } catch (DuplicatedItemException $e) {
            $itemData = $this->categoryService->getByName($category->getName());

            if ($itemData === null) {
                throw $e;
            }

            return $this->addWorkingItem('category', $itemData->getName(), $itemData->getId());
        }
    }

    /**
     * @param string $type
     * @param string|int $value
     *
     * @return int|null
     */
    protected function getWorkingItem(string $type, $value): ?int
    {
        return $this->items[$type][$value] ?? null;
    }

    /**
     * @param string $type
     * @param string|int $value
     * @param int $id
     *
     * @return int
     */
    protected function addWorkingItem(string $type, $value, int $id): int
    {
        if (isset($this->items[$type][$value])) {
            return $this->items[$type][$value];
        }

        $this->items[$type][$value] = $id;

        return $id;
    }

    /**
     * Añadir un cliente y devolver el Id
     *
     * @param Client $client
     *
     * @return int
     * @throws DuplicatedItemException
     * @throws SPException
     */
    protected function addClient(Client $client): int
    {
        try {
            $clientId = $this->getWorkingItem('client', $client->getName());

            return $clientId ?? $this->clientService->create($client);
        } catch (DuplicatedItemException $e) {
            $itemData = $this->clientService->getByName($client->getName());

            if ($itemData === null) {
                throw $e;
            }

            return $this->addWorkingItem(
                'client',
                $itemData->getName(),
                $itemData->getId()
            );
        }
    }

    /**
     * Añadir una etiqueta y devolver el Id
     *
     * @param Tag $tag
     *
     * @return int
     * @throws SPException
     */
    protected function addTag(Tag $tag): int
    {
        try {
            $tagId = $this->getWorkingItem('tag', $tag->getName());

            return $tagId ?? $this->tagService->create($tag);
        } catch (DuplicatedItemException $e) {
            $itemData = $this->tagService->getByName($tag->getName());

            if ($itemData === null) {
                throw $e;
            }

            return $this->addWorkingItem(
                'tag',
                $itemData->getName(),
                $itemData->getId()
            );
        }
    }
}
