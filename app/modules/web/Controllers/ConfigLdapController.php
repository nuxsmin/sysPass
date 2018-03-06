<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Mvc\View\Template;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Services\Ldap\LdapCheckService;
use SP\Services\Ldap\LdapImportParams;
use SP\Services\Ldap\LdapImportService;

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
        try {
            $eventMessage = EventMessage::factory();
            $configData = $this->config->getConfigData();

            // LDAP
            $ldapEnabled = Request::analyzeBool('ldap_enabled', false);
            $ldapDefaultGroup = Request::analyzeInt('ldap_defaultgroup');
            $ldapDefaultProfile = Request::analyzeInt('ldap_defaultprofile');

            $ldapParams = $this->getLdapParamsFromRequest();

            // Valores para la configuración de LDAP
            if ($ldapEnabled && !($ldapParams->getServer() || $ldapParams->getSearchBase() || $ldapParams->getBindDn())) {
                $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de LDAP'));
            }

            if ($ldapEnabled) {
                $configData->setLdapEnabled(true);
                $configData->setLdapAds($ldapParams->isAds());
                $configData->setLdapServer($ldapParams->getServer());
                $configData->setLdapBase($ldapParams->getSearchBase());
                $configData->setLdapGroup($ldapParams->getGroup());
                $configData->setLdapDefaultGroup($ldapDefaultGroup);
                $configData->setLdapDefaultProfile($ldapDefaultProfile);
                $configData->setLdapBindUser($ldapParams->getBindDn());
                $configData->setLdapBindPass($ldapParams->getBindPass());

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
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return LdapParams
     * @throws ValidationException
     */
    protected function getLdapParamsFromRequest()
    {
        $data = LdapParams::getServerAndPort(Request::analyzeString('ldap_server'));

        if ($data === false) {
            throw new ValidationException(__u('Parámetros de LDAP incorrectos'));
        }

        return (new LdapParams())
            ->setServer($data['server'])
            ->setPort(isset($data['port']) ? $data['port'] : 389)
            ->setSearchBase(Request::analyzeString('ldap_base'))
            ->setGroup(Request::analyzeString('ldap_group'))
            ->setBindDn(Request::analyzeString('ldap_binduser'))
            ->setBindPass(Request::analyzeEncrypted('ldap_bindpass'))
            ->setAds(Request::analyzeBool('ldap_ads', false));
    }

    /**
     * checkAction
     */
    public function checkAction()
    {
        try {
            $ldapParams = $this->getLdapParamsFromRequest();

            // Valores para la configuración de LDAP
            if (!($ldapParams->getServer() || $ldapParams->getSearchBase() || $ldapParams->getBindDn())) {
                $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de LDAP'));
            }

            $ldapCheckService = $this->dic->get(LdapCheckService::class);
            $ldapCheckService->checkConnection($ldapParams);

            $data = $ldapCheckService->getObjects();

            $template = $this->dic->get(Template::class);
            $template->addTemplate('results', 'itemshow');
            $template->assign('header', __('Resultados'));

            $this->returnJsonResponseData(
                ['template' => $template->render(), 'items' => $data['results']],
                JsonResponse::JSON_SUCCESS,
                __u('Conexión a LDAP correcta'),
                [sprintf(__('Objetos encontrados: %d'), $data['count'])]
            );
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
//            $this->JsonResponse->addMessage(__('Revise el registro de eventos para más detalles', false));
        }
    }

    /**
     * checkAction
     */
    public function checkImportAction()
    {
        try {
            $ldapParams = $this->getLdapParamsFromRequest();

            // Valores para la configuración de LDAP
            if (!($ldapParams->getServer() || $ldapParams->getSearchBase() || $ldapParams->getBindDn())) {
                $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de LDAP'));
            }

            $ldapCheckService = $this->dic->get(LdapCheckService::class);
            $ldapCheckService->checkConnection($ldapParams);

            $filter = Request::analyzeString('ldap_import_filter');

            if (empty($filter)) {
                $data = $ldapCheckService->getObjects(Request::analyzeBool('ldap_import_groups', false));
            } else {
                $data = $ldapCheckService->getObjectsByFilter($filter);
            }

            $template = $this->dic->get(Template::class);
            $template->addTemplate('results', 'itemshow');
            $template->assign('header', __('Resultados'));
            $template->assign('results', $data);

            $this->returnJsonResponseData(
                ['template' => $template->render(), 'items' => $data['results']],
                JsonResponse::JSON_SUCCESS,
                __u('Conexión a LDAP correcta'),
                [sprintf(__('Objetos encontrados: %d'), $data['count'])]
            );
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
//            $this->JsonResponse->addMessage(__('Revise el registro de eventos para más detalles', false));
        }
    }

    /**
     * importAction
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function importAction()
    {
        try {
            $ldapImportParams = new LdapImportParams();

            $ldapImportParams->filter = Request::analyzeString('ldap_import_filter');
            $ldapImportParams->loginAttribute = Request::analyzeString('ldap_login_attribute');
            $ldapImportParams->userNameAttribute = Request::analyzeString('ldap_username_attribute');
            $ldapImportParams->userGroupNameAttribute = Request::analyzeString('ldap_groupname_attribute');
            $ldapImportParams->defaultUserGroup = Request::analyzeInt('ldap_defaultgroup');
            $ldapImportParams->defaultUserProfile = Request::analyzeInt('ldap_defaultprofile');

            $checkImportGroups = Request::analyzeBool('ldap_import_groups', false);

            if ((empty($ldapImportParams->loginAttribute)
                    || empty($ldapImportParams->userNameAttribute)
                    || empty($ldapImportParams->defaultUserGroup)
                    || empty($ldapImportParams->defaultUserProfile))
                && ($checkImportGroups === true && empty($ldapImportParams->userGroupNameAttribute))
            ) {
                throw new ValidationException(__u('Parámetros de LDAP incorrectos'));
            }

            $ldapParams = $this->getLdapParamsFromRequest();

            $userLdapService = $this->dic->get(LdapImportService::class);

            $this->eventDispatcher->notifyEvent('import.ldap.start',
                new Event($this, EventMessage::factory()->addDescription(__u('Importación LDAP')))
            );

            $userLdapService->importUsers($ldapParams, $ldapImportParams);

            $filter = Request::analyzeString('ldap_import_filter');

            // Groups won't be imported if filter is set
            if ($checkImportGroups === true && empty($filter)) {
                $userLdapService->importGroups($ldapParams, $ldapImportParams);
            }

            $this->eventDispatcher->notifyEvent('import.ldap.end',
                new Event($this, EventMessage::factory()->addDescription(__u('Importación finalizada')))
            );

            if ($userLdapService->getTotalObjects() === 0) {
                throw new SPException(__u('No se encontraron objetos para sincronizar'));
            }

            $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Importación de usuarios de LDAP realizada'),
                [
                    sprintf(__('Usuarios importados: %d/%d'), $userLdapService->getSyncedObjects(), $userLdapService->getTotalObjects()),
                    sprintf(__('Errores: %d'), $userLdapService->getErrorObjects())

                ]
            );
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::LDAP_CONFIG);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}