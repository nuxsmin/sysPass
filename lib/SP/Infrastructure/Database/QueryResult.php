<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

/**
 * Class QueryResult
 *
 * @package SP\Infrastructure\Database
 */
final class QueryResult
{
    private ?array $data            = null;
    private int    $numRows         = 0;
    private int    $totalNumRows    = 0;
    private int    $affectedNumRows = 0;
    private int    $statusCode      = 0;
    private int    $lastId          = 0;

    /**
     * QueryResult constructor.
     *
     * @param  array|null  $data
     */
    public function __construct(?array $data = null)
    {
        if (null !== $data) {
            $this->data = $data;
            $this->numRows = count($data);
        }
    }

    public static function fromResults(
        array $data,
        ?int $totalNumRows = null
    ): QueryResult {
        $result = new self($data);

        if (null !== $totalNumRows) {
            $result->totalNumRows = $totalNumRows;
        }

        return $result;
    }

    /**
     * @return mixed|null
     */
    public function getData(): mixed
    {
        return $this->numRows === 1 ? $this->data[0] : null;
    }

    public function getDataAsArray(): array
    {
        return $this->data ?? [];
    }

    public function getNumRows(): int
    {
        return $this->numRows;
    }

    public function getTotalNumRows(): int
    {
        return $this->totalNumRows;
    }

    public function setTotalNumRows(int $totalNumRows): QueryResult
    {
        $this->totalNumRows = $totalNumRows;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getAffectedNumRows(): int
    {
        return $this->affectedNumRows;
    }

    public function setAffectedNumRows(int $affectedNumRows): QueryResult
    {
        $this->affectedNumRows = $affectedNumRows;

        return $this;
    }

    public function getLastId(): int
    {
        return $this->lastId;
    }

    public function setLastId(int $lastId): QueryResult
    {
        $this->lastId = $lastId;

        return $this;
    }
}
