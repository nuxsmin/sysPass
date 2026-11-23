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

namespace SP\Modules\Web\Controllers\Tag;


use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\UseCases\Dtos\GetTagCommand;
use SP\Domain\Tag\UseCases\GetTagUseCase;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

/**
 * Class TagViewBase
 */
abstract class TagViewBase extends ControllerBase
{
    public function __construct(
        WebControllerHelper $webControllerHelper,
        private readonly GetTagUseCase $getTagUseCase
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * Sets view data for displaying tag's data
     *
     * @param  int|null  $tagId
     */
    protected function setViewData(?int $tagId = null): void
    {
        $this->view->addTemplate('tag', 'itemshow');

        $tag = $tagId !== null
            ? $this->getTagUseCase->execute(new GetTagCommand($tagId))->tag
            : new Tag();

        $this->view->assign('tag', $tag);

        $this->view->assign('nextAction', $this->acl->getRouteFor(AclActionsInterface::ITEMS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }
}
