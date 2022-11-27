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

use SP\Domain\Common\Adapters\SimpleModel;

/**
 * Class FileDataGenerator
 */
final class FileDataGenerator extends DataGenerator
{
    public function buildFileExtData(): SimpleModel
    {
        return new SimpleModel(
            array_merge(
                $this->buildFileData()->toArray(),
                ['clientName' => $this->faker->name, 'accountName' => $this->faker->name]
            )
        );
    }

    public function buildFileData(): SimpleModel
    {
        return new SimpleModel([
            'id' => $this->faker->randomNumber(),
            'accountId' => $this->faker->randomNumber(),
            'name' => $this->faker->colorName,
            'type' => $this->faker->mimeType,
            'content' => $this->faker->image(null, 32, 32),
            'extension' => $this->faker->fileExtension,
            'thumb' => $this->faker->image(null, 32, 32),
            'size' => $this->faker->randomNumber(),
        ]);
    }
}
