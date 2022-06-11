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

namespace SP\Modules\Web\Controllers\Client;


use SP\Core\Application;
use SP\Domain\Client\ClientServiceInterface;
use SP\Domain\CustomField\CustomFieldServiceInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Forms\ClientForm;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class ClientSaveBase
 */
abstract class ClientSaveBase extends ControllerBase
{
    protected ClientServiceInterface      $clientService;
    protected CustomFieldServiceInterface $customFieldService;
    protected ClientForm                  $form;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        ClientServiceInterface $clientService,
        CustomFieldServiceInterface $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->clientService = $clientService;
        $this->customFieldService = $customFieldService;
        $this->form = new ClientForm($application, $this->request);
    }
}