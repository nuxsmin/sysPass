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

namespace SP\Domain\User\Services;

use Exception;
use SP\Domain\Core\Exceptions\SPException;

/**
 * Class UpdatedMasterPassException
 */
final class UpdatedMasterPassException extends SPException
{
    /**
     * SPException constructor.
     *
     * @param  string  $type
     * @param  int  $code
     * @param Exception|null  $previous
     */
    public function __construct(string $type, int $code = 0, Exception $previous = null)
    {
        parent::__construct(
            __u('Master password updated'),
            $type,
            __u('Please, restart the session for update it'),
            $code,
            $previous
        );
    }

}
