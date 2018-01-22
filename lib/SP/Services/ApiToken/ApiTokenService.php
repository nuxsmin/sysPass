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

namespace SP\Services\ApiToken;

use SP\Core\Exceptions\SPException;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\ItemSearchData;
use SP\Repositories\ApiToken\ApiTokenRepository;
use SP\Services\ServiceItemTrait;

/**
 * Class ApiTokenService
 *
 * @package SP\Services\ApiToken
 */
class ApiTokenService
{
    use InjectableTrait;
    use ServiceItemTrait;

    /**
     * @var ApiTokenRepository
     */
    protected $apiTokenRepository;

    /**
     * CategoryService constructor.
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * @param ApiTokenRepository $apiTokenRepository
     */
    public function inject(ApiTokenRepository $apiTokenRepository)
    {
        $this->apiTokenRepository = $apiTokenRepository;
    }

    /**
     * @param ItemSearchData $itemSearchData
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->apiTokenRepository->search($itemSearchData);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getById($id)
    {
        return $this->apiTokenRepository->getById($id);
    }

    /**
     * @param $id
     * @return ApiTokenService
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        if ($this->apiTokenRepository->delete($id) === 0) {
            throw new SPException(SPException::SP_INFO, __u('Token no encontrado'));
        }

        return $this;
    }

    /**
     * @param $itemData
     * @return mixed
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        return $this->apiTokenRepository->create($itemData);
    }

    /**
     * @param $itemData
     * @return mixed
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        return $this->apiTokenRepository->update($itemData);
    }


    /**
     * @return array
     */
    public function getAllBasic()
    {
        return $this->apiTokenRepository->getAll();
    }
}