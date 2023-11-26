<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

/**
 * Class ConfigLdapController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveController extends SimpleControllerBase
{
    use ConfigLdapTrait;
    use ConfigTrait;

    /**
     * @return bool
     * @throws JsonException
     */
    public function saveAction(): bool
    {
        try {
            $eventMessage = EventMessage::factory();
            $configData = $this->config->getConfigData();

            $ldapEnabled = $this->request->analyzeBool('ldap_enabled', false);
            $ldapDefaultGroup = $this->request->analyzeInt('ldap_defaultgroup');
            $ldapDefaultProfile = $this->request->analyzeInt('ldap_defaultprofile');

            $ldapParams = $this->getLdapParamsFromRequest($this->request);

            if ($ldapEnabled
                && !($ldapParams->getServer() || $ldapParams->getSearchBase() || $ldapParams->getBindDn())) {
                throw new ValidationException(SPException::ERROR, __u('Missing LDAP parameters'));
            }

            if ($ldapEnabled) {
                $configData->setLdapEnabled(true);
                $configData->setLdapType($ldapParams->getType());
                $configData->setLdapTlsEnabled($ldapParams->isTlsEnabled());
                $configData->setLdapServer($this->request->analyzeString('ldap_server'));
                $configData->setLdapBase($ldapParams->getSearchBase());
                $configData->setLdapGroup($ldapParams->getGroup());
                $configData->setLdapDefaultGroup($ldapDefaultGroup);
                $configData->setLdapDefaultProfile($ldapDefaultProfile);
                $configData->setLdapBindUser($ldapParams->getBindDn());
                $configData->setLdapFilterUserObject($ldapParams->getFilterUserObject());
                $configData->setLdapFilterGroupObject($ldapParams->getFilterGroupObject());
                $configData->setLdapFilterUserAttributes($ldapParams->getFilterUserAttributes());
                $configData->setLdapFilterGroupAttributes($ldapParams->getFilterGroupAttributes());

                $databaseEnabled = $this->request->analyzeBool('ldap_database_enabled', false);
                $configData->setLdapDatabaseEnabled($databaseEnabled);

                if ($ldapParams->getBindPass() !== '***') {
                    $configData->setLdapBindPass($ldapParams->getBindPass());
                }

                if ($configData->isLdapEnabled() === false) {
                    $eventMessage->addDescription(__u('LDAP enabled'));
                }
            } elseif ($configData->isLdapEnabled()) {
                $configData->setLdapEnabled(false);

                $eventMessage->addDescription(__u('LDAP disabled'));
            } else {
                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('No changes'));
            }

            return $this->saveConfig(
                $configData,
                $this->config,
                function () use ($eventMessage) {
                    $this->eventDispatcher->notify('save.config.ldap', new Event($this, $eventMessage));
                }
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
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
