<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Log;

use SP\Html\Html;

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
    protected $action;
    /**
     * Detalles de la acción
     *
     * @var array
     */
    protected $description;
    /**
     * Formato de nueva línea en HTML
     *
     * @var bool
     */
    protected $newLineHtml = false;
    /**
     * @var string
     */
    protected $logLevel = '';
    /**
     * @var array
     */
    protected $details;

    /**
     * Contructor
     *
     * @param string $action      La acción realizada
     * @param string $description La descripción de la acción realizada
     * @param string $level       El nivel del mensaje
     */
    public function __construct($action = null, $description = null, $level = Log::INFO)
    {
        if (null !== $action) {
            $this->setAction($action);
        }

        if (null !== $description) {
            $this->addDescription($description);
        }

        $this->logLevel = $level;
    }

    /**
     * Establece la descripción de la acción realizada
     *
     * @param string $description
     */
    public function addDescription($description = '')
    {
        $this->description[] = $this->formatString($description);
    }

    /**
     * Formatear una cadena para guardarla en el registro
     *
     * @param $string string La cadena a formatear
     * @return string
     */
    private function formatString($string)
    {
        return strip_tags($string);
    }

    /**
     * Establece la descripción de la acción realizada en formato HTML
     *
     * @param string $description
     */
    public function addDescriptionHtml($description = '')
    {
        $this->addDescription(Html::strongText($description));
    }

    /**
     * Añadir una línea en blanco a la descripción
     */
    public function addDescriptionLine()
    {
        $this->description[] = '';
    }

    /**
     * @return string
     */
    public function getLogLevel()
    {
        return strtoupper($this->logLevel);
    }

    /**
     * @param string $logLevel
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }

    /**
     * Devuelve los detalles de la acción realizada
     *
     * @param bool $translate
     * @return string
     */
    public function getDetails($translate = false)
    {
        if (null === $this->details) {
            return '';
        }

        if (count($this->details) > 1) {
            if ($translate === true) {
                return implode(PHP_EOL, array_map(function ($detail) use ($translate) {
                    return $this->formatDetail($detail, $translate);
                }, $this->details));
            }

            return implode(PHP_EOL, array_map([$this, 'formatDetail'], $this->details));
        }

        return $this->formatDetail($this->details[0], $translate);
    }

    /**
     * Devolver un detalle formateado
     *
     * @param array $detail
     * @param bool  $translate
     * @return string
     */
    protected function formatDetail(array $detail, $translate = false)
    {
        if ($translate === true) {
            return sprintf('%s : %s', __($detail[0]), __($detail[1]));
        }

        return sprintf('%s : %s', $detail[0], $detail[1]);
    }

    /**
     * Devuelve la acción realizada
     *
     * @param bool $translate
     * @return string
     */
    public function getAction($translate = false)
    {
        return $translate === true ? __($this->action) : $this->action;
    }

    /**
     * Establece la acción realizada
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $this->formatString($action);
    }

    /**
     * Devuelve la descripción de la acción realizada
     *
     * @param bool $translate
     * @return string
     */
    public function getDescription($translate = false)
    {
        if (null === $this->description) {
            return '';
        }

        if (count($this->description) > 1) {
            if ($translate === true) {
                return implode(PHP_EOL, array_map('__', $this->description));
            }

            return implode(PHP_EOL, $this->description);
        }

        return $translate === true ? __($this->description[0]) : $this->description[0];
    }

    /**
     * Devuelve la descripción de la acción realizada en formato HTML
     *
     * @param bool $translate
     * @return string
     */
    public function getHtmlDescription($translate = false) {
        return nl2br($this->getDescription($translate));
    }

    /**
     * Añadir detalle en formato HTML. Se resalta el texto clave.
     *
     * @param $key   string
     * @param $value string
     */
    public function addDetailsHtml($key, $value)
    {
        $this->addDetails(Html::strongText($key), $value);
    }

    /**
     * Establece los detalles de la acción realizada
     *
     * @param $key   string
     * @param $value string
     */
    public function addDetails($key, $value)
    {
        $this->details[] = [$this->formatString($key), $this->formatString($value)];
    }

    /**
     * Establecer el formato de nueva línea a HTML
     *
     * @param $bool bool
     */
    public function setNewLineHtml($bool)
    {
        $this->newLineHtml = $bool;
    }

    /**
     * Restablecer la variable de descripcion
     */
    public function resetDescription()
    {
        $this->description = null;
    }
}