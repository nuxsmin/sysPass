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

/**
 * Class ActionData
 *
 * @package SP\DataModel
 */
class ActionData implements DataModelInterface
{
    /**
     * @var int
     */
    public $action_id;
    /**
     * @var string
     */
    public $action_name;
    /**
     * @var string
     */
    public $action_text;
    /**
     * @var string
     */
    public $action_route;

    /**
     * @return int
     */
    public function getActionId()
    {
        return $this->action_id;
    }

    /**
     * @return string
     */
    public function getActionName()
    {
        return $this->action_name;
    }

    /**
     * @return string
     */
    public function getActionText()
    {
        return $this->action_text;
    }

    /**
     * @return string
     */
    public function getActionRoute()
    {
        return $this->action_route;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->action_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->action_name;
    }
}