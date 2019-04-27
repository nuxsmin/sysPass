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

namespace SP\Html\DataGrid\Layout;

defined('APP_ROOT') || die();

/**
 * Class DataGridHeaderBase para establecer las cabeceras de la matriz
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridHeaderBase implements DataGridHeaderInterface
{
    /**
     * Las cabeceras que identifican las columnas de datos
     *
     * @var array
     */
    private $headers = [];
    /**
     * El ancho de las columnas
     *
     * @var int
     */
    private $width = 0;

    /**
     * @param $header string
     */
    public function addHeader($header)
    {
        $this->headers[] = $header;

        $numHeaders = count($this->headers);
        $this->width = ($numHeaders > 0) ? floor(65 / $numHeaders) : 65;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}