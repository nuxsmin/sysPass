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

namespace SP\Core\Events;

use InvalidArgumentException;
use SP\Core\Exceptions\InvalidClassException;

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
            throw new InvalidArgumentException(__u('An object is needed'));
        }

        $this->source = $source;
        $this->eventMessage = $eventMessage;
    }

    /**
     * @param null $type
     *
     * @return mixed
     * @throws InvalidClassException
     */
    public function getSource($type = null)
    {
        if ($type !== null
            && ($source = get_class($this->source)) !== $type
            && !is_subclass_of($this->source, $type)
        ) {
            throw new InvalidClassException(
                'Source type mismatch',
                InvalidClassException::ERROR,
                sprintf('Source: %s - Expected: %s', $source, $type)
            );
        }

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