<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Mvc\View;

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Http\Uri;

use function SP\__;
use function SP\logger;

/**
 * Class Template
 *
 * A very basic template engine...
 *
 * Idea original de http://www.sitepoint.com/author/agervasio/
 * publicada en http://www.sitepoint.com/flexible-view-manipulation-1/
 *
 */
final class Template implements TemplateInterface
{
    private const TEMPLATE_EXTENSION = '.inc';
    public const  PARTIALS_DIR       = '_partials';

    /**
     * @var array List of templates to load into the view
     */
    private array                 $templates = [];
    private TemplateVarCollection $vars;
    /**
     * @var string Base path for imcluding templates
     */
    private string $base;
    private array  $contentTemplates = [];
    private bool   $upgraded         = false;

    public function __construct(
        protected readonly ThemeInterface    $theme,
        private readonly UriContextInterface $uriContext,
        private readonly ConfigDataInterface $configData
    ) {
        $this->vars = new TemplateVarCollection();
    }

    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param string $name Con el nombre del archivo de plantilla
     * @param string|null $base Directorio base para la plantilla
     */
    public function addContentTemplate(string $name, ?string $base = null): string
    {
        try {
            $template = $this->checkTemplate($name, $base);
            $this->setContentTemplate($template, $name);
        } catch (FileNotFoundException $e) {
            logger($e->getMessage(), 'WARN');

            return '';
        }

        return $template;
    }

    /**
     * Comprobar si un archivo de plantilla existe y se puede leer
     *
     * @param string $template Con el nombre del archivo
     * @param string|null $base Directorio base para la plantilla
     *
     * @return string La ruta al archivo de la plantilla
     *
     * @throws FileNotFoundException
     */
    private function checkTemplate(
        string $template,
        ?string $base = null
    ): string {
        $base = $base ?? $this->base;

        if ($base === null) {
            $templateFile = $this->theme->getViewsPath() . DIRECTORY_SEPARATOR . $template . self::TEMPLATE_EXTENSION;
        } elseif (str_starts_with($base, APP_ROOT) && is_dir($base)) {
            $templateFile = $base . DIRECTORY_SEPARATOR . $template . self::TEMPLATE_EXTENSION;
        } else {
            $templateFile = $this->theme->getViewsPath() . DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . $template
                            . self::TEMPLATE_EXTENSION;
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
    private function setContentTemplate(string $file, string $name): void
    {
        $this->contentTemplates[$name] = $file;
    }

    /**
     * Removes a template from the stack
     */
    public function removeTemplate(string $name): TemplateInterface
    {
        unset($this->templates[$name]);

        return $this;
    }

    /**
     * Removes a template from the stack
     */
    public function removeContentTemplate(string $name): TemplateInterface
    {
        unset($this->contentTemplates[$name]);

        return $this;
    }

    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param string $name Con el nombre del archivo de plantilla
     * @param string|null $base Directorio base para la plantilla
     *
     * @return string
     */
    public function addTemplate(string $name, ?string $base = null): string
    {
        try {
            $template = $this->checkTemplate($name, $base);
            $this->setTemplate($template, $name);
        } catch (FileNotFoundException) {
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
    private function setTemplate(string $file, string $name): void
    {
        $this->templates[$name] = $file;
    }

    /**
     * Añadir una nueva plantilla dentro de una plantilla
     *
     * @param string $file Con el nombre del archivo de plantilla
     *
     * @return bool|string
     */
    public function includePartial(string $file): bool|string
    {
        return $this->includeTemplate($file, self::PARTIALS_DIR);
    }

    /**
     * Añadir una nueva plantilla dentro de una plantilla
     *
     * @param string $file Con el nombre del archivo de plantilla
     * @param string|null $base Directorio base para la plantilla
     *
     * @return bool|string
     */
    public function includeTemplate(string $file, ?string $base = null): bool|string
    {
        try {
            return $this->checkTemplate($file, $base);
        } catch (FileNotFoundException) {
            return false;
        }
    }

    /**
     * Overloading para controlar la devolución de atributos dinámicos.
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Overloading para añadir nuevas variables en al array de variables dela plantilla
     * pasadas como atributos dinámicos de la clase
     *
     * @param string $name Nombre del atributo
     * @param string $value Valor del atributo
     */
    public function __set(string $name, string $value)
    {
        $this->vars->set($name, $value);
    }

    /**
     * Returns a variable value
     */
    public function get(string $name)
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
    public function __isset(string $name): bool
    {
        return $this->vars->exists($name);
    }

    /**
     * Overloading para eliminar una variable del array de variables de la plantilla pasado como
     * atributo dinámico de la clase
     */
    public function __unset(string $name): void
    {
        if (!$this->vars->exists($name)) {
            logger(sprintf(__('Unable to unset "%s" variable'), $name));
        } else {
            $this->vars->offsetUnset($name);
        }
    }

    /**
     * Mostrar la plantilla solicitada.
     * La salida se almacena en buffer y se devuelve el contenido
     *
     * @return string Con el contenido del buffer de salida
     * @throws FileNotFoundException
     */
    public function render(): string
    {
        if (count($this->templates) === 0) {
            throw new FileNotFoundException(__('Template does not contain files'));
        }

        $icons = $this->theme->getIcons();
        $this->vars->set('configData', $this->configData);

        // An anonymous proxy function for handling views variables
        $_getvar = function (string $key, mixed $default = null) {
            if (DEBUG && !$this->vars->exists($key)) {
                logger(sprintf(__('Unable to retrieve "%s" variable'), $key), 'WARN');

                return $default;
            }

            return $this->vars->get($key, $default);
        };

        $_getRoute = function (string $path) {
            $baseUrl = ($this->configData->getApplicationUrl() ?: $this->uriContext->getWebUri()) .
                       $this->uriContext->getSubUri();

            $uri = new Uri($baseUrl);
            $uri->addParam('r', $path);

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
     * @param string $name nombre de la variable
     * @param mixed $value valor de la variable
     * @param string|null $scope string ámbito de la variable
     * @param int|null $index string índice del array
     */
    public function append(
        string $name,
        mixed  $value,
        ?string $scope = null,
        int    $index = null
    ): void {
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
    public function resetTemplates(): TemplateInterface
    {
        $this->templates = [];

        return $this;
    }

    /**
     * Reset de las plantillas añadidas
     */
    public function resetContentTemplates(): TemplateInterface
    {
        $this->contentTemplates = [];

        return $this;
    }

    public function getBase(): string
    {
        return $this->base;
    }

    public function setBase(string $base): void
    {
        $this->base = $base;
    }

    public function getTheme(): ThemeInterface
    {
        return $this->theme;
    }

    public function getContentTemplates(): array
    {
        return $this->contentTemplates;
    }

    public function hasContentTemplates(): bool
    {
        return count($this->contentTemplates) > 0;
    }

    /**
     * Assigns the current templates to contentTemplates
     */
    public function upgrade(): TemplateInterface
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
     * @param string $name nombre de la variable
     * @param mixed $value valor de la variable
     * @param string|null $scope string ámbito de la variable
     */
    public function assign(string $name, $value = '', ?string $scope = null): void
    {
        if (null !== $scope) {
            $name = $scope . '_' . $name;
        }

        $this->vars->set($name, $value);
    }

    public function isUpgraded(): bool
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
