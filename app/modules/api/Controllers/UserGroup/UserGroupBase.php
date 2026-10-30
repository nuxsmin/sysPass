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

namespace SP\Modules\Api\Controllers\UserGroup;

use SP\Domain\Api\Ports\ApiService;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\User\UseCases\CreateUserGroupUseCase;
use SP\Domain\User\UseCases\DeleteUserGroupUseCase;
use SP\Domain\User\UseCases\GetUserGroupUseCase;
use SP\Domain\User\UseCases\SearchUserGroupsUseCase;
use SP\Domain\User\UseCases\UpdateUserGroupUseCase;
use SP\Modules\Api\Controllers\ApiControllerHelper;
use SP\Modules\Api\Controllers\ControllerBase;
use SP\Modules\Api\Controllers\Help\UserGroupHelp;

/**
 * Class UserGroupBase
 */
abstract class UserGroupBase extends ControllerBase
{
    /**
     * @throws InvalidClassException
     */
    public function __construct(
        ApiControllerHelper                        $controllerHelper,
        ApiService                                 $apiService,
        protected readonly GetUserGroupUseCase     $getUserGroupUseCase,
        protected readonly CreateUserGroupUseCase  $createUserGroupUseCase,
        protected readonly UpdateUserGroupUseCase  $updateUserGroupUseCase,
        protected readonly DeleteUserGroupUseCase  $deleteUserGroupUseCase,
        protected readonly SearchUserGroupsUseCase $searchUserGroupsUseCase
    ) {
        parent::__construct($controllerHelper, $apiService);

        $this->apiService->setHelpClass(UserGroupHelp::class);
    }
}
