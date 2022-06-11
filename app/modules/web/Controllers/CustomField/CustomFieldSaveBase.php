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

namespace SP\Modules\Web\Controllers\CustomField;


use SP\Core\Application;
use SP\Domain\CustomField\CustomFieldDefServiceInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Forms\CustomFieldDefForm;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class CustomFieldSaveBase
 */
abstract class CustomFieldSaveBase extends ControllerBase
{
    protected CustomFieldDefServiceInterface $customFieldDefService;
    protected CustomFieldDefForm             $form;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        CustomFieldDefServiceInterface $customFieldDefService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->customFieldDefService = $customFieldDefService;
        $this->form = new CustomFieldDefForm($application, $this->request);
    }
}