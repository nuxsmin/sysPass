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

namespace SP\DataModel;

use SP\Core\Messages\MessageInterface;

/**
 * Class NoticeData
 *
 * @package SP\DataModel
 */
class NoticeData implements DataModelInterface
{
    /**
     * @var int
     */
    public $notice_id = 0;
    /**
     * @var string
     */
    public $notice_type;
    /**
     * @var string
     */
    public $notice_component;
    /**
     * @var string
     */
    public $notice_description;
    /**
     * @var int
     */
    public $notice_date = 0;
    /**
     * @var bool
     */
    public $notice_checked = 0;
    /**
     * @var int
     */
    public $notice_userId = 0;
    /**
     * @var bool
     */
    public $notice_sticky = 0;
    /**
     * @var bool
     */
    public $notice_onlyAdmin = 0;

    /**
     * @return int
     */
    public function getNoticeId()
    {
        return (int)$this->notice_id;
    }

    /**
     * @param int $notice_id
     */
    public function setNoticeId($notice_id)
    {
        $this->notice_id = (int)$notice_id;
    }

    /**
     * @return string
     */
    public function getNoticeType()
    {
        return $this->notice_type;
    }

    /**
     * @param string $notice_type
     */
    public function setNoticeType($notice_type)
    {
        $this->notice_type = $notice_type;
    }

    /**
     * @return string
     */
    public function getNoticeComponent()
    {
        return $this->notice_component;
    }

    /**
     * @param string $notice_component
     */
    public function setNoticeComponent($notice_component)
    {
        $this->notice_component = $notice_component;
    }

    /**
     * @return string
     */
    public function getNoticeDescription()
    {
        return $this->notice_description;
    }

    /**
     * @param MessageInterface $message
     */
    public function setNoticeDescription(MessageInterface $message)
    {
        $this->notice_description = $message->composeText();
    }

    /**
     * @return int
     */
    public function getNoticeDate()
    {
        return $this->notice_date;
    }

    /**
     * @param int $notice_date
     */
    public function setNoticeDate($notice_date)
    {
        $this->notice_date = (int)$notice_date;
    }

    /**
     * @return bool
     */
    public function isNoticeChecked()
    {
        return (int)$this->notice_checked;
    }

    /**
     * @param bool $notice_checked
     */
    public function setNoticeChecked($notice_checked)
    {
        $this->notice_checked = (int)$notice_checked;
    }

    /**
     * @return int
     */
    public function getNoticeUserId()
    {
        return (int)$this->notice_userId;
    }

    /**
     * @param int $notice_userId
     */
    public function setNoticeUserId($notice_userId)
    {
        $this->notice_userId = (int)$notice_userId;
    }

    /**
     * @return bool
     */
    public function isNoticeSticky()
    {
        return (int)$this->notice_sticky;
    }

    /**
     * @param bool $notice_sticky
     */
    public function setNoticeSticky($notice_sticky)
    {
        $this->notice_sticky = (int)$notice_sticky;
    }

    /**
     * @return bool
     */
    public function isNoticeOnlyAdmin()
    {
        return (int)$this->notice_onlyAdmin;
    }

    /**
     * @param bool $notice_onlyAdmin
     */
    public function setNoticeOnlyAdmin($notice_onlyAdmin)
    {
        $this->notice_onlyAdmin = (int)$notice_onlyAdmin;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->notice_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->notice_component;
    }
}