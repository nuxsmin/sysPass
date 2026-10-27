<?php

declare(strict_types=1);
/**
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

namespace SP\Domain\Tag\Exceptions;

use SP\Domain\Core\Exceptions\SPException;

/**
 * Tag domain errors with stable codes and untranslated message keys.
 *
 * The interface layer translates the message keys before rendering them.
 */
final class TagException extends SPException
{
    public const NOT_FOUND     = 1001;
    public const DELETE_FAILED = 1002;

    public static function notFound(): self
    {
        return self::info('Tag not found', null, self::NOT_FOUND);
    }

    public static function deleteFailed(): self
    {
        return self::warning('Error while removing the tags', null, self::DELETE_FAILED);
    }
}
