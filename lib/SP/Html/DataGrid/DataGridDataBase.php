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

namespace SP\Html\DataGrid;

use SP\Domain\Core\Exceptions\SPException;
use SP\Html\Assets\IconInterface;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class DataGridDataBase para establecer el origen de datos de la matriz
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridDataBase implements DataGridDataInterface
{
    /**
     * Los datos de la matriz
     */
    private array $data = [];
    /**
     * Las columnas a mostrar de los datos obtenidos
     */
    private array $sources = [];
    /**
     * La columna que identifica cada elemento de los datos de la matriz
     */
    private string $sourceId = '';
    /**
     * Las columnas a mostrar de los datos obtenidos que son representadas con iconos
     */
    private array $sourcesWithIcon = [];
    private int $dataCount = 0;

    public function getDataRowSourcesWithIcon(): array
    {
        return $this->sourcesWithIcon;
    }

    public function addDataRowSource(
        string    $source,
        ?bool     $isMethod = false,
        ?callable $filter = null,
        ?bool     $truncate = true
    ): void {
        $this->sources[] = [
            'name' => $source,
            'isMethod' => (bool)$isMethod,
            'filter' => $filter,
            'truncate' => (bool)$truncate
        ];
    }

    public function setDataRowSourceId(string $id): void
    {
        $this->sourceId = $id;
    }

    public function getDataRowSources(): array
    {
        return $this->sources;
    }

    public function getDataRowSourceId(): string
    {
        return $this->sourceId;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @throws SPException
     */
    public function setData(QueryResult $queryResult): void
    {
        $this->dataCount = $queryResult->getTotalNumRows();
        $this->data = $queryResult->getDataAsArray();
    }

    public function addDataRowSourceWithIcon(
        string        $source,
        IconInterface $icon,
        int           $value = 1
    ): void {
        $this->sourcesWithIcon[] = [
            'field' => $source,
            'icon' => $icon,
            'value' => $value
        ];
    }

    public function getDataCount(): int
    {
        return $this->dataCount;
    }
}
