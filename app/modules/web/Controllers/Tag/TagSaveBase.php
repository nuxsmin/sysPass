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

namespace SP\Modules\Web\Controllers\Tag;


use SP\Core\Application;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Domain\Tag\Ports\TagServiceInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Forms\TagForm;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class TagSaveBase
 */
abstract class TagSaveBase extends ControllerBase
{
    protected TagServiceInterface         $tagService;
    protected TagForm                     $form;
    protected CustomFieldServiceInterface $customFieldService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        TagServiceInterface $tagService,
        CustomFieldServiceInterface $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->tagService = $tagService;
        $this->customFieldService = $customFieldService;
        $this->form = new TagForm($application, $this->request);
    }
}
