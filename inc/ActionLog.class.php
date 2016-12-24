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

namespace SP;


/**
 * Clase abstracta ActionLog para la gestión de mensajes de eventos
 *
 * @package SP
 */
abstract class ActionLog
{
    /**
     * Constante de nueva línea para descriciones
     */
    const NEWLINE_TXT = ';;';

    /**
     * Constante de nueva línea para descriciones en formato HTML
     */
    const NEWLINE_HTML = '<br>';

    /**
     * Acción realizada
     *
     * @var string
     */
    protected $_action = __CLASS__;
    /**
     * Detalles de la acción
     *
     * @var array
     */
    protected $_description = null;
    /**
     * Formato de nueva línea en HTML
     *
     * @var bool
     */
    protected $_newLineHtml = false;

    /**
     * Contructor
     *
     * @param $action      string La acción realizada
     * @param $description string La descripción de la acción realizada
     */
    public function __construct($action = null, $description = null)
    {
        if (null !== $action) {
            $this->setAction($action);
        }

        if (null !== $description) {
            $this->addDescription($description);
        }
    }

    /**
     * Devuelve la acción realizada
     *
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * Establece la acción realizada
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->_action = $this->formatString($action);
    }

    /**
     * Devuelve la descripción de la acción realizada
     *
     * @return array
     */
    public function getDescription()
    {
        if(is_null($this->_description)){
            return '';
        }

        if (count($this->_description) > 1){
            $newline = ($this->_newLineHtml === false) ? self::NEWLINE_TXT : self::NEWLINE_HTML;

            return implode($newline, $this->_description);
        }

        return $this->_description[0];
    }

    /**
     * Establece la descripción de la acción realizada
     *
     * @param string $description
     */
    public function addDescription($description = '')
    {
        $this->_description[] = $this->formatString($description);
    }

    /**
     * Formatear una cadena para guardarla en el registro
     *
     * @param $string string La cadena a formatear
     * @return string
     */
    private function formatString($string)
    {
        return strip_tags(utf8_encode($string));
    }

    /**
     * Establecer el formato de nueva línea a HTML
     *
     * @param $bool bool
     */
    public function setNewLineHtml($bool){
        $this->_newLineHtml = $bool;
    }

    /**
     * Restablecer la variable de descripcion
     */
    public function resetDescription()
    {
        $this->_description = null;
    }
}