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

use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ItemPreset\Password;
use SP\Domain\Account\Models\ItemPreset;

/**
 * Class ItemPresetDataGenerator
 */
final class ItemPresetDataGenerator extends DataGenerator
{
    public function buildItemPresetData(object $data): ItemPreset
    {
        return new ItemPreset([
                                  'id' => $this->faker->randomNumber(3),
            'type'          => $this->faker->colorName,
                                  'userId' => $this->faker->randomNumber(3),
                                  'userGroupId' => $this->faker->randomNumber(3),
                                  'userProfileId' => $this->faker->randomNumber(3),
            'fixed'         => (int)$this->faker->boolean,
                                  'priority' => $this->faker->randomNumber(3),
            'data'          => serialize($data),
        ]);
    }

    public function buildPassword(): Password
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
            $this->faker->randomNumber(3),
            $this->faker->regexify('abc123')
        );
    }

    public function buildAccountPrivate(): AccountPrivate
    {
        return new AccountPrivate($this->faker->boolean, $this->faker->boolean);
    }
}
