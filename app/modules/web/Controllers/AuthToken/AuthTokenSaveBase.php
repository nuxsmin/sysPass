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

namespace SP\Modules\Web\Controllers\AuthToken;


use SP\Core\Application;
use SP\Domain\Auth\Ports\AuthTokenService;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AuthTokenForm;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * A base class for all "save" actions
 */
abstract class AuthTokenSaveBase extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    protected CustomFieldDataService $customFieldService;
    protected AuthTokenService       $authTokenService;
    protected AuthTokenForm               $form;

    public function __construct(
        Application         $application,
        WebControllerHelper $webControllerHelper,
        AuthTokenService    $authTokenService,
        CustomFieldDataService $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->authTokenService = $authTokenService;
        $this->customFieldService = $customFieldService;
        $this->form = new AuthTokenForm($application, $this->request);
    }
}
