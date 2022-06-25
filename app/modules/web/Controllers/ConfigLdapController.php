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

namespace SP\Modules\Web\Controllers;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\CheckException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Mvc\View\Template;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Auth\Ldap\LdapTypeInterface;
use SP\Services\Ldap\LdapCheckService;
use SP\Services\Ldap\LdapImportParams;
use SP\Services\Ldap\LdapImportService;

/**
 * Class ConfigLdapController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ConfigLdapController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * saveAction
     */
    public function saveAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $eventMessage = EventMessage::factory();
            $configData = $this->config->getConfigData();

            // LDAP
            $ldapEnabled = $this->request->analyzeBool('ldap_enabled', false);
            $ldapDefaultGroup = $this->request->analyzeInt('ldap_defaultgroup');
            $ldapDefaultProfile = $this->request->analyzeInt('ldap_defaultprofile');

            $ldapParams = $this->getLdapParamsFromRequest();

            // Valores para la configuración de LDAP
            if ($ldapEnabled && !($ldapParams->getServer() || $ldapParams->getSearchBase() || $ldapParams->getBindDn())) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Missing LDAP parameters'));
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

                if ($ldapParams->getBindPass() !== '***') {
                    $configData->setLdapBindPass($ldapParams->getBindPass());
                }

                if ($configData->isLdapEnabled() === false) {
                    $eventMessage->addDescription(__u('LDAP enabled'));
                }
            } elseif ($ldapEnabled === false && $configData->isLdapEnabled()) {
                $configData->setLdapEnabled(false);

                $eventMessage->addDescription(__u('LDAP disabled'));
            } else {
                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('No changes'));
            }

            return $this->saveConfig($configData, $this->config, function () use ($eventMessage) {
                $this->eventDispatcher->notifyEvent('save.config.ldap', new Event($this, $eventMessage));
            });
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return LdapParams
     * @throws ValidationException
     */
    protected function getLdapParamsFromRequest()
    {
        $data = LdapParams::getServerAndPort($this->request->analyzeString('ldap_server'));

        if ($data === false) {
            throw new ValidationException(__u('Wrong LDAP parameters'));
        }

        return (new LdapParams())
            ->setServer($data['server'])
            ->setPort(isset($data['port']) ? $data['port'] : 389)
            ->setSearchBase($this->request->analyzeString('ldap_base'))
            ->setGroup($this->request->analyzeString('ldap_group'))
            ->setBindDn($this->request->analyzeString('ldap_binduser'))
            ->setBindPass($this->request->analyzeEncrypted('ldap_bindpass'))
            ->setType($this->request->analyzeInt('ldap_server_type', LdapTypeInterface::LDAP_STD))
            ->setTlsEnabled($this->request->analyzeBool('ldap_tls_enabled', false));
    }

    /**
     * checkAction
     */
    public function checkAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $ldapParams = $this->getLdapParamsFromRequest();

            // Valores para la configuración de LDAP
            if (!($ldapParams->getServer() || $ldapParams->getSearchBase() || $ldapParams->getBindDn())) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Missing LDAP parameters'));
            }

            $ldapCheckService = $this->dic->get(LdapCheckService::class);
            $ldapCheckService->checkConnection($ldapParams);

            $data = $ldapCheckService->getObjects(false);

            $template = $this->dic->get(Template::class);
            $template->addTemplate('results', 'itemshow');
            $template->assign('header', __('Results'));

            return $this->returnJsonResponseData(
                ['template' => $template->render(), 'items' => $data['results']],
                JsonResponse::JSON_SUCCESS,
                __u('LDAP connection OK'),
                [sprintf(__('Objects found: %d'), $data['count'])]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * checkAction
     */
    public function checkImportAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $ldapParams = $this->getLdapParamsFromRequest();

            // Valores para la configuración de LDAP
            if (!($ldapParams->getServer() || $ldapParams->getSearchBase() || $ldapParams->getBindDn())) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Missing LDAP parameters'));
            }

            $ldapCheckService = $this->dic->get(LdapCheckService::class);
            $ldapCheckService->checkConnection($ldapParams);

            $filter = $this->request->analyzeString('ldap_import_filter');

            if (empty($filter)) {
                $data = $ldapCheckService->getObjects($this->request->analyzeBool('ldap_import_groups', false));
            } else {
                $data = $ldapCheckService->getObjectsByFilter($filter);
            }

            $template = $this->dic->get(Template::class);
            $template->addTemplate('results', 'itemshow');
            $template->assign('header', __('Results'));
            $template->assign('results', $data);

            return $this->returnJsonResponseData(
                ['template' => $template->render(), 'items' => $data['results']],
                JsonResponse::JSON_SUCCESS,
                __u('LDAP connection OK'),
                [sprintf(__('Objects found: %d'), $data['count'])]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * importAction
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function importAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if ($this->configData->isDemoEnabled()) {
                return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, this is a DEMO!!'));
            }

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

            $ldapParams = $this->getLdapParamsFromRequest();

            $this->eventDispatcher->notifyEvent('import.ldap.start',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('LDAP Import')))
            );

            $userLdapService = $this->dic->get(LdapImportService::class);
            $userLdapService->importUsers($ldapParams, $ldapImportParams);

            if ($checkImportGroups === true) {
                $userLdapService->importGroups($ldapParams, $ldapImportParams);
            }

            $this->eventDispatcher->notifyEvent('import.ldap.end',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Import finished')))
            );

            if ($userLdapService->getTotalObjects() === 0) {
                throw new SPException(__u('There aren\'t any objects to synchronize'));
            }

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('LDAP users import finished'),
                [
                    sprintf(__('Imported users: %d / %d'), $userLdapService->getSyncedObjects(), $userLdapService->getTotalObjects()),
                    sprintf(__('Errors: %d'), $userLdapService->getErrorObjects())

                ]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return bool
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(Acl::CONFIG_LDAP);

            $this->extensionChecker->checkLdapAvailable(true);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        } catch (CheckException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }

        return true;
    }
}
