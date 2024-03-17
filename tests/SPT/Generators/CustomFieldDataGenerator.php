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

use SP\Domain\CustomField\Models\CustomFieldData;

/**
 * Class CustomFieldDataGenerator
 */
final class CustomFieldDataGenerator extends DataGenerator
{
    public function buildCustomFieldData(bool $useEncryption = true): CustomFieldData
    {
        return new CustomFieldData($this->customFieldDataProperties($useEncryption));
    }

    private function customFieldDataProperties(bool $useEncryption): array
    {
        $key = null;

        if ($useEncryption) {
            $key = $this->faker->sha1();
        }

        return [
            'moduleId' => $this->faker->randomNumber(3),
            'itemId' => $this->faker->randomNumber(3),
            'definitionId' => $this->faker->randomNumber(3),
            'data' => $this->faker->text(),
            'key' => $key,
        ];
    }
}
