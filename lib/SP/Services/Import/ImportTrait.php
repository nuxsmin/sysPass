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

namespace SP\Services\Import;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\OldCrypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\ClientData;
use SP\DataModel\TagData;
use SP\Repositories\DuplicatedItemException;
use SP\Services\Account\AccountRequest;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Tag\TagService;

/**
 * Trait ImportTrait
 *
 * @package SP\Services\Import
 */
trait ImportTrait
{
    /**
     * @var ImportParams
     */
    protected $importParams;
    /**
     * @var int
     */
    protected $version = 0;
    /**
     * @var bool Indica si el hash de la clave suministrada es igual a la actual
     */
    protected $mPassValidHash = false;
    /**
     * @var int
     */
    protected $counter = 0;
    /**
     * @var AccountService
     */
    private $accountService;
    /**
     * @var CategoryService
     */
    private $categoryService;
    /**
     * @var ClientService
     */
    private $clientService;
    /**
     * @var TagService
     */
    private $tagService;
    /**
     * @var array
     */
    private $items;

    /**
     * @return int
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * Añadir una cuenta desde un archivo importado.
     *
     * @param AccountRequest $accountRequest
     *
     * @throws ImportException
     * @throws SPException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     */
    protected function addAccount(AccountRequest $accountRequest)
    {
        if (empty($accountRequest->categoryId)) {
            throw new ImportException(__u('Category Id not set. Unable to import account.'));
        }

        if (empty($accountRequest->clientId)) {
            throw new ImportException(__u('Client Id not set. Unable to import account.'));
        }

        $accountRequest->userId = $this->importParams->getDefaultUser();
        $accountRequest->userGroupId = $this->importParams->getDefaultGroup();

        if ($this->mPassValidHash === false && !empty($this->importParams->getImportMasterPwd())) {
            if ($this->version >= 210) {
                $pass = Crypt::decrypt($accountRequest->pass, $accountRequest->key, $this->importParams->getImportMasterPwd());
            } else {
                $pass = OldCrypt::getDecrypt($accountRequest->pass, $accountRequest->key, $this->importParams->getImportMasterPwd());
            }

            $accountRequest->pass = $pass;
            $accountRequest->key = '';
        }

        $this->accountService->create($accountRequest);

        $this->counter++;
    }

    /**
     * Añadir una categoría y devolver el Id
     *
     * @param CategoryData $categoryData
     *
     * @return int
     * @throws DuplicatedItemException
     * @throws SPException
     */
    protected function addCategory(CategoryData $categoryData)
    {
        try {
            $categoryId = $this->getWorkingItem('category', $categoryData->getName());

            if ($categoryId === null) {
                return $this->categoryService->create($categoryData);
            }

            return $categoryId;
        } catch (DuplicatedItemException $e) {
            $itemData = $this->categoryService->getByName($categoryData->getName());

            if (empty($itemData)) {
                throw $e;
            }

            return $this->addWorkingItem('category', $itemData->getName(), $itemData->getId());
        }
    }

    /**
     * @param string $type
     * @param string $value
     *
     * @return int|null
     */
    protected function getWorkingItem($type, $value)
    {
        if (!isset($this->items[$type][$value])) {
            return null;
        }

        return $this->items[$type][$value];
    }

    /**
     * @param string $type
     * @param string $value
     * @param int    $id
     *
     * @return int|bool
     */
    protected function addWorkingItem($type, $value, $id)
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
     * @param ClientData $clientData
     *
     * @return int
     * @throws DuplicatedItemException
     * @throws SPException
     */
    protected function addClient(ClientData $clientData)
    {
        try {
            $clientId = $this->getWorkingItem('client', $clientData->getName());

            if ($clientId === null) {
                return $this->clientService->create($clientData);
            }

            return $clientId;
        } catch (DuplicatedItemException $e) {
            $itemData = $this->clientService->getByName($clientData->getName());

            if (empty($itemData)) {
                throw $e;
            }

            return $this->addWorkingItem('client', $itemData->getName(), $itemData->getId());
        }
    }

    /**
     * Añadir una etiqueta y devolver el Id
     *
     * @param TagData $tagData
     *
     * @return int
     * @throws SPException
     */
    protected function addTag(TagData $tagData)
    {
        try {
            $tagId = $this->getWorkingItem('tag', $tagData->getName());

            if ($tagId === null) {
                return $this->tagService->create($tagData);
            }

            return $tagId;
        } catch (DuplicatedItemException $e) {
            $itemData = $this->tagService->getByName($tagData->getName());

            if (empty($itemData)) {
                throw $e;
            }

            return $this->addWorkingItem('tag', $itemData->getName(), $itemData->getId());
        }
    }
}