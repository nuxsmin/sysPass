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

namespace SP\Modules\Web\Controllers\CustomField;

use Exception;
use JsonException;
use SP\Core\Events\Event;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class CustomFieldController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ViewController extends CustomFieldViewBase
{
    use JsonTrait;

    /**
     * View action
     *
     * @param  int  $id
     *
     * @return bool
     * @throws JsonException
     */
    public function viewAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(AclActionsInterface::CUSTOMFIELD_VIEW)) {
                return $this->returnJsonResponse(
                    JsonMessage::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('View Field'));
            $this->view->assign('isView', true);

            $this->setViewData($id);

            $this->eventDispatcher->notify('show.customField', new Event($this));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponse(JsonMessage::JSON_ERROR, $e->getMessage());
        }

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }
}
