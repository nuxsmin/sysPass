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

namespace SP\Infrastructure\Database;

use SP\Domain\Common\Models\Model;
use SplFixedArray;
use TypeError;

use function SP\__u;

/**
 * Class QueryResult
 *
 * @template T of Model&object
 */
class QueryResult
{
    private readonly SplFixedArray $data;
    private readonly int           $numRows;
    private int $totalNumRows = 0;

    /**
     * QueryResult constructor.
     */
    public function __construct(
        array|SplFixedArray|null $data = null,
        private readonly int     $affectedNumRows = 0,
        private readonly int     $lastId = 0
    ) {
        if ($data instanceof SplFixedArray) {
            $this->data = $data;
        } else {
            $this->data = SplFixedArray::fromArray($data ?? []);
        }

        $this->numRows = $this->data->count();
    }

    public static function withTotalNumRows(array $data, ?int $totalNumRows = null): QueryResult
    {
        $result = new self($data);
        $result->totalNumRows = (int)$totalNumRows;

        return $result;
    }

    /**
     * @param class-string<T>|null $dataType
     * @return T
     */
    public function getData(?string $dataType = null): ?object
    {
        if ($dataType) {
            $this->checkDataType($dataType);
        }

        return $this->numRows === 1 ? $this->data->offsetGet(0) : null;
    }

    /**
     * @param string $dataType
     * @return void
     */
    private function checkDataType(string $dataType): void
    {
        if ($this->numRows > 0
            && (!is_object($this->data->offsetGet(0))
                || !is_a($this->data->offsetGet(0), $dataType))
        ) {
            throw new TypeError(sprintf(__u('Invalid data\'s type. Expected: %s'), $dataType));
        }
    }

    /**
     * @param class-string<T>|null $dataType
     *
     * @return T[]
     */
    public function getDataAsArray(?string $dataType = null): array
    {
        if ($dataType) {
            $this->checkDataType($dataType);
        }

        return $this->data?->toArray();
    }

    public function getNumRows(): int
    {
        return $this->numRows;
    }

    public function getTotalNumRows(): int
    {
        return $this->totalNumRows;
    }

    public function getAffectedNumRows(): int
    {
        return $this->affectedNumRows;
    }

    public function getLastId(): int
    {
        return $this->lastId;
    }

    /**
     * Mutate the current data into another {@link QueryResult} by applying the given callback function
     *
     * @param callable $callable
     * @return QueryResult
     */
    public function mutateWithCallback(callable $callable): QueryResult
    {
        $queryResult = new self(
            SplFixedArray::fromArray(array_map($callable, $this->data->toArray())),
            $this->affectedNumRows,
            $this->lastId
        );
        $queryResult->totalNumRows = $this->totalNumRows;

        return $queryResult;
    }
}
