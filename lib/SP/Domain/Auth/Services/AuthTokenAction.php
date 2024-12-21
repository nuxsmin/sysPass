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

namespace SP\Domain\Auth\Services;

use SP\Domain\Auth\Ports\AuthTokenActionService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;

/**
 * Class AuthTokenAction
 */
final readonly class AuthTokenAction implements AuthTokenActionService
{
    public function __construct(private AclInterface $acl)
    {
    }

    /**
     * Devuelver un array de acciones posibles para los tokens
     *
     * @return array
     */
    public function getTokenActions(): array
    {
        return [
            AclActionsInterface::ACCOUNT_SEARCH => $this->acl->getInfoFor(AclActionsInterface::ACCOUNT_SEARCH),
            AclActionsInterface::ACCOUNT_VIEW => $this->acl->getInfoFor(AclActionsInterface::ACCOUNT_VIEW),
            AclActionsInterface::ACCOUNT_VIEW_PASS => $this->acl->getInfoFor(AclActionsInterface::ACCOUNT_VIEW_PASS),
            AclActionsInterface::ACCOUNT_EDIT_PASS => $this->acl->getInfoFor(AclActionsInterface::ACCOUNT_EDIT_PASS),
            AclActionsInterface::ACCOUNT_DELETE => $this->acl->getInfoFor(AclActionsInterface::ACCOUNT_DELETE),
            AclActionsInterface::ACCOUNT_CREATE => $this->acl->getInfoFor(AclActionsInterface::ACCOUNT_CREATE),
            AclActionsInterface::ACCOUNT_EDIT => $this->acl->getInfoFor(AclActionsInterface::ACCOUNT_EDIT),
            AclActionsInterface::CATEGORY_SEARCH => $this->acl->getInfoFor(AclActionsInterface::CATEGORY_SEARCH),
            AclActionsInterface::CATEGORY_VIEW => $this->acl->getInfoFor(AclActionsInterface::CATEGORY_VIEW),
            AclActionsInterface::CATEGORY_CREATE => $this->acl->getInfoFor(AclActionsInterface::CATEGORY_CREATE),
            AclActionsInterface::CATEGORY_EDIT => $this->acl->getInfoFor(AclActionsInterface::CATEGORY_EDIT),
            AclActionsInterface::CATEGORY_DELETE => $this->acl->getInfoFor(AclActionsInterface::CATEGORY_DELETE),
            AclActionsInterface::CLIENT_SEARCH => $this->acl->getInfoFor(AclActionsInterface::CLIENT_SEARCH),
            AclActionsInterface::CLIENT_VIEW => $this->acl->getInfoFor(AclActionsInterface::CLIENT_VIEW),
            AclActionsInterface::CLIENT_CREATE => $this->acl->getInfoFor(AclActionsInterface::CLIENT_CREATE),
            AclActionsInterface::CLIENT_EDIT => $this->acl->getInfoFor(AclActionsInterface::CLIENT_EDIT),
            AclActionsInterface::CLIENT_DELETE => $this->acl->getInfoFor(AclActionsInterface::CLIENT_DELETE),
            AclActionsInterface::TAG_SEARCH => $this->acl->getInfoFor(AclActionsInterface::TAG_SEARCH),
            AclActionsInterface::TAG_VIEW => $this->acl->getInfoFor(AclActionsInterface::TAG_VIEW),
            AclActionsInterface::TAG_CREATE => $this->acl->getInfoFor(AclActionsInterface::TAG_CREATE),
            AclActionsInterface::TAG_EDIT => $this->acl->getInfoFor(AclActionsInterface::TAG_EDIT),
            AclActionsInterface::TAG_DELETE => $this->acl->getInfoFor(AclActionsInterface::TAG_DELETE),
            AclActionsInterface::GROUP_VIEW => $this->acl->getInfoFor(AclActionsInterface::GROUP_VIEW),
            AclActionsInterface::GROUP_CREATE => $this->acl->getInfoFor(AclActionsInterface::GROUP_CREATE),
            AclActionsInterface::GROUP_EDIT => $this->acl->getInfoFor(AclActionsInterface::GROUP_EDIT),
            AclActionsInterface::GROUP_DELETE => $this->acl->getInfoFor(AclActionsInterface::GROUP_DELETE),
            AclActionsInterface::GROUP_SEARCH => $this->acl->getInfoFor(AclActionsInterface::GROUP_SEARCH),
            AclActionsInterface::CONFIG_BACKUP_RUN => $this->acl->getInfoFor(AclActionsInterface::CONFIG_BACKUP_RUN),
            AclActionsInterface::CONFIG_EXPORT_RUN => $this->acl->getInfoFor(AclActionsInterface::CONFIG_EXPORT_RUN),
        ];
    }
}
