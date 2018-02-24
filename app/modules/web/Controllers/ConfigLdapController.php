<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

/**
 * Class ConfigLdapController
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigLdapController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * saveAction
     */
    public function saveAction()
    {
        $eventMessage = EventMessage::factory();
        $configData = clone $this->config->getConfigData();

        // LDAP
        $ldapEnabled = Request::analyze('ldap_enabled', false, false, true);
        $ldapADSEnabled = Request::analyze('ldap_ads', false, false, true);
        $ldapServer = Request::analyze('ldap_server');
        $ldapBase = Request::analyze('ldap_base');
        $ldapGroup = Request::analyze('ldap_group');
        $ldapDefaultGroup = Request::analyze('ldap_defaultgroup', 0);
        $ldapDefaultProfile = Request::analyze('ldap_defaultprofile', 0);
        $ldapBindUser = Request::analyze('ldap_binduser');
        $ldapBindPass = Request::analyzeEncrypted('ldap_bindpass');

        // Valores para la configuración de LDAP
        if ($ldapEnabled && (!$ldapServer || !$ldapBase || !$ldapBindUser)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de LDAP'));
        }

        if ($ldapEnabled) {
            $configData->setLdapEnabled(true);
            $configData->setLdapAds($ldapADSEnabled);
            $configData->setLdapServer($ldapServer);
            $configData->setLdapBase($ldapBase);
            $configData->setLdapGroup($ldapGroup);
            $configData->setLdapDefaultGroup($ldapDefaultGroup);
            $configData->setLdapDefaultProfile($ldapDefaultProfile);
            $configData->setLdapBindUser($ldapBindUser);
            $configData->setLdapBindPass($ldapBindPass);

            if ($configData->isLdapEnabled() === false) {
                $eventMessage->addDescription(__u('LDAP habiltado'));
            }
        } elseif ($ldapEnabled === false && $configData->isLdapEnabled()) {
            $configData->setLdapEnabled(false);

            $eventMessage->addDescription(__u('LDAP deshabilitado'));
        }

        $this->saveConfig($configData, $this->config, function () use ($eventMessage) {
            $this->eventDispatcher->notifyEvent('save.config.ldap', new Event($this, $eventMessage));
        });
    }

    protected function initialize()
    {
        try {
            $this->checkAccess(ActionsInterface::LDAP_CONFIG);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}