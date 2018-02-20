<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Services\Import;

use SP\Account\AccountRequest;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\SPException;
use SP\Core\OldCrypt;
use SP\DataModel\CategoryData;
use SP\DataModel\ClientData;
use SP\DataModel\TagData;
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
     * @throws ImportException
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Dic\ContainerException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function addAccount(AccountRequest $accountRequest)
    {
        if ($accountRequest->categoryId === 0) {
            throw new ImportException(__u('Id de categoría no definido. No es posible importar cuenta.'));
        }

        if ($accountRequest->clientId === 0) {
            throw new ImportException(__u('Id de cliente no definido. No es posible importar cuenta.'));
        }

        $accountRequest->userId = $this->importParams->getDefaultUser();
        $accountRequest->userGroupId = $this->importParams->getDefaultGroup();

        if ($this->mPassValidHash === false && $this->importParams->getImportMasterPwd() !== '') {
            if ($this->version >= 210) {
                $securedKey = Crypt::unlockSecuredKey($accountRequest->key, $this->importParams->getImportMasterPwd());
                $pass = Crypt::decrypt($accountRequest->pass, $securedKey, $this->importParams->getImportMasterPwd());
            } else {
                $pass = OldCrypt::getDecrypt($accountRequest->pass, $accountRequest->key, $this->importParams->getImportMasterPwd());
            }

            $accountRequest->pass = $pass;
            $accountRequest->key = '';
        }

        $this->accountService->create($accountRequest);

//            $this->LogMessage->addDetails(__('Cuenta creada', false), $accountRequest->name);
        $this->counter++;
    }

    /**
     * Añadir una categoría y devolver el Id
     *
     * @param CategoryData $categoryData
     * @return int
     * @throws SPException
     */
    protected function addCategory(CategoryData $categoryData)
    {
        return $this->categoryService->create($categoryData);
    }

    /**
     * Añadir un cliente y devolver el Id
     *
     * @param ClientData $clientData
     * @return int
     * @throws SPException
     */
    protected function addClient(ClientData $clientData)
    {
        return $this->clientService->create($clientData);
    }

    /**
     * Añadir una etiqueta y devolver el Id
     *
     * @param TagData $tagData
     * @return int
     * @throws SPException
     */
    protected function addTag(TagData $tagData)
    {
        return $this->tagService->create($tagData);
    }
}