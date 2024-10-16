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

use SP\Core\Application;
use SP\Domain\Auth\Ports\LdapCheckService;
use SP\Domain\Auth\Providers\Ldap\LdapParams;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;
use SP\Mvc\View\TemplateInterface;

use function SP\__;
use function SP\__u;

/**
 * Class CheckController
 */
final class CheckController extends SimpleControllerBase
{

    public function __construct(
        Application                        $application,
        SimpleControllerHelper             $simpleControllerHelper,
        private readonly LdapCheckService  $ldapCheckService,
        private readonly TemplateInterface $template
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    /**
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function checkAction(bool $useConfigParams): ActionResponse
    {
        if ($useConfigParams) {
            $ldapParams = LdapParams::fromConfig($this->configData);
        } else {
            $ldapParams = LdapParams::fromRequest($this->request);
        }

        $data = $this->ldapCheckService->getObjects($ldapParams, false);

        $this->template->addTemplate('results', 'itemshow');
        $this->template->assign('header', __('Results'));

        return ActionResponse::ok(
            [__u('LDAP connection OK'), sprintf(__('Objects found: %d'), $data->count())],
            ['template' => $this->template->render(), 'items' => $data->getResults()],
        );
    }

    /**
     * @throws SessionTimeout
     * @throws SPException
     */
    protected function initialize(): void
    {
        $this->checks();
        $this->checkAccess(AclActionsInterface::CONFIG_LDAP);

        $this->extensionChecker->checkLdap(true);
    }
}
