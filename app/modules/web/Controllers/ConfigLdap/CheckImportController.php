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

namespace SP\Modules\Web\Controllers\ConfigLdap;


use Exception;
use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Exceptions\CheckException;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Auth\LdapCheckServiceInterface;
use SP\Http\JsonResponse;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\View\TemplateInterface;

/**
 * Class CheckImportController
 */
final class CheckImportController extends SimpleControllerBase
{
    use JsonTrait, ConfigLdapTrait;

    private LdapCheckServiceInterface $ldapCheckService;
    private TemplateInterface         $template;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        LdapCheckServiceInterface $ldapCheckService,
        TemplateInterface $template
    ) {
        parent::__construct($application, $theme, $router, $acl, $request, $extensionChecker);

        $this->ldapCheckService = $ldapCheckService;
        $this->template = $template;
    }

    /**
     * @return bool
     * @throws \JsonException
     */
    public function checkImportAction(): bool
    {
        try {
            $ldapParams = $this->getLdapParamsFromRequest($this->request);

            // Valores para la configuración de LDAP
            if (!($ldapParams->getServer()
                  || $ldapParams->getSearchBase()
                  || $ldapParams->getBindDn())
            ) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Missing LDAP parameters'));
            }

            $this->ldapCheckService->checkConnection($ldapParams);

            $filter = $this->request->analyzeString('ldap_import_filter');

            if (empty($filter)) {
                $data = $this->ldapCheckService->getObjects($this->request->analyzeBool('ldap_import_groups', false));
            } else {
                $data = $this->ldapCheckService->getObjectsByFilter($filter);
            }

            $this->template->addTemplate('results', 'itemshow');
            $this->template->assign('header', __('Results'));
            $this->template->assign('results', $data);

            return $this->returnJsonResponseData(
                ['template' => $this->template->render(), 'items' => $data['results']],
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
     * @return void
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::CONFIG_LDAP);

            $this->extensionChecker->checkLdapAvailable(true);
        } catch (UnauthorizedPageException|CheckException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}