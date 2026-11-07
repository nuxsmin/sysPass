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

namespace SP\Modules\Web\Controllers\Client;


use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Forms\ClientForm;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

/**
 * Class ClientSaveBase
 */
abstract class ClientSaveBase extends ControllerBase
{
    protected readonly ClientForm $form;

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        WebControllerHelper                       $webControllerHelper,
        protected readonly ClientService          $clientService,
        protected readonly CustomFieldDataService $customFieldService
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();

        $this->form = new ClientForm($this->config, $this->session, $this->request);
    }
}
