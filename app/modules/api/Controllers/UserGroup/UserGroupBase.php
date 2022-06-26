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

namespace SP\Modules\Api\Controllers\UserGroup;

use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Domain\Api\ApiServiceInterface;
use SP\Domain\User\UserGroupServiceInterface;
use SP\Modules\Api\Controllers\ControllerBase;
use SP\Modules\Api\Controllers\Help\TagHelp;

/**
 * Class UserGroupBase
 */
abstract class UserGroupBase extends ControllerBase
{
    protected UserGroupServiceInterface $userGroupService;

    /**
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function __construct(
        Application $application,
        Klein $router,
        ApiServiceInterface $apiService,
        Acl $acl,
        UserGroupServiceInterface $groupService
    ) {
        parent::__construct($application, $router, $apiService, $acl);

        $this->userGroupService = $groupService;

        $this->apiService->setHelpClass(TagHelp::class);
    }
}