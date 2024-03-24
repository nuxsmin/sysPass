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

use SP\Domain\Plugin\Models\Plugin;

/**
 * Class PluginGenerator
 */
final class PluginGenerator extends DataGenerator
{

    public function buildPlugin(): Plugin
    {
        return new Plugin($this->pluginProperties());
    }

    private function pluginProperties(): array
    {
        return [
            'id' => $this->faker->randomNumber(3),
            'name' => $this->faker->colorName(),
            'data' => $this->faker->text(),
            'enabled' => $this->faker->boolean(),
            'versionLevel' => sprintf('%d.%d', $this->faker->randomNumber(4, true), $this->faker->randomNumber(6, true))
        ];
    }
}
