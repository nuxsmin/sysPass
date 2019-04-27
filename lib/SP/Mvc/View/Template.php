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

namespace SP\Mvc\View;

defined('APP_ROOT') || die();

use SP\Bootstrap;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\UI\ThemeInterface;
use SP\Http\Uri;

/**
 * Class Template
 *
 * A very basic template engine...
 *
 * Idea original de http://www.sitepoint.com/author/agervasio/
 * publicada en http://www.sitepoint.com/flexible-view-manipulation-1/
 *
 */
final class Template
{
    const TEMPLATE_EXTENSION = '.inc';
    const PARTIALS_DIR = '_partials';
    const LAYOUTS_DIR = '_layouts';

    /**
     * @var ThemeInterface
     */
    protected $theme;
    /**
     * @var array List of templates to load into the view
     */
    private $templates = [];
    /**
     * @var TemplateVarCollection Template's variables collection
     */
    private $vars;
    /**
     * @var string Base path for imcluding templates
     */
    private $base;
    /**
     * @var array
     */
    private $contentTemplates = [];
    /**
     * @var bool
     */
    private $upgraded = false;

    /**
     * @param ThemeInterface $theme
     */
    public function __construct(ThemeInterface $theme)
    {
        $this->theme = $theme;
        $this->vars = new TemplateVarCollection();
    }

    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param string $name Con el nombre del archivo de plantilla
     * @param string $base Directorio base para la plantilla
     *
     * @return bool
     */
    public function addContentTemplate($name, $base = null)
    {
        try {
            $template = $this->checkTemplate($name, $base);
            $this->setContentTemplate($template, $name);
        } catch (FileNotFoundException $e) {
            return '';
        }

        return $template;
    }

    /**
     * Comprobar si un archivo de plantilla existe y se puede leer
     *
     * @param string $template Con el nombre del archivo
     * @param string $base     Directorio base para la plantilla
     *
     * @return string La ruta al archivo de la plantilla
     *
     * @throws FileNotFoundException
     */
    private function checkTemplate($template, $base = null)
    {
        $base = null !== $base ? $base : (null !== $this->base ? $this->base : null);

        if ($base === null) {
            $templateFile = $this->theme->getViewsPath() . DIRECTORY_SEPARATOR . $template . self::TEMPLATE_EXTENSION;
        } elseif (strpos($base, APP_ROOT) === 0
            && is_dir($base)
        ) {
            $templateFile = $base . DIRECTORY_SEPARATOR . $template . self::TEMPLATE_EXTENSION;
        } else {
            $templateFile = $this->theme->getViewsPath() . DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . $template . self::TEMPLATE_EXTENSION;
        }

        if (!is_readable($templateFile)) {
            $msg = sprintf(__('Unable to retrieve "%s" template: %s'), $templateFile, $template);

            logger($msg);

            throw new FileNotFoundException($msg);
        }

        return $templateFile;
    }

    /**
     * Añadir un nuevo archivo de plantilla al array de plantillas de contenido
     *
     * @param string $file Con el nombre del archivo
     * @param string $name Nombre de la plantilla
     */
    private function setContentTemplate($file, $name)
    {
        $this->contentTemplates[$name] = $file;
    }

    /**
     * Removes a template from the stack
     *
     * @param $name
     *
     * @return Template
     */
    public function removeTemplate($name)
    {
        unset($this->templates[$name]);

        return $this;
    }

    /**
     * Removes a template from the stack
     *
     * @param $name
     *
     * @return Template
     */
    public function removeContentTemplate($name)
    {
        unset($this->contentTemplates[$name]);

        return $this;
    }

    /**
     * Removes a template from the stack
     *
     * @param string $src Source template
     * @param string $dst Destination template
     * @param string $base
     *
     * @return mixed|string
     */
    public function replaceTemplate($src, $dst, $base)
    {
        try {
            if (isset($this->contentTemplates[$dst])) {
                $this->contentTemplates[$dst] = $this->checkTemplate($src, $base);
            }
        } catch (FileNotFoundException $e) {
            return '';
        }

        return $this->contentTemplates[$dst];
    }

    /**
     * Add partial template
     *
     * @param $partial
     */
    public function addPartial($partial)
    {
        $this->addTemplate($partial, self::PARTIALS_DIR);
    }

    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param string $name Con el nombre del archivo de plantilla
     * @param string $base Directorio base para la plantilla
     *
     * @return bool
     */
    public function addTemplate($name, $base = null)
    {
        try {
            $template = $this->checkTemplate($name, $base);
            $this->setTemplate($template, $name);
        } catch (FileNotFoundException $e) {
            return '';
        }

        return $template;
    }

    /**
     * Añadir un nuevo archivo de plantilla al array de plantillas
     *
     * @param string $file Con el nombre del archivo
     * @param string $name Nombre de la plantilla
     */
    private function setTemplate($file, $name)
    {
        $this->templates[$name] = $file;
    }

    /**
     * Añadir una nueva plantilla dentro de una plantilla
     *
     * @param string $file Con el nombre del archivo de plantilla
     *
     * @return bool
     */
    public function includePartial($file)
    {
        return $this->includeTemplate($file, self::PARTIALS_DIR);
    }

    /**
     * Añadir una nueva plantilla dentro de una plantilla
     *
     * @param string $file Con el nombre del archivo de plantilla
     * @param string $base Directorio base para la plantilla
     *
     * @return bool
     */
    public function includeTemplate($file, $base = null)
    {
        try {
            return $this->checkTemplate($file, $base);
        } catch (FileNotFoundException $e) {
            return false;
        }
    }

    /**
     * Overloading para controlar la devolución de atributos dinámicos.
     *
     * @param string $name Nombre del atributo
     *
     * @return null
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Overloading para añadir nuevas variables en al array de variables dela plantilla
     * pasadas como atributos dinámicos de la clase
     *
     * @param string $name  Nombre del atributo
     * @param string $value Valor del atributo
     */
    public function __set($name, $value)
    {
        $this->vars->set($name, $value);
    }

    /**
     * Returns a variable value
     *
     * @param $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if (!$this->vars->exists($name)) {
            logger(sprintf(__('Unable to retrieve "%s" variable'), $name), 'ERROR');

            return null;
//            throw new InvalidArgumentException(sprintf(__('Unable to retrieve "%s" variable'), $name));
        }

        return $this->vars->get($name);
    }

    /**
     * Overloading para comprobar si el atributo solicitado está declarado como variable
     * en el array de variables de la plantilla.
     *
     * @param string $name Nombre del atributo
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->vars->exists($name);
    }

    /**
     * Overloading para eliminar una variable del array de variables de la plantilla pasado como
     * atributo dinámico de la clase
     *
     * @param string $name Nombre del atributo
     *
     * @return $this
     */
    public function __unset($name)
    {
        if (!$this->vars->exists($name)) {
            logger(sprintf(__('Unable to unset "%s" variable'), $name));

//            throw new InvalidArgumentException(sprintf(__('Unable to unset "%s" variable'), $name));
            return $this;
        }

        $this->vars->remove($name);

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
        if (empty($this->templates)) {
            throw new FileNotFoundException(__('Template does not contain files'));
        }

        $icons = $this->theme->getIcons();
        $configData = $this->vars->get('configData');
        $sk = $this->vars->get('sk');

        // An anonymous proxy function for handling views variables
        $_getvar = function ($key, $default = null) {
            if (DEBUG && !$this->vars->exists($key)) {
                logger(sprintf(__('Unable to retrieve "%s" variable'), $key), 'WARN');

                return $default;
            }

            return $this->vars->get($key, $default);
        };

        $_getRoute = function ($path) use ($sk, $configData) {
            $baseUrl = ($configData->getApplicationUrl() ?: Bootstrap::$WEBURI) . Bootstrap::$SUBURI;

            $uri = new Uri($baseUrl);
            $uri->addParam('r', $path);
            $uri->addParam('sk', $sk);

            return $uri->getUri();
        };

        ob_start();

        // Añadimos las plantillas
        foreach ($this->templates as $template) {
            include_once $template;
        }

        return ob_get_clean();
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

        $var = $this->vars->get($name, []);

        if (null === $index) {
            $var[] = $value;
        } else {
            $var[$index] = $value;
        }

        $this->vars->set($name, $var);
    }

    /**
     * Reset de las plantillas añadidas
     */
    public function resetTemplates()
    {
        $this->templates = [];

        return $this;
    }

    /**
     * Reset de las plantillas añadidas
     */
    public function resetContentTemplates()
    {
        $this->contentTemplates = [];

        return $this;
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

    /**
     * @return ThemeInterface
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Dumps current stored vars
     */
    public function dumpVars()
    {
        logger($this->vars);
    }

    /**
     * @return array
     */
    public function getContentTemplates()
    {
        return $this->contentTemplates;
    }

    /**
     * @return bool
     */
    public function hashContentTemplates()
    {
        return count($this->contentTemplates) > 0;
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Assigns the current templates to contentTemplates
     *
     * @return $this
     */
    public function upgrade()
    {
        if (count($this->templates) > 0) {
            $this->contentTemplates = $this->templates;

            $this->templates = [];

            $this->upgraded = true;
        }

        return $this;
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

        $this->vars->set($name, $value);
    }

    /**
     * @return bool
     */
    public function isUpgraded()
    {
        return $this->upgraded;
    }

    /**
     * When an object is cloned, PHP 5 will perform a shallow copy of all of the object's properties.
     * Any properties that are references to other variables, will remain references.
     * Once the cloning is complete, if a __clone() method is defined,
     * then the newly created object's __clone() method will be called, to allow any necessary properties that need to be changed.
     * NOT CALLABLE DIRECTLY.
     *
     * @link https://php.net/manual/en/language.oop5.cloning.php
     */
    public function __clone()
    {
        // Clone TemplateVarCollection to avoid unwanted object references
        $this->vars = clone $this->vars;
    }

}
