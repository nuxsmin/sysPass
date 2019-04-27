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

namespace SP\Services\Ldap;

use Exception;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\UserData;
use SP\DataModel\UserGroupData;
use SP\Providers\Auth\Ldap\Ldap;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapInterface;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Services\Service;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;

/**
 * Class UserLdapService
 *
 * @package SP\Services\User
 */
final class LdapImportService extends Service
{
    /**
     * @var int
     */
    protected $totalObjects = 0;
    /**
     * @var int
     */
    protected $syncedObjects = 0;
    /**
     * @var int
     */
    protected $errorObjects = 0;

    /**
     * @return int
     */
    public function getTotalObjects()
    {
        return $this->totalObjects;
    }

    /**
     * @return int
     */
    public function getSyncedObjects()
    {
        return $this->syncedObjects;
    }

    /**
     * @return int
     */
    public function getErrorObjects()
    {
        return $this->errorObjects;
    }

    /**
     * Sincronizar usuarios de LDAP
     *
     * @param LdapParams       $ldapParams
     * @param LdapImportParams $ldapImportParams
     *
     * @throws LdapException
     */
    public function importGroups(LdapParams $ldapParams, LdapImportParams $ldapImportParams)
    {
        $ldap = $this->getLdap($ldapParams);

        if (empty($ldapImportParams->filter)) {
            $objects = $ldap->getLdapActions()
                ->getObjects($ldap->getGroupObjectFilter());
        } else {
            $objects = $ldap->getLdapActions()
                ->getObjects($ldapImportParams->filter);
        }

        $numObjects = (int)$objects['count'];

        $this->eventDispatcher->notifyEvent('import.ldap.groups',
            new Event($this, EventMessage::factory()
                ->addDetail(__u('Objects found'), $numObjects))
        );

        $this->totalObjects += $numObjects;

        if ($numObjects > 0) {
            $userGroupService = $this->dic->get(UserGroupService::class);

            foreach ($objects as $result) {
                if (is_array($result)) {
                    $userGroupData = new UserGroupData();

                    foreach ($result as $attribute => $values) {

                        $value = $values[0];

                        switch (strtolower($attribute)) {
                            case $ldapImportParams->userGroupNameAttribute:
                                $userGroupData->setName($value);
                                break;
                        }
                    }

                    if (!empty($userGroupData->getName())) {
                        try {
                            $userGroupData->setDescription(__('Imported from LDAP'));

                            $userGroupService->create($userGroupData);

                            $this->eventDispatcher->notifyEvent('import.ldap.progress.groups',
                                new Event($this, EventMessage::factory()
                                    ->addDetail(__u('Group'), sprintf('%s', $userGroupData->getName())))
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
     * @param LdapParams $ldapParams
     *
     * @return LdapInterface
     * @throws LdapException
     */
    protected function getLdap(LdapParams $ldapParams)
    {
        return Ldap::factory($ldapParams, $this->eventDispatcher, $this->config->getConfigData()->isDebug());
    }

    /**
     * @param LdapParams       $ldapParams
     * @param LdapImportParams $ldapImportParams
     *
     * @throws LdapException
     */
    public function importUsers(LdapParams $ldapParams, LdapImportParams $ldapImportParams)
    {
        $ldap = $this->getLdap($ldapParams);

        if (empty($ldapImportParams->filter)) {
            $objects = $ldap->getLdapActions()
                ->getObjects($ldap->getGroupMembershipFilter());
        } else {
            $objects = $ldap->getLdapActions()
                ->getObjects($ldapImportParams->filter);
        }

        $numObjects = (int)$objects['count'];

        $this->eventDispatcher->notifyEvent('import.ldap.users',
            new Event($this, EventMessage::factory()
                ->addDetail(__u('Objects found'), $numObjects))
        );

        $this->totalObjects += $numObjects;

        if ($numObjects > 0) {
            $userService = $this->dic->get(UserService::class);

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

                            $userService->create($userData);

                            $this->eventDispatcher->notifyEvent('import.ldap.progress.users',
                                new Event($this, EventMessage::factory()
                                    ->addDetail(__u('User'), sprintf('%s (%s)', $userData->getName(), $userData->getLogin())))
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