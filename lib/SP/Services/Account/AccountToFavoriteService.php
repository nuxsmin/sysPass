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

namespace SP\Services\Account;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Repositories\Account\AccountToFavoriteRepository;
use SP\Services\Service;

/**
 * Class AccountFavoriteService
 *
 * @package SP\Services\Account
 */
final class AccountToFavoriteService extends Service
{
    /**
     * @var AccountToFavoriteRepository
     */
    protected $accountFavoriteRepository;

    /**
     * Obtener un array con los Ids de cuentas favoritas
     *
     * @param $id int El Id de usuario
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUserId($id)
    {
        return $this->accountFavoriteRepository->getForUserId($id)->getDataAsArray();
    }

    /**
     * Añadir una cuenta a la lista de favoritos
     *
     * @param $accountId int El Id de la cuenta
     * @param $userId    int El Id del usuario
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add($accountId, $userId)
    {
        return $this->accountFavoriteRepository->add($accountId, $userId);
    }

    /**
     * Eliminar una cuenta de la lista de favoritos
     *
     * @param $accountId int El Id de la cuenta
     * @param $userId    int El Id del usuario
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($accountId, $userId)
    {
        return $this->accountFavoriteRepository->delete($accountId, $userId);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->accountFavoriteRepository = $this->dic->get(AccountToFavoriteRepository::class);
    }
}