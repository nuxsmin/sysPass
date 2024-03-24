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

use SP\Domain\Common\Models\Simple;

/**
 * Class FileDataGenerator
 */
final class FileDataGenerator extends DataGenerator
{
    public function buildFileExtData(): Simple
    {
        return new Simple(
            array_merge(
                $this->buildFileData()->toArray(null, null, true),
                ['clientName' => $this->faker->name(), 'accountName' => $this->faker->name()]
            )
        );
    }

    public function buildFileData(): Simple
    {
        return new Simple([
                              'id' => $this->faker->randomNumber(3),
                              'accountId' => $this->faker->randomNumber(3),
                              'name' => $this->faker->colorName(),
                              'type' => 'image/jpeg',
                              'content' => $this->faker->imageUrl(32, 32),
                              'extension' => $this->faker->fileExtension(),
                              'thumb' => $this->faker->imageUrl(32, 32),
                              'size' => $this->faker->randomNumber(3),
                          ]);
    }
}
