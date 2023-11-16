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
use SP\Core\Acl\AclActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Exceptions\CheckException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Domain\Auth\Ports\LdapCheckServiceInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;
use SP\Mvc\View\TemplateInterface;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class CheckController
 */
final class CheckController extends SimpleControllerBase
{
    use ConfigLdapTrait;
    use JsonTrait;

    public function __construct(
        Application                                $application,
        SimpleControllerHelper                     $simpleControllerHelper,
        private readonly LdapCheckServiceInterface $ldapCheckService,
        private readonly TemplateInterface         $template
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    /**
     * @return bool
     * @throws JsonException
     * @throws SPException
     */
    public function checkAction(): bool
    {
        try {
            $ldapParams = $this->getLdapParamsFromRequest($this->request);

            // Valores para la configuración de LDAP
            if (!($ldapParams->getServer()
                  || $ldapParams->getSearchBase()
                  || $ldapParams->getBindDn())
            ) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('Missing LDAP parameters')
                );
            }

            $data = $this->ldapCheckService->getObjects(false, $ldapParams);

            $this->template->addTemplate('results', 'itemshow');
            $this->template->assign('header', __('Results'));

            return $this->returnJsonResponseData(
                ['template' => $this->template->render(), 'items' => $data['results']],
                JsonResponse::JSON_SUCCESS,
                __u('LDAP connection OK'),
                [sprintf(__('Objects found: %d'), $data['count'])]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return void
     * @throws SessionTimeout
     * @throws SPException
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(AclActionsInterface::CONFIG_LDAP);

            $this->extensionChecker->checkLdapAvailable(true);
        } catch (UnauthorizedPageException|CheckException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}
