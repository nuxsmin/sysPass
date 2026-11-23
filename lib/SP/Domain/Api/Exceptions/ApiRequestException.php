<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Api\Exceptions;

use SP\Domain\Core\Exceptions\SPException;

/**
 * Class ApiRequestException
 */
final class ApiRequestException extends SPException
{
    private int|float|string|null $requestId = null;
    private bool $notification = false;

    public function withRequestId(int|float|string|null $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function getRequestId(): int|float|string|null
    {
        return $this->requestId;
    }

    public function asNotification(bool $notification = true): self
    {
        $this->notification = $notification;

        return $this;
    }

    public function isNotification(): bool
    {
        return $this->notification;
    }
}
