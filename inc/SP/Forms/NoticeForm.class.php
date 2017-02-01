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

use SP\Core\ActionsInterface;
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
    protected $NoticeData;

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
            case ActionsInterface::ACTION_NOT_USER_NEW:
            case ActionsInterface::ACTION_NOT_USER_EDIT:
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

        $this->NoticeData = new NoticeData();
        $this->NoticeData->setNoticeId($this->itemId);
        $this->NoticeData->setNoticeType(Request::analyze('notice_type'));
        $this->NoticeData->setNoticeComponent(Request::analyze('notice_component'));
        $this->NoticeData->setNoticeDescription($Description);
        $this->NoticeData->setNoticeUserId(Request::analyze('notice_user', 0));

        if ($this->NoticeData->getNoticeUserId() === 0) {
            $this->NoticeData->setNoticeOnlyAdmin(Request::analyze('notice_onlyadmin', 0, false, 1));
            $this->NoticeData->setNoticeSticky(Request::analyze('notice_sticky', 0, false, 1));
        }
    }

    private function checkCommon()
    {
        if (!$this->NoticeData->getNoticeComponent()) {
            throw new ValidationException(__('Es necesario un componente', false));
        } elseif (!$this->NoticeData->getNoticeType()) {
            throw new ValidationException(__('Es necesario un tipo', false));
        } elseif (!$this->NoticeData->getNoticeDescription()) {
            throw new ValidationException(__('Es necesaria una descripción', false));
        } elseif (!$this->NoticeData->getNoticeUserId()
            && !$this->NoticeData->isNoticeOnlyAdmin()
            && !$this->NoticeData->isNoticeSticky()
        ) {
            throw new ValidationException(__('Es necesario un destinatario', false));
        }
    }

    /**
     * @return NoticeData
     */
    public function getItemData()
    {
        return $this->NoticeData;
    }
}