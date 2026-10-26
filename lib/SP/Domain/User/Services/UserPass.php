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

namespace SP\Domain\User\Services;

use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserPassService;
use SP\Domain\User\Ports\UserRepository;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__u;

/**
 * Class UserPass
 */
final class UserPass extends Service implements UserPassService
{
    public function __construct(
        Application                     $application,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct($application);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function migrateUserPassById(int $id, string $userPass): void
    {
        $user = new User(
            [
                'id' => $id,
                'pass' => Hash::hashKey($userPass),
                'isChangePass' => false,
                'isChangedPass' => true,
                'isMigrate' => false
            ]
        );

        if ($this->userRepository->updatePassById($user) === 0) {
            throw NoSuchItemException::info(__u('User does not exist'));
        }
    }
}
