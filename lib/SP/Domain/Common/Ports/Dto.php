<?php
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

namespace SP\Domain\Common\Ports;

use SP\Domain\Common\Models\Model;
use SP\Infrastructure\Database\QueryResult;

/**
 * Interface Dto
 */
interface Dto
{
    /**
     * @param QueryResult $queryResult
     * @param string|null $type
     * @return static
     */
    public static function fromResult(QueryResult $queryResult, ?string $type = null): static;

    /**
     * @param Model $model
     * @return static
     */
    public static function fromModel(Model $model): static;

    public static function fromArray(array $properties): static;

    /**
     * Set any properties in batch mode. This allows to set any property from dynamic calls.
     *
     * @param string[] $properties
     * @param array $values
     *
     * @return Dto Returns a new instance with the poperties set.
     */
    public function setBatch(array $properties, array $values): static;
}
