<?php
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

namespace SP\Modules\Api\Controllers\Help;

/**
 * Trait HelpTrait
 *
 * @package SP\Modules\Api\Controllers\Help
 */
trait HelpTrait
{
    /**
     * @param  string  $action
     *
     * @return array
     */
    public static function getHelpFor(string $action): array
    {
        if (str_contains($action, '/')) {
            [, $action] = explode('/', $action);
        }

        if (method_exists(static::class, $action)) {
            return [
                'help' => static::$action(),
            ];
        }

        return [];
    }

    /**
     * @param  string  $name
     * @param  string  $description
     * @param  bool  $required
     *
     * @return array
     */
    private static function getItem(
        string $name,
        string $description,
        bool $required = false
    ): array {
        return [
            $name => ['description' => $description, 'required' => $required],
        ];
    }
}
