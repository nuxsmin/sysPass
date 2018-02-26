<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Forms;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\Core\Messages\NoticeMessage;
use SP\DataModel\NotificationData;
use SP\Http\Request;

/**
 * Class NotificationForm
 *
 * @package SP\Forms
 */
class NotificationForm extends FormBase implements FormInterface
{
    /**
     * @var NotificationData
     */
    protected $notificationData;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return bool
     * @throws \SP\Core\Exceptions\ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::NOTIFICATION_CREATE:
            case ActionsInterface::NOTIFICATION_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
        }

        return true;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData()
    {
        $this->notificationData = new NotificationData();
        $this->notificationData->setId($this->itemId);
        $this->notificationData->setType(Request::analyze('notification_type'));
        $this->notificationData->setComponent(Request::analyze('notification_component'));
        $this->notificationData->setDescription(NoticeMessage::factory()->addDescription(Request::analyze('notification_description')));
        $this->notificationData->setUserId(Request::analyze('notification_user', 0));
        $this->notificationData->setChecked(Request::analyze('notification_checkout', 0, false, 1));

        if ($this->session->getUserData()->getIsAdminApp() && $this->notificationData->getUserId() === 0) {
            $this->notificationData->setOnlyAdmin(Request::analyze('notification_onlyadmin', 0, false, 1));
            $this->notificationData->setSticky(Request::analyze('notification_sticky', 0, false, 1));
        }
    }

    /**
     * @throws ValidationException
     */
    private function checkCommon()
    {
        if (!$this->notificationData->getComponent()) {
            throw new ValidationException(__u('Es necesario un componente'));
        }

        if (!$this->notificationData->getType()) {
            throw new ValidationException(__u('Es necesario un tipo'));
        }

        if (!$this->notificationData->getDescription()) {
            throw new ValidationException(__u('Es necesaria una descripción'));
        }

        if (!$this->notificationData->getUserId()
            && !$this->notificationData->isOnlyAdmin()
            && !$this->notificationData->isSticky()) {
            throw new ValidationException(__u('Es necesario un destinatario'));
        }
    }

    /**
     * @return NotificationData
     */
    public function getItemData()
    {
        return $this->notificationData;
    }
}