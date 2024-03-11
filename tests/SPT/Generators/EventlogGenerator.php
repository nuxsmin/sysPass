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

namespace SPT\Generators;

use SP\Domain\Security\Models\Eventlog;

/**
 * Class EventlogGenerator
 */
final class EventlogGenerator extends DataGenerator
{
    public function buildEventlog(): Eventlog
    {
        return new Eventlog($this->eventlogProperties());
    }

    private function eventlogProperties(): array
    {
        return [
            'id' => $this->faker->randomNumber(3),
            'date' => $this->faker->unixTime(),
            'login' => $this->faker->colorName(),
            'userId' => $this->faker->randomNumber(3),
            'ipAddress' => $this->faker->ipv4(),
            'action' => $this->faker->jobTitle(),
            'description' => $this->faker->text(),
            'level' => $this->faker->colorName()
        ];
    }
}
