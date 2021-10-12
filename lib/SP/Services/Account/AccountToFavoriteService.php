<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
    protected ?AccountToFavoriteRepository $accountFavoriteRepository = null;

    /**
     * Obtener un array con los Ids de cuentas favoritas
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForUserId(int $id): array
    {
        return $this->accountFavoriteRepository
            ->getForUserId($id)
            ->getDataAsArray();
    }

    /**
     * Añadir una cuenta a la lista de favoritos
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(int $accountId, int $userId): int
    {
        return $this->accountFavoriteRepository->add($accountId, $userId);
    }

    /**
     * Eliminar una cuenta de la lista de favoritos
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $accountId, int $userId): int
    {
        return $this->accountFavoriteRepository->delete($accountId, $userId);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize(): void
    {
        $this->accountFavoriteRepository = $this->dic->get(AccountToFavoriteRepository::class);
    }
}