<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Events;

use InvalidArgumentException;

/**
 * Class Event
 *
 * @package SP\Core\Events
 */
final class Event
{
    /**
     * @var object
     */
    private $source;
    /**
     * @var EventMessage
     */
    private $eventMessage;

    /**
     * Event constructor.
     *
     * @param object       $source
     * @param EventMessage $eventMessage
     *
     * @throws InvalidArgumentException
     */
    public function __construct($source, EventMessage $eventMessage = null)
    {
        if (!is_object($source)) {
            throw new InvalidArgumentException(__u('Es necesario un objeto'));
        }

        $this->source = $source;
        $this->eventMessage = $eventMessage;
    }

    /**
     * @return object
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return EventMessage|null
     */
    public function getEventMessage()
    {
        return $this->eventMessage;
    }

}