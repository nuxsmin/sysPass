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
use SP\DataModel\NoticeData;
use SP\Http\Request;

/**
 * Class NoticeForm
 *
 * @package SP\Forms
 */
class NoticeForm extends FormBase implements FormInterface
{
    /**
     * @var NoticeData
     */
    protected $noticeData;

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
            case ActionsInterface::NOTICE_USER_CREATE:
            case ActionsInterface::NOTICE_USER_EDIT:
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
        $Description = new NoticeMessage();
        $Description->addDescription(Request::analyze('notice_description'));

        $this->noticeData = new NoticeData();
        $this->noticeData->setId($this->itemId);
        $this->noticeData->setType(Request::analyze('notice_type'));
        $this->noticeData->setComponent(Request::analyze('notice_component'));
        $this->noticeData->setDescription($Description);
        $this->noticeData->setUserId(Request::analyze('notice_user', 0));

        if ($this->noticeData->getUserId() === 0) {
            $this->noticeData->setOnlyAdmin(Request::analyze('notice_onlyadmin', 0, false, 1));
            $this->noticeData->setSticky(Request::analyze('notice_sticky', 0, false, 1));
        }
    }

    /**
     * @throws ValidationException
     */
    private function checkCommon()
    {
        if (!$this->noticeData->getComponent()) {
            throw new ValidationException(__u('Es necesario un componente'));
        }

        if (!$this->noticeData->getType()) {
            throw new ValidationException(__u('Es necesario un tipo'));
        }

        if (!$this->noticeData->getDescription()) {
            throw new ValidationException(__u('Es necesaria una descripción'));
        }

        if (!$this->noticeData->getUserId()
            && !$this->noticeData->isOnlyAdmin()
            && !$this->noticeData->isSticky()) {
            throw new ValidationException(__u('Es necesario un destinatario'));
        }
    }

    /**
     * @return NoticeData
     */
    public function getItemData()
    {
        return $this->noticeData;
    }
}