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

namespace SP\Infrastructure\Database;

use SP\Core\Exceptions\SPException;

use function SP\__u;

/**
 * Class QueryResult
 *
 * @package SP\Infrastructure\Database
 */
class QueryResult
{
    private ?array  $data            = null;
    private ?string $dataType        = null;
    private int     $numRows         = 0;
    private int     $totalNumRows    = 0;
    private int     $affectedNumRows = 0;
    private int     $statusCode      = 0;
    private int     $lastId          = 0;

    /**
     * QueryResult constructor.
     *
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if (null !== $data) {
            $this->data = $data;
            $this->numRows = count($data);

            if ($this->numRows > 0 && is_object($data[0])) {
                $this->dataType = get_class($data[0]);
            }
        }
    }

    public static function withTotalNumRows(
        array $data,
        ?int  $totalNumRows = null
    ): QueryResult {
        $result = new self($data);
        $result->totalNumRows = (int)$totalNumRows;

        return $result;
    }

    /**
     * @template T
     *
     * @param class-string<T>|null $dataType
     *
     * @return T|mixed|null
     *
     * @throws SPException
     */
    public function getData(?string $dataType = null): mixed
    {
        $this->checkDataType($dataType);

        return $this->numRows === 1 ? $this->data[0] : null;
    }

    /**
     * @param string|null $dataType
     *
     * @return void
     * @throws SPException
     */
    private function checkDataType(?string $dataType = null): void
    {
        if (null !== $dataType && $this->dataType !== null && $dataType !== $this->dataType) {
            throw new SPException(sprintf(__u('Invalid data\'s type: %s - Current: %s'), $dataType, $this->dataType));
        }
    }

    /**
     * @template T
     *
     * @param class-string<T>|null $dataType
     *
     * @return T[]
     * @throws SPException
     */
    public function getDataAsArray(?string $dataType = null): array
    {
        $this->checkDataType($dataType);

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

    public function getDataType(): ?string
    {
        return $this->dataType;
    }
}
