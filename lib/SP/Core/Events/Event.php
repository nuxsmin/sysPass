<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Core\Exceptions\InvalidClassException;

/**
 * Class Event
 */
readonly class Event
{
    public function __construct(
        private object        $source,
        private ?EventMessage $eventMessage = null
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|null $type
     * @return T&object
     * @throws InvalidClassException
     */
    public function getSource(?string $type = null): object
    {
        if ($type !== null && !is_a($this->source, $type)) {
            throw InvalidClassException::error(
                'Source type mismatch',
                sprintf('Source: %s - Expected: %s', get_class($this->source), $type)
            );
        }

        return $this->source;
    }

    public function getEventMessage(): ?EventMessage
    {
        return $this->eventMessage;
    }

}
