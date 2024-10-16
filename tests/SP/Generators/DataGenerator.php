<?php

declare(strict_types=1);
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

namespace SP\Tests\Generators;

use Faker\Factory;
use Faker\Generator;

/**
 * Class DataGenerator
 */
abstract class DataGenerator
{
    protected Generator $faker;

    private function __construct()
    {
        $this->faker = Factory::create();
    }

    public static function factory(): static
    {
        return new static();
    }

    protected function getRandomIdList(): array
    {
        return array_map(fn() => $this->faker->randomNumber(3), range(0, 4));
    }
}
