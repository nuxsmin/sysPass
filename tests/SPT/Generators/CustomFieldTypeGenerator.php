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

use SP\Domain\CustomField\Models\CustomFieldType;

/**
 * Class CustomFieldTypeGenerator
 */
final class CustomFieldTypeGenerator extends DataGenerator
{

    public function buildCustomFieldType(): CustomFieldType
    {
        return new CustomFieldType($this->customFieldTypeProperties());
    }

    private function customFieldTypeProperties(): array
    {
        return [
            'id' => $this->faker->randomNumber(3),
            'name' => $this->faker->colorName(),
            'text' => $this->faker->text(),
        ];
    }
}
