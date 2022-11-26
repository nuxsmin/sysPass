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

namespace SP\Domain\Import\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\UserData;
use SP\DataModel\UserGroupData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Import\Ports\LdapImportServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Domain\User\Services\UserGroupService;
use SP\Providers\Auth\Ldap\Ldap;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapInterface;
use SP\Providers\Auth\Ldap\LdapParams;

/**
 * Class UserLdapService
 *
 * @package SP\Domain\User\Services
 */
final class LdapImportService extends Service implements LdapImportServiceInterface
{
    protected int                $totalObjects  = 0;
    protected int                $syncedObjects = 0;
    protected int                $errorObjects  = 0;
    private UserServiceInterface $userService;
    private UserGroupService     $userGroupService;

    public function __construct(
        Application $application,
        UserServiceInterface $userService,
        UserGroupService $userGroupService
    ) {
        parent::__construct($application);

        $this->userService = $userService;
        $this->userGroupService = $userGroupService;
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
    public function importGroups(
        LdapParams $ldapParams,
        LdapImportParams $ldapImportParams
    ): void {
        $ldap = $this->getLdap($ldapParams);

        if (empty($ldapImportParams->filter)) {
            $objects = $ldap->getLdapActions()->getObjects($ldap->getGroupObjectFilter());
        } else {
            $objects = $ldap->getLdapActions()->getObjects($ldapImportParams->filter);
        }

        $numObjects = (int)$objects['count'];

        $this->eventDispatcher->notifyEvent(
            'import.ldap.groups',
            new Event($this, EventMessage::factory()->addDetail(__u('Objects found'), $numObjects))
        );

        $this->totalObjects += $numObjects;

        if ($numObjects > 0) {
            foreach ($objects as $result) {
                if (is_array($result)) {
                    $userGroupData = new UserGroupData();

                    foreach ($result as $attribute => $values) {

                        $value = $values[0];

                        if (strtolower($attribute) === $ldapImportParams->userGroupNameAttribute) {
                            $userGroupData->setName($value);
                        }
                    }

                    if (!empty($userGroupData->getName())) {
                        try {
                            $userGroupData->setDescription(__('Imported from LDAP'));

                            $this->userGroupService->create($userGroupData);

                            $this->eventDispatcher->notifyEvent(
                                'import.ldap.progress.groups',
                                new Event(
                                    $this,
                                    EventMessage::factory()->addDetail(
                                        __u('Group'),
                                        sprintf('%s', $userGroupData->getName())
                                    )
                                )
                            );

                            $this->syncedObjects++;
                        } catch (Exception $e) {
                            processException($e);

                            $this->eventDispatcher->notifyEvent('exception', new Event($e));

                            $this->errorObjects++;
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws \SP\Providers\Auth\Ldap\LdapException
     */
    protected function getLdap(LdapParams $ldapParams): LdapInterface
    {
        return Ldap::factory(
            $ldapParams,
            $this->eventDispatcher,
            $this->config->getConfigData()->isDebug()
        );
    }

    /**
     * @throws LdapException
     */
    public function importUsers(
        LdapParams $ldapParams,
        LdapImportParams $ldapImportParams
    ): void {
        $ldap = $this->getLdap($ldapParams);

        if (empty($ldapImportParams->filter)) {
            $objects = $ldap->getLdapActions()->getObjects($ldap->getGroupMembershipIndirectFilter());
        } else {
            $objects = $ldap->getLdapActions()->getObjects($ldapImportParams->filter);
        }

        $numObjects = (int)$objects['count'];

        $this->eventDispatcher->notifyEvent(
            'import.ldap.users',
            new Event($this, EventMessage::factory()->addDetail(__u('Objects found'), $numObjects))
        );

        $this->totalObjects += $numObjects;

        if ($numObjects > 0) {
            foreach ($objects as $result) {
                if (is_array($result)) {
                    $userData = new UserData();

                    foreach ($result as $attribute => $values) {

                        $value = $values[0];

                        switch (strtolower($attribute)) {
                            case $ldapImportParams->userNameAttribute:
                                $userData->setName($value);
                                break;
                            case $ldapImportParams->loginAttribute:
                                $userData->setLogin($value);
                                break;
                            case 'mail':
                                $userData->setEmail($value);
                                break;
                        }
                    }

                    if (!empty($userData->getName())
                        && !empty($userData->getLogin())
                    ) {
                        try {
                            $userData->setNotes(__('Imported from LDAP'));
                            $userData->setUserGroupId($ldapImportParams->defaultUserGroup);
                            $userData->setUserProfileId($ldapImportParams->defaultUserProfile);
                            $userData->setIsLdap(true);

                            $this->userService->create($userData);

                            $this->eventDispatcher->notifyEvent(
                                'import.ldap.progress.users',
                                new Event(
                                    $this,
                                    EventMessage::factory()
                                        ->addDetail(
                                            __u('User'),
                                            sprintf('%s (%s)', $userData->getName(), $userData->getLogin())
                                        )
                                )
                            );

                            $this->syncedObjects++;
                        } catch (Exception $e) {
                            processException($e);

                            $this->eventDispatcher->notifyEvent('exception', new Event($e));

                            $this->errorObjects++;
                        }
                    }
                }
            }
        }
    }
}
