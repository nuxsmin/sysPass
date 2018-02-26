<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Lib\Mvc\View;

/**
 * Interface ViewInterface
 *
 * @package SP\Lib\Mvc\View
 */
interface ViewInterface
{
    /**
     * Añadir una variable
     *
     * @param $name
     * @param $value
     * @return $this
     */
    public function assign($name, $value);

    /**
     * Devolver una variable
     *
     * @param $name
     * @return mixed
     */
    public function get($name);

    /**
     * Establecer variables
     *
     * @param array $vars
     * @return $this
     */
    public function setVars(array $vars);

    /**
     * Renderizar plantilla
     *
     * @param $template
     * @param null $path
     * @return string
     */
    public function render($template, $path = null);

    /**
     * Establecer namespace para las plantillas
     *
     * @param $name
     * @return $this
     */
    public function setNamespace($name);

    /**
     * Establecer el controlador
     *
     * @param $name
     * @return $this
     */
    public function setController($name);

    /**
     * Devolver el namespace
     *
     * @return string
     */
    public function getNamespace();

    /**
     * Devolver el controlador
     *
     * @return string
     */
    public function getController();

    /**
     * Devolver la ruta a la vista actual
     *
     * @return string
     */
    public function getDir();

    /**
     * Devolver la ruta a los layouts
     *
     * @return string
     */
    public function getLayoutsDir();

    /**
     * Devolver el directorio de layouts
     *
     * @return string
     */
    public function getLayouts();

    /**
     * Establecer el directorio de layouts
     *
     * @param string $layouts
     */
    public function setLayouts($layouts);

    /**
     * Devolver la ruta a los partials
     *
     * @return string
     */
    public function getPartialsDir();

    /**
     * Devolver el directorio de partials
     *
     * @return string
     */
    public function getPartials();

    /**
     * Establecer el directorio de partials
     *
     * @param string $partials
     */
    public function setPartials($partials);
}