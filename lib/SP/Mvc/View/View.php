<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Mvc\View;

/**
 * Class View
 *
 * @package SP\Lib\Mvc\View
 */
final class View implements ViewInterface
{
    /**
     * @var array
     */
    protected $vars = [];
    /**
     * @var \Twig_Environment
     */
    protected $view;
    /**
     * @var array
     */
    protected $template = [
        'namespace' => '',
        'controller' => '',
        'action' => ''
    ];
    /**
     * @var string
     */
    protected $layouts = '_layouts';
    /**
     * @var string
     */
    protected $partials = '_partials';

    /**
     * View constructor.
     *
     * @param \Twig_Environment $view
     */
    public function __construct(\Twig_Environment $view)
    {
        $this->view = $view;
        $this->vars['view'] = $this;
    }

    /**
     * Asignar una variable
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function assign($name, $value)
    {
        if ($name === 'view') {
            return $this;
        }

        $this->vars[$name] = $value;

        return $this;
    }

    /**
     * Establecer variables
     *
     * @param array $vars
     *
     * @return $this
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * Renderizar plantilla
     *
     * @param        $template
     * @param string $path
     *
     * @return string
     */
    public function render($template, $path = null)
    {
        $this->template['action'] = $template;

        if (null !== $path) {
            return $this->view->load($this->template['namespace'] . '/' . trim($path, '/') . '/' . $template)->render($this->vars);
        }

        return $this->view->load(implode('/', $this->template))->render($this->vars);
    }

    /**
     * Establecer namespace para las plantillas
     *
     * @param $name
     *
     * @return $this
     */
    public function setNamespace($name)
    {
        $this->template['namespace'] = '@' . $name;

        return $this;
    }

    /**
     * Establecer el controlador
     *
     * @param $name
     *
     * @return $this
     */
    public function setController($name)
    {
        $this->template['controller'] = $name;

        return $this;
    }

    /**
     * Devolver el namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->template['namespace'];
    }

    /**
     * Devolver el controlador
     *
     * @return string
     */
    public function getController()
    {
        return $this->template['controller'];
    }

    /**
     * Devolver el directorio a la vista actual
     *
     * @return string
     */
    public function getDir()
    {
        return $this->template['namespace'] . '/' . $this->template['controller'];
    }

    /**
     * Devolver el directorio de layouts
     *
     * @return string
     */
    public function getLayoutsDir()
    {
        return $this->template['namespace'] . '/' . $this->layouts;
    }

    /**
     * Devolver el directorio de layouts
     *
     * @return string
     */
    public function getLayouts()
    {
        return $this->layouts;
    }

    /**
     * Establecer el directorio de layouts
     *
     * @param string $layouts
     */
    public function setLayouts($layouts)
    {
        $this->layouts = $layouts;
    }

    /**
     * Devolver una variable
     *
     * @param $name
     *
     * @return mixed
     */
    public function get($name)
    {
        return isset($this->vars[$name]) ? $this->vars[$name] : null;
    }

    /**
     * Devolver la ruta a los partials
     *
     * @return string
     */
    public function getPartialsDir()
    {
        return $this->template['namespace'] . '/' . $this->partials;
    }

    /**
     * Devolver el directorio de partials
     *
     * @return string
     */
    public function getPartials()
    {
        return $this->partials;
    }

    /**
     * Establecer el directorio de partials
     *
     * @param string $partials
     */
    public function setPartials($partials)
    {
        $this->partials = $partials;
    }
}