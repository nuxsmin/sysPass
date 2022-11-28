<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\DataModel\ItemPreset\Password;
use SP\Domain\Common\Adapters\SimpleModel;

/**
 * Class ItemPresetDataGenerator
 */
final class ItemPresetDataGenerator extends DataGenerator
{
    public function buildItemPresetData(object $data): SimpleModel
    {
        return new SimpleModel([
            'id'            => $this->faker->randomNumber(),
            'type'          => $this->faker->colorName,
            'userId'        => $this->faker->randomNumber(),
            'userGroupId'   => $this->faker->randomNumber(),
            'userProfileId' => $this->faker->randomNumber(),
            'fixed'         => $this->faker->numberBetween(0, 1),
            'priority'      => $this->faker->randomNumber(),
            'data'          => serialize($data),
        ]);
    }

    public function buildPasswordPreset(): Password
    {
        return new Password(
            $this->faker->numberBetween(1, 12),
            $this->faker->boolean,
            $this->faker->boolean,
            $this->faker->boolean,
            $this->faker->boolean,
            $this->faker->boolean,
            $this->faker->boolean,
            $this->faker->unixTime,
            $this->faker->randomNumber(),
            $this->faker->regexify('abc123')
        );
    }
}
