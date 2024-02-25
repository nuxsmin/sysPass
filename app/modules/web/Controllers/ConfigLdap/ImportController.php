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

namespace SP\Modules\Web\Controllers\ConfigLdap;


use Exception;
use JsonException;
use Klein\Klein;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\PhpExtensionChecker;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Import\Ports\LdapImportService;
use SP\Domain\Import\Services\LdapImportParams;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class ImportController
 */
final class ImportController extends SimpleControllerBase
{
    use ConfigLdapTrait;
    use JsonTrait;

    private LdapImportService $ldapImportService;

    public function __construct(
        Application         $application,
        ThemeInterface      $theme,
        Klein               $router,
        AclInterface        $acl,
        RequestInterface    $request,
        PhpExtensionChecker $extensionChecker,
        LdapImportService $ldapImportService
    ) {
        parent::__construct($application, $theme);

        $this->ldapImportService = $ldapImportService;
    }

    /**
     * importAction
     *
     * @return bool
     * @throws JsonException
     */
    public function importAction(): bool
    {
        try {
            if ($this->configData->isDemoEnabled()) {
                return $this->returnJsonResponse(JsonMessage::JSON_WARNING, __u('Ey, this is a DEMO!!'));
            }

            [$ldapImportParams, $checkImportGroups] = $this->getImportParams();

            $ldapParams = $this->getLdapParamsFromRequest($this->request);

            $this->eventDispatcher->notify(
                'import.ldap.start',
                new Event($this, EventMessage::factory()->addDescription(__u('LDAP Import')))
            );

            $this->ldapImportService->importUsers($ldapParams, $ldapImportParams);

            if ($checkImportGroups === true) {
                $this->ldapImportService->importGroups($ldapParams, $ldapImportParams);
            }

            $this->eventDispatcher->notify(
                'import.ldap.end',
                new Event($this, EventMessage::factory()->addDescription(__u('Import finished')))
            );

            if ($this->ldapImportService->getTotalObjects() === 0) {
                throw new SPException(__u('There aren\'t any objects to synchronize'));
            }

            return $this->returnJsonResponse(
                JsonMessage::JSON_SUCCESS,
                __u('LDAP users import finished'),
                [
                    sprintf(
                        __('Imported users: %d / %d'),
                        $this->ldapImportService->getSyncedObjects(),
                        $this->ldapImportService->getTotalObjects()
                    ),
                    sprintf(__('Errors: %d'), $this->ldapImportService->getErrorObjects()),

                ]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return array
     * @throws ValidationException
     */
    private function getImportParams(): array
    {
        $ldapImportParams = new LdapImportParams();

        $ldapImportParams->filter = $this->request->analyzeString('ldap_import_filter');
        $ldapImportParams->loginAttribute = $this->request->analyzeString('ldap_login_attribute');
        $ldapImportParams->userNameAttribute = $this->request->analyzeString('ldap_username_attribute');
        $ldapImportParams->userGroupNameAttribute = $this->request->analyzeString('ldap_groupname_attribute');
        $ldapImportParams->defaultUserGroup = $this->request->analyzeInt('ldap_defaultgroup');
        $ldapImportParams->defaultUserProfile = $this->request->analyzeInt('ldap_defaultprofile');

        $checkImportGroups = $this->request->analyzeBool('ldap_import_groups', false);

        if ((empty($ldapImportParams->loginAttribute)
             || empty($ldapImportParams->userNameAttribute)
             || empty($ldapImportParams->defaultUserGroup)
             || empty($ldapImportParams->defaultUserProfile))
            && ($checkImportGroups === true && empty($ldapImportParams->userGroupNameAttribute))
        ) {
            throw new ValidationException(__u('Wrong LDAP parameters'));
        }

        return array($ldapImportParams, $checkImportGroups);
    }

    /**
     * @return void
     * @throws JsonException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(AclActionsInterface::CONFIG_LDAP);

            $this->extensionChecker->checkLdap(true);
        } catch (UnauthorizedPageException|CheckException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}
