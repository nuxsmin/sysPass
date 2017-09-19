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

namespace SP\Core;

defined('APP_ROOT') || die();

use InvalidArgumentException;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SPException;
use SP\Core\UI\ThemeInterface;

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
     * @var ThemeInterface
     */
    protected $Theme;
    /**
     * @var array Variable con los archivos de plantilla a cargar
     */
    private $file = [];
    /**
     * @var array Variable con las variables a incluir en la plantilla
     */
    private $vars = [];
    /**
     * @var string Directorio base para los archivos de plantillas
     */
    private $base;

    /**
     * @param null  $file Archivo de plantilla a añadir
     * @param array $vars Variables a inicializar
     */
    public function __construct($file = null, array $vars = array())
    {
        if (null !== $file) {
            $this->addTemplate($file);
        }

        if (!empty($vars)) {
            $this->setVars($vars);
        }

        $this->Theme = DiFactory::getTheme();
    }

    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param string $file Con el nombre del archivo de plantilla
     * @param string $base Directorio base para la plantilla
     * @return bool
     */
    public function addTemplate($file, $base = null)
    {
        try {
            $template = $this->checkTemplate($file, $base);
            $this->setTemplate($template);
        } catch (FileNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * Comprobar si un archivo de plantilla existe y se puede leer
     *
     * @param string $template Con el nombre del archivo
     * @return string La ruta al archivo de la plantilla
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @param string $base     Directorio base para la plantilla
     */
    private function checkTemplate($template, $base = null)
    {
        $useBase = false;

        if (null !== $base) {
            $template = $base . DIRECTORY_SEPARATOR . $template . '.inc';

            $useBase = is_readable($template);
        } elseif (null !== $this->base) {
            $template = $this->base . DIRECTORY_SEPARATOR . $template . '.inc';

            $useBase = is_readable($template);
        } else {
            $template .= '.inc';
        }

        $file = ($useBase === false) ? $this->Theme->getViewsPath() . DIRECTORY_SEPARATOR . $template : $template;

        if (!is_readable($file)) {
            debugLog(sprintf(__('No es posible obtener la plantilla "%s" : %s'), $file, $template));
//            Log::writeNewLog(__FUNCTION__, sprintf(__('No es posible obtener la plantilla "%s" : %s'), $file, $template), Log::ERROR);
            throw new FileNotFoundException(SPException::SP_ERROR, sprintf(__('No es posible obtener la plantilla "%s" : %s'), $file, $template));
        }

        return $file;
    }

    /**
     * Añadir un nuevo archivo de plantilla al array de plantillas de la clase.
     *
     * @param string $file Con el nombre del archivo
     */
    private function setTemplate($file)
    {
        $this->file[] = $file;
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
     * Añadir una nueva plantilla dentro de una plantilla
     *
     * @param string $file Con el nombre del archivo de plantilla
     * @param string $base Directorio base para la plantilla
     * @return bool
     */
    public function includeTemplate($file, $base = null)
    {
        try {
            return $this->checkTemplate($file, $base);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Overloading para controlar la devolución de atributos dinámicos.
     *
     * @param string $name Nombre del atributo
     * @return null
     * @throws InvalidArgumentException
     */
    public function __get($name)
    {
        if (!array_key_exists($name, $this->vars)) {
            debugLog(sprintf(__('No es posible obtener la variable "%s"'), $name));

            throw new InvalidArgumentException(sprintf(__('No es posible obtener la variable "%s"'), $name));
        }

        return $this->vars[$name];
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
        $this->vars[$name] = $value;
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
        return array_key_exists($name, $this->vars);
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
        if (!array_key_exists($name, $this->vars)) {
            debugLog(sprintf(__('No es posible destruir la variable "%s"'), $name));

            throw new InvalidArgumentException(sprintf(__('No es posible destruir la variable "%s"'), $name));
        }

        unset($this->vars[$name]);
        return $this;
    }

    /**
     * Mostrar la plantilla solicitada.
     * La salida se almacena en buffer y se devuelve el contenido
     *
     * @return string Con el contenido del buffer de salida
     * @throws FileNotFoundException
     */
    public function render()
    {
        if (count($this->file) === 0) {
            throw new FileNotFoundException(SPException::SP_ERROR, __('La plantilla no contiene archivos'));
        }

        extract($this->vars, EXTR_SKIP);

        ob_start();

        // Añadimos las plantillas
        foreach ($this->file as $template) {
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
        if (null !== $scope) {
            $name = $scope . '_' . $name;
        }

        $this->vars[$name] = $value;
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
        if (null !== $scope) {
            $name = $scope . '_' . $name;
        }

        if (null !== $index) {
            $this->vars[$name][$index] = $value;
        } else {
            $this->vars[$name][] = $value;
        }
    }

    /**
     * Reset de las plantillas añadidas
     */
    public function resetTemplates()
    {
        $this->file = [];
    }

    /**
     * Reset de las plantillas añadidas
     */
    public function resetVariables()
    {
        $this->vars = [];
    }

    /**
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @param string $base
     */
    public function setBase($base)
    {
        $this->base = $base;
    }
}