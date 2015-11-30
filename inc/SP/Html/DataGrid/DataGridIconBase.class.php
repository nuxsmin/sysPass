<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Html\DataGrid;

/**
 * Class DataGridIconBase para crear los iconos de la matriz
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridIconBase implements DataGridIconInterface
{
    /**
     * El icono a utilizar en la etiqueta <i> (según tema visual)
     *
     * @var string
     */
    private $_icon = '';
    /**
     * Imagen a utilizar en el icono (según tema visual)
     *
     * @var string
     */
    private $_image = '';
    /**
     * Título del icono
     *
     * @var string
     */
    private $_title = '';
    /**
     * Clases CSS del icono
     *
     * @var array
     */
    private $_class = array();

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->_icon;
    }

    /**
     * @param $icon
     */
    public function setIcon($icon)
    {
        $this->_icon = $icon;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->_image;
    }

    /**
     * @param $image
     */
    public function setImage($image)
    {
        $this->_image = $image;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param $title
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @param $class
     */
    public function setClass($class)
    {
        $this->_class[] = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return implode(' ', $this->_class);
    }
}