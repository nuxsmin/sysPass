<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Domain\Account\AccountToFavoriteServiceInterface;
use SP\Domain\Account\In\AccountToFavoriteRepositoryInterface;
use SP\Domain\Common\Services\Service;

/**
 * Class AccountFavoriteService
 *
 * @package SP\Domain\Account\Services
 */
final class AccountToFavoriteService extends Service implements AccountToFavoriteServiceInterface
{
    private AccountToFavoriteRepositoryInterface $accountFavoriteRepository;

    public function __construct(
        Application $application,
        AccountToFavoriteRepositoryInterface $accountFavoriteRepository
    ) {
        $this->accountFavoriteRepository = $accountFavoriteRepository;

        parent::__construct($application);
    }

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
    public function delete(int $accountId, int $userId): bool
    {
        return $this->accountFavoriteRepository->delete($accountId, $userId);
    }
}