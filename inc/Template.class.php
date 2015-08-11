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

use InvalidArgumentException;

/**
 * Clase Template para la manipulación de plantillas
 *
 * Motor de plantillas muy básico...
 *
 * Idea original de http://www.sitepoint.com/author/agervasio/
 * publicada en http://www.sitepoint.com/flexible-view-manipulation-1/
 *
 */
class Template
{
    /**
     * @var array Variable con los archivos de plantilla a cargar
     */
    private $_file = array();
    /**
     * @var array Variable con las variables a incluir en la plantilla
     */
    private $_vars = array();

    /**
     * @param null  $file Archivo de plantilla a añadir
     * @param array $vars Variables a inicializar
     */
    public function __construct($file = null, array $vars = array())
    {
        $this->addTemplate($file);

        if (!empty($vars)) {
            $this->setVars($vars);
        }
    }

    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param string $file Con el nombre del archivo de plantilla
     * @return bool
     */
    public function addTemplate($file)
    {
        if (!is_null($file) && $this->checkTemplate($file)) {
            return true;
        }

        return false;
    }

    /**
     * Comprobar si un archivo de plantilla existe y se puede leer
     *
     * @param string $file Con el nombre del archivo
     * @return bool
     * @throws InvalidArgumentException
     */
    private function checkTemplate($file)
    {
        $template = VIEW_PATH . DIRECTORY_SEPARATOR . Init::$THEME . DIRECTORY_SEPARATOR . $file . '.inc';

        if (!is_readable($template)) {
            throw new InvalidArgumentException('No es posible obtener la plantilla "' . $file . '"');
        }

        $this->setTemplate($template);
        return true;
    }

    /**
     * Añadir un nuevo archivo de plantilla al array de plantillas de la clase.
     *
     * @param string $file Con el nombre del archivo
     */
    private function setTemplate($file)
    {
        $this->_file[] = $file;
    }

    /**
     * Establecer los atributos de la clase a partir de un array.
     *
     * @param array $vars Con los atributos de la clase
     */
    private function setVars(&$vars)
    {
        foreach ($vars as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Overloadig para controlar la devolución de atributos dinámicos.
     *
     * @param string $name Nombre del atributo
     * @return null
     * @throws InvalidArgumentException
     */
    public function __get($name)
    {
        if (!array_key_exists($name, $this->_vars)) {
            throw new InvalidArgumentException('No es posible obtener la variable "' . $name . '"');
        }

        return $this->_vars[$name];
    }

    /**
     * Overloading para añadir nuevas variables en al array de variables dela plantilla
     * pasadas como atributos dinámicos de la clase
     *
     * @param string $name  Nombre del atributo
     * @param string $value Valor del atributo
     * @return null
     */
    public function __set($name, $value)
    {
        $this->_vars[$name] = $value;
        return null;
    }

    /**
     * Overloading para comprobar si el atributo solicitado está declarado como variable
     * en el array de variables de la plantilla.
     *
     * @param string $name Nombre del atributo
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->_vars);
    }

    /**
     * Overloading para eliminar una variable del array de variables de la plantilla pasado como
     * atributo dinámico de la clase
     *
     * @param string $name Nombre del atributo
     * @return $this
     * @throws InvalidArgumentException
     */
    public function __unset($name)
    {
        if (!isset($this->_vars[$name])) {
            throw new InvalidArgumentException('No es posible destruir la variable "' . $name . '"');
        }

        unset($this->_vars[$name]);
        return $this;
    }

    /**
     * Mostrar la plantilla solicitada.
     * La salida se almacena en buffer y se devuelve el contenido
     *
     * @return string Con el contenido del buffer de salida
     */
    public function render()
    {
        extract($this->_vars);

        ob_start();

        // Añadimos las plantillas
        foreach ($this->_file as $template) {
            include_once $template;
        }

        return ob_get_clean();
    }

    /**
     * Crear la variable y asignarle un valor en el array de variables
     *
     * @param      $name  string nombre de la variable
     * @param      $value mixed valor de la variable
     * @param null $scope string ámbito de la variable
     */
    public function assign($name, $value = '', $scope = null)
    {
        if (!is_null($scope)) {
            $name = $scope . '_' . $name;
        }

//        error_log('SET: ' . $name . ' -> ' . $value);

        $this->_vars[$name] = $value;
    }

    /**
     * Anexar el valor de la variable al array de la misma en el array de variables
     *
     * @param      $name  string nombre de la variable
     * @param      $value mixed valor de la variable
     * @param      $index string índice del array
     * @param null $scope string ámbito de la variable
     */
    public function append($name, $value, $scope = null, $index = null)
    {
        if (!is_null($scope)) {
            $name = $scope . '_' . $name;
        }

        if (!is_null($index)) {
            $this->_vars[$name][$index] = $value;
        } else {
            $this->_vars[$name][] = $value;
        }
    }

    /**
     * Reset de las plantillas añadidas
     */
    public function resetTemplates()
    {
        $this->_file = array();
    }

    /**
     * Reset de las plantillas añadidas
     */
    public function resetVariables()
    {
        $this->_vars = array();
    }
}