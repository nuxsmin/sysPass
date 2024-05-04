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

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace SP\Domain\Common\Attributes;

use Attribute;

/**
 * Class Encryptable
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Encryptable
{
    public function __construct(private readonly string $dataProperty, private readonly string $keyProperty)
    {
    }

    public function getDataProperty(): string
    {
        return $this->dataProperty;
    }

    public function getKeyProperty(): string
    {
        return $this->keyProperty;
    }
}
