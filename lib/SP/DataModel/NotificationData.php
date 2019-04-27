<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
class NotificationData implements DataModelInterface
{
    /**
     * @var int
     */
    public $id = 0;
    /**
     * @var string
     */
    public $type;
    /**
     * @var string
     */
    public $component;
    /**
     * @var string
     */
    public $description;
    /**
     * @var int
     */
    public $date = 0;
    /**
     * @var bool
     */
    public $checked = 0;
    /**
     * @var int
     */
    public $userId;
    /**
     * @var bool
     */
    public $sticky = 0;
    /**
     * @var bool
     */
    public $onlyAdmin = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * @param string $component
     */
    public function setComponent($component)
    {
        $this->component = $component;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param MessageInterface $message
     * @param bool             $useHtml
     */
    public function setDescription(MessageInterface $message, bool $useHtml = false)
    {
        if ($useHtml) {
            $this->description = $message->composeHtml();
        } else {
            $this->description = $message->composeText();
        }
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return (int)$this->date;
    }

    /**
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date = (int)$date;
    }

    /**
     * @return int
     */
    public function isChecked()
    {
        return (int)$this->checked;
    }

    /**
     * @param bool $checked
     */
    public function setChecked($checked)
    {
        $this->checked = (int)$checked;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return (int)$this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = (int)$userId;
    }

    /**
     * @return int
     */
    public function isSticky()
    {
        return (int)$this->sticky;
    }

    /**
     * @param bool $sticky
     */
    public function setSticky($sticky)
    {
        $this->sticky = (int)$sticky;
    }

    /**
     * @return int
     */
    public function isOnlyAdmin()
    {
        return (int)$this->onlyAdmin;
    }

    /**
     * @param bool $onlyAdmin
     */
    public function setOnlyAdmin($onlyAdmin)
    {
        $this->onlyAdmin = (int)$onlyAdmin;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->component;
    }
}