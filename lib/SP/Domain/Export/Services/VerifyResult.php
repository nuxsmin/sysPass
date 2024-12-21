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

namespace SP\Domain\Export\Services;


/**
 * Class VerifyResult
 */
final class VerifyResult
{
    private string $version;
    private bool   $encrypted;
    private array  $nodes;

    public function __construct(string $version, bool $encrypted, array $nodes)
    {
        $this->version = $version;
        $this->encrypted = $encrypted;
        $this->nodes = $nodes;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function getNodes(): array
    {
        return $this->nodes;
    }
}
