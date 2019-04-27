<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Storage\Database;

/**
 * Class QueryResult
 *
 * @package SP\Storage\Database
 */
final class QueryResult
{
    /**
     * @var array
     */
    private $data;
    /**
     * @var int
     */
    private $numRows = 0;
    /**
     * @var int
     */
    private $totalNumRows;
    /**
     * @var int
     */
    private $affectedNumRows;
    /**
     * @var int
     */
    private $statusCode;
    /**
     * @var int
     */
    private $lastId = 0;

    /**
     * QueryResult constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = null)
    {
        if ($data !== null) {
            $this->data = $data;
            $this->numRows = count($data);
        }
    }

    /**
     * @param array $data
     * @param null  $totalNumRows
     *
     * @return QueryResult
     */
    public static function fromResults(array $data, $totalNumRows = null)
    {
        $result = new self($data);

        if ($totalNumRows !== null) {
            $result->totalNumRows = $totalNumRows;
        }

        return $result;
    }

    /**
     * @param string $class
     *
     * @return mixed|null
     */
    public function getData(string $class = null)
    {
        if ($this->numRows === 1) {
            return $this->data[0];
        }

        return null;
    }

    /**
     * Always returns an array
     *
     * @return array
     */
    public function getDataAsArray(): array
    {
        return (array)$this->data;
    }

    /**
     * @return int
     */
    public function getNumRows(): int
    {
        return $this->numRows;
    }

    /**
     * @return int
     */
    public function getTotalNumRows(): int
    {
        return $this->totalNumRows;
    }

    /**
     * @param int $totalNumRows
     *
     * @return QueryResult
     */
    public function setTotalNumRows(int $totalNumRows)
    {
        $this->totalNumRows = $totalNumRows;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     *
     * @return QueryResult
     */
    public function setStatusCode(int $statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return int
     */
    public function getAffectedNumRows(): int
    {
        return $this->affectedNumRows;
    }

    /**
     * @param int $affectedNumRows
     *
     * @return QueryResult
     */
    public function setAffectedNumRows(int $affectedNumRows)
    {
        $this->affectedNumRows = $affectedNumRows;

        return $this;
    }

    /**
     * @return int
     */
    public function getLastId(): int
    {
        return $this->lastId;
    }

    /**
     * @param int $lastId
     *
     * @return QueryResult
     */
    public function setLastId(int $lastId)
    {
        $this->lastId = $lastId;

        return $this;
    }
}