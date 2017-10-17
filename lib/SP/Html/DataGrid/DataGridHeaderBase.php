<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Html\DataGrid;

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
    private $_headers = array();
    /**
     * El ancho de las columnas
     *
     * @var int
     */
    private $_width = 0;

    /**
     * @param $header string
     */
    public function addHeader($header)
    {
        $this->_headers[] = $header;

        $numHeaders = count($this->_headers);
        $this->_width = ($numHeaders > 0) ? floor(65 / $numHeaders) : 65;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->_width;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }
}