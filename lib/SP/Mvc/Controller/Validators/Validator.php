<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mvc\Controller\Validators;

/**
 * Class Validator
 *
 * @package SP\Util
 */
final class Validator
{
    public static function hasLetters(string $string): bool
    {
        return preg_match('#[a-z]+#i', $string) === 1;
    }

    public static function hasNumbers(string $string): bool
    {
        return preg_match('#[\d]+#', $string) === 1;
    }

    public static function hasUpper(string $string): bool
    {
        return preg_match('#[A-Z]+#', $string) === 1;
    }

    public static function hasLower(string $string): bool
    {
        return preg_match('#[a-z]+#', $string) === 1;
    }

    public static function hasSymbols(string $string): bool
    {
        return preg_match('#[$-/:-?{-~!"^_`\[\]]+#', $string) === 1;
    }

    public static function matchRegex(string $string, string $regex): bool
    {
        return preg_match('#' . str_replace('#', '\#', $regex) . '#', $string) === 1;
    }

    public static function isRegex(string $regex): bool
    {
        return @preg_match('#' . str_replace('#', '\#', $regex) . '#', null) !== false;
    }
}