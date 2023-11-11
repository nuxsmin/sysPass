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

namespace SP\Core\Events;

use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;

/**
 * Class Event
 */
class Event
{
    /**
     * Event constructor.
     *
     * @param object $source
     * @param EventMessage|null $eventMessage
     *
     */
    public function __construct(
        readonly object        $source,
        readonly ?EventMessage $eventMessage = null
    ) {
    }

    /**
     * @throws InvalidClassException
     */
    public function getSource(?string $type = null): object
    {
        if ($type !== null
            && ($source = get_class($this->source)) !== $type
            && !is_subclass_of($this->source, $type)
        ) {
            throw new InvalidClassException(
                'Source type mismatch',
                SPException::ERROR,
                sprintf('Source: %s - Expected: %s', $source, $type)
            );
        }

        return $this->source;
    }

    public function getEventMessage(): ?EventMessage
    {
        return $this->eventMessage;
    }
}
