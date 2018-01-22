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

namespace SP\Services\PublicLink;

use SP\Core\Exceptions\SPException;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\ItemSearchData;
use SP\Repositories\PublicLink\PublicLinkRepository;
use SP\Services\ServiceItemTrait;

/**
 * Class PublicLinkService
 *
 * @package SP\Services\PublicLink
 */
class PublicLinkService
{
    use InjectableTrait;
    use ServiceItemTrait;

    /**
     * @var PublicLinkRepository
     */
    protected $publicLinkRepository;

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
     * @param PublicLinkRepository $publicLinkRepository
     */
    public function inject(PublicLinkRepository $publicLinkRepository)
    {
        $this->publicLinkRepository = $publicLinkRepository;
    }

    /**
     * @param ItemSearchData $itemSearchData
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->publicLinkRepository->search($itemSearchData);
    }

    /**
     * @param $id
     * @return \SP\DataModel\PublicLinkData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getById($id)
    {
        return $this->publicLinkRepository->getById($id);
    }

    /**
     * @param $id
     * @return bool
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function refresh($id)
    {
        return $this->publicLinkRepository->refresh($id);
    }

    /**
     * @param $id
     * @return $this
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if ($this->publicLinkRepository->delete($id) === 0) {
            throw new SPException(SPException::SP_INFO, __u('Enlace no encontrado'));
        }

        return $this;
    }

    /**
     * @param $itemData
     * @return int
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        return $this->publicLinkRepository->create($itemData);
    }

    /**
     * Get all items from the service's repository
     *
     * @return array
     */
    public function getAllBasic()
    {
        return $this->publicLinkRepository->getAll();
    }
}