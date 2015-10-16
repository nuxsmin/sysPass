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

namespace SP\Log;

/**
 * Clase abstracta ActionLog para la gestión de mensajes de eventos
 *
 * @package SP
 */
abstract class ActionLog extends LogLevel
{
    /**
     * Constante de nueva línea para descripciones
     */
    const NEWLINE_TXT = PHP_EOL;
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
     * @var string
     */
    protected $_logLevel = '';
    /**
     * @var array
     */
    protected $_details = null;

    /**
     * Contructor
     *
     * @param string $action      La acción realizada
     * @param string $description La descripción de la acción realizada
     * @param string $level       El nivel del mensaje
     */
    function __construct($action = null, $description = null, $level = Log::INFO)
    {
        if (!is_null($action)) {
            $this->setAction($action);
        }

        if (!is_null($description)) {
            $this->addDescription($description);
        }

        $this->_logLevel = $level;
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
     * @return string
     */
    public function getLogLevel()
    {
        return strtoupper($this->_logLevel);
    }

    /**
     * @param string $logLevel
     */
    public function setLogLevel($logLevel)
    {
        $this->_logLevel = $logLevel;
    }

    /**
     * Devuelve los detalles de la acción realizada
     *
     * @return string
     */
    public function getDetails()
    {
        if (is_null($this->_details)) {
            return '';
        }

        if (count($this->_details) > 1) {
            $newline = ($this->_newLineHtml === false) ? PHP_EOL : self::NEWLINE_HTML;

            return implode($newline, $this->_details);
        }

        return $this->_details[0];
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
     * @return string
     */
    public function getDescription()
    {
        if (is_null($this->_description)) {
            return '';
        }

        if (count($this->_description) > 1) {
            $newline = ($this->_newLineHtml === false) ? PHP_EOL : self::NEWLINE_HTML;

            return implode($newline, $this->_description);
        }

        return $this->_description[0];
    }

    /**
     * Establece los detalles de la acción realizada
     *
     * @param $key   string
     * @param $value string
     */
    public function addDetails($key, $value)
    {
        $this->_details[] = sprintf('%s: %s', $this->formatString($key), $this->formatString($value));
    }

    /**
     * Establecer el formato de nueva línea a HTML
     *
     * @param $bool bool
     */
    public function setNewLineHtml($bool)
    {
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