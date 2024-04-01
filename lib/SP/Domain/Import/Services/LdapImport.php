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
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Domain\Auth\Ports\LdapService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Import\Dtos\LdapImportParamsDto;
use SP\Domain\Import\Dtos\LdapImportResultsDto;
use SP\Domain\Import\Ports\LdapImportService;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Providers\Auth\Ldap\LdapBase;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Auth\Ldap\LdapResults;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class LdapImport
 */
final class LdapImport extends Service implements LdapImportService
{
    public function __construct(
        Application                              $application,
        private readonly UserServiceInterface    $userService,
        private readonly UserGroupService        $userGroupService,
        private readonly LdapActionsService      $ldapActionsService,
        private readonly LdapConnectionInterface $ldapConnection
    ) {
        parent::__construct($application);
    }

    /**
     * Sincronizar usuarios de LDAP
     *
     * @throws LdapException
     */
    public function importGroups(LdapParams $ldapParams, LdapImportParamsDto $ldapImportParams): LdapImportResultsDto
    {
        $objects = $this->getObjects($ldapParams, $ldapImportParams, true);

        $importResults = new LdapImportResultsDto($objects->getCount());

        $this->eventDispatcher->notify(
            'import.ldap.groups',
            new Event($this, EventMessage::factory()->addDetail(__u('Objects found'), $objects->getCount()))
        );

        $iterator = $objects->getIterator();

        while ($iterator->valid()) {
            $entry = $iterator->current();
            $userGroup = [
                'name' => $entry[$ldapImportParams->getUserGroupNameAttribute()][0] ?? null,
                'description' => __('Imported from LDAP')
            ];

            if (!empty($userGroup['name'])) {
                try {
                    $this->userGroupService->create(new UserGroup($userGroup));

                    $this->eventDispatcher->notify(
                        'import.ldap.progress.groups',
                        new Event(
                            $this,
                            EventMessage::factory()
                                        ->addDetail(__u('Group'), sprintf('%s', $userGroup['name']))
                        )
                    );

                    $importResults->addSyncedObject();
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));

                    $importResults->addErrorObject();
                }
            }

            $iterator->next();
        }

        return $importResults;
    }

    /**
     * @throws LdapException
     */
    private function getObjects(
        LdapParams          $ldapParams,
        LdapImportParamsDto $ldapImportParams,
        bool                $isGroup = false
    ): LdapResults {
        $ldap = $this->getLdap($ldapParams);

        $useInputFilter = empty($ldapImportParams->getFilter());

        $filter = match (true) {
            $useInputFilter && $isGroup => $ldap->getGroupObjectFilter(),
            $useInputFilter && !$isGroup => $ldap->getGroupMembershipIndirectFilter(),
            default => $ldapImportParams->getFilter()
        };

        return $ldap->actions()->getObjects($filter);
    }

    /**
     * @throws LdapException
     */
    private function getLdap(LdapParams $ldapParams): LdapService
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
    public function importUsers(LdapParams $ldapParams, LdapImportParamsDto $ldapImportParams): LdapImportResultsDto
    {
        $objects = $this->getObjects($ldapParams, $ldapImportParams);

        $importResults = new LdapImportResultsDto($objects->getCount());

        $this->eventDispatcher->notify(
            'import.ldap.users',
            new Event($this, EventMessage::factory()->addDetail(__u('Objects found'), $objects->getCount()))
        );

        $iterator = $objects->getIterator();

        while ($iterator->valid()) {
            $entry = $iterator->current();

            $user = [
                'name' => $entry[$ldapImportParams->getUserNameAttribute()][0] ?? null,
                'login' => $entry[$ldapImportParams->getLoginAttribute()][0] ?? null,
                'email' => $entry['mail'][0] ?? null,
                'notes' => __('Imported from LDAP'),
                'userGroupId' => $ldapImportParams->getDefaultUserGroup(),
                'userProfileId' => $ldapImportParams->getDefaultUserProfile(),
                'isLdap' => true
            ];

            if (!empty($user['name']) && !empty($user['login'])) {
                try {
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

                    $importResults->addSyncedObject();
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));

                    $importResults->addErrorObject();
                }
            }

            $iterator->next();
        }

        return $importResults;
    }
}
