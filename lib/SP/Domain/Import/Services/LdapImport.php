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

namespace SP\Domain\Import\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\User;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Domain\Auth\Ports\LdapService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Import\Ports\LdapImportService;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Domain\User\Services\UserGroupService;
use SP\Providers\Auth\Ldap\LdapBase;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class LdapImport
 */
final class LdapImport extends Service implements LdapImportService
{
    protected int $totalObjects  = 0;
    protected int $syncedObjects = 0;
    protected int $errorObjects  = 0;

    public function __construct(
        Application                              $application,
        private readonly UserServiceInterface    $userService,
        private readonly UserGroupService        $userGroupService,
        private readonly LdapActionsService      $ldapActionsService,
        private readonly LdapConnectionInterface $ldapConnection
    ) {
        parent::__construct($application);
    }


    public function getTotalObjects(): int
    {
        return $this->totalObjects;
    }

    public function getSyncedObjects(): int
    {
        return $this->syncedObjects;
    }

    public function getErrorObjects(): int
    {
        return $this->errorObjects;
    }

    /**
     * Sincronizar usuarios de LDAP
     *
     * @throws LdapException
     */
    public function importGroups(LdapParams $ldapParams, LdapImportParams $ldapImportParams): void
    {
        $ldap = $this->getLdap($ldapParams);

        if (empty($ldapImportParams->filter)) {
            $objects = $ldap->actions()->getObjects($ldap->getGroupObjectFilter());
        } else {
            $objects = $ldap->actions()->getObjects($ldapImportParams->filter);
        }

        $numObjects = (int)$objects['count'];

        $this->eventDispatcher->notify(
            'import.ldap.groups',
            new Event($this, EventMessage::factory()->addDetail(__u('Objects found'), $numObjects))
        );

        $this->totalObjects += $numObjects;

        if ($numObjects > 0) {
            foreach ($objects as $result) {
                if (is_array($result)) {
                    $userGroup = [];

                    foreach ($result as $attribute => $values) {
                        $value = $values[0];

                        if (strtolower($attribute) === $ldapImportParams->userGroupNameAttribute) {
                            $userGroup['name'] = $value;
                        }
                    }

                    if (!isset($userGroup['name'])) {
                        try {
                            $userGroup['description'] = __('Imported from LDAP');

                            $this->userGroupService->create(new UserGroup($userGroup));

                            $this->eventDispatcher->notify(
                                'import.ldap.progress.groups',
                                new Event(
                                    $this,
                                    EventMessage::factory()
                                                ->addDetail(__u('Group'), sprintf('%s', $userGroup['name']))
                                )
                            );

                            $this->syncedObjects++;
                        } catch (Exception $e) {
                            processException($e);

                            $this->eventDispatcher->notify('exception', new Event($e));

                            $this->errorObjects++;
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws LdapException
     */
    protected function getLdap(LdapParams $ldapParams): LdapService
    {
        return LdapBase::factory(
            $this->eventDispatcher,
            $this->ldapConnection,
            $this->ldapActionsService,
            $ldapParams,
        );
    }

    /**
     * @throws LdapException
     */
    public function importUsers(LdapParams $ldapParams, LdapImportParams $ldapImportParams): void
    {
        $ldap = $this->getLdap($ldapParams);

        if (empty($ldapImportParams->filter)) {
            $objects = $ldap->actions()->getObjects($ldap->getGroupMembershipIndirectFilter());
        } else {
            $objects = $ldap->actions()->getObjects($ldapImportParams->filter);
        }

        $numObjects = (int)$objects['count'];

        $this->eventDispatcher->notify(
            'import.ldap.users',
            new Event($this, EventMessage::factory()->addDetail(__u('Objects found'), $numObjects))
        );

        $this->totalObjects += $numObjects;

        if ($numObjects > 0) {
            foreach ($objects as $result) {
                if (is_array($result)) {
                    $user = [];

                    foreach ($result as $attribute => $values) {
                        switch (strtolower($attribute)) {
                            case $ldapImportParams->userNameAttribute:
                                $user['name'] = $values[0];
                                break;
                            case $ldapImportParams->loginAttribute:
                                $user['login'] = $values[0];
                                break;
                            case 'mail':
                                $user['email'] = $values[0];
                                break;
                        }
                    }

                    if (!isset($user['name'], $user['login'])) {
                        try {
                            $user['notes'] = __('Imported from LDAP');
                            $user['userGroupId'] = $ldapImportParams->defaultUserGroup;
                            $user['userProfileId'] = $ldapImportParams->defaultUserProfile;
                            $user['isLdap'] = true;

                            $this->userService->create(new User($user));

                            $this->eventDispatcher->notify(
                                'import.ldap.progress.users',
                                new Event(
                                    $this,
                                    EventMessage::factory()
                                        ->addDetail(
                                            __u('User'),
                                            sprintf('%s (%s)', $user['name'], $user['login'])
                                        )
                                )
                            );

                            $this->syncedObjects++;
                        } catch (Exception $e) {
                            processException($e);

                            $this->eventDispatcher->notify('exception', new Event($e));

                            $this->errorObjects++;
                        }
                    }
                }
            }
        }
    }
}
