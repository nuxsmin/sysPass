<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\Http\Providers\Uri;

use function SP\__u;
use function SP\logger;

/**
 * Class Template
 *
 * A very basic template engine...
 *
 * Original idea: http://www.sitepoint.com/author/agervasio/
 * Published on: http://www.sitepoint.com/flexible-view-manipulation-1/
 *
 */
final class Template implements TemplateInterface
{
    private const  PARTIALS_DIR = '_partials';
    private const  LAYOUTS_DIR  = '_layouts';

    private TemplateCollection $templates;
    private TemplateCollection $contentTemplates;
    private TemplateCollection $vars;
    private bool               $upgraded = false;
    private readonly string $baseUrl;

    public function __construct(
        private readonly OutputHandlerInterface    $outputHandler,
        private readonly TemplateResolverInterface $templateResolver,
        protected readonly ThemeIconsInterface     $themeIcons,
        private readonly UriContextInterface       $uriContext,
        private readonly ConfigDataInterface       $configData,
        private readonly string                    $base
    ) {
        $this->vars = new TemplateCollection();
        $this->templates = new TemplateCollection();
        $this->contentTemplates = new TemplateCollection();
        $this->baseUrl = ($this->configData->getApplicationUrl() ?: $this->uriContext->getWebUri()) .
                         $this->uriContext->getSubUri();
    }

    /**
     * @inheritDoc
     * @throws FileNotFoundException
     */
    public function setLayout(string $name): void
    {
        $this->templates->set($name, $this->templateResolver->getTemplateFor(self::LAYOUTS_DIR, $name));
    }

    /**
     * @inheritDoc
     * @throws FileNotFoundException
     */
    public function addPartial(string $name): void
    {
        $this->templates->set($name, $this->templateResolver->getTemplateFor(self::PARTIALS_DIR, $name));
    }

    /**
     * @inheritDoc
     * @throws FileNotFoundException
     */
    public function addContentTemplate(string $name, ?string $base = null): void
    {
        $this->contentTemplates->set($name, $this->templateResolver->getTemplateFor($base ?? $this->base, $name));
    }

    /**
     * Removes a template from the stack
     */
    public function remove(string $name): void
    {
        $this->templates->offsetUnset($name);
        $this->contentTemplates->offsetUnset($name);
    }

    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param string $name Con el nombre del archivo de plantilla
     * @param string|null $base Directorio base para la plantilla
     *
     * @throws FileNotFoundException
     */
    public function addTemplate(string $name, ?string $base = null): void
    {
        $this->templates->set($name, $this->templateResolver->getTemplateFor($base ?? $this->base, $name));
    }

    /**
     * @inheritDoc
     * @throws FileNotFoundException
     */
    public function includePartial(string $name): string
    {
        return $this->templateResolver->getTemplateFor(self::PARTIALS_DIR, $name);
    }

    /**
     * @inheritDoc
     * @throws FileNotFoundException
     */
    public function includeTemplate(string $name, ?string $base = null): string
    {
        return $this->templateResolver->getTemplateFor($base ?? $this->base, $name);
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        if ($this->templates->count() === 0) {
            logger(__u('Template does not contain files'));

            return '';
        }

        $this->vars->set('configData', $this->configData);
        $this->vars->set('upgraded', $this->upgraded);

        return $this->outputHandler->bufferedContent($this->includeTemplates(...));
    }

    /**
     * @inheritDoc
     */
    public function append(string $name, mixed $value): void
    {
        $var = $this->vars->get($name, []);

        $var[] = $value;

        $this->vars->set($name, $var);
    }

    /**
     * @inheritDoc
     */
    public function reset(): void
    {
        $this->templates->exchangeArray([]);
        $this->contentTemplates->exchangeArray([]);
    }

    /**
     * TODO: remove
     */
    public function getBase(): string
    {
        return $this->base;
    }

    public function getContentTemplates(): array
    {
        return $this->contentTemplates->getArrayCopy();
    }

    /**
     * Assigns the current templates to contentTemplates
     */
    public function upgrade(): void
    {
        if (!$this->upgraded && $this->templates->count() > 0) {
            $this->contentTemplates->exchangeArray($this->templates->getArrayCopy());
            $this->templates->exchangeArray([]);
        }

        $this->upgraded = true;
    }

    /**
     * @inheritDoc
     */
    public function assign(string $name, mixed $value): void
    {
        $this->vars->set($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function assignWithScope(string $name, mixed $value, string $scope): void
    {
        $this->vars->set(sprintf('%s_%s', $scope, $name), $value);
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

    protected function includeTemplates(): void
    {
        // These variables will be included in the same scope as included files
        $icons = $this->themeIcons;
        $_getvar = $this->vars->get(...);
        $_getRoute = $this->getRoute(...);
        $configData = clone $this->configData;

        foreach ($this->templates as $template) {
            include_once $template;
        }
    }

    protected function getRoute(string $path): string
    {
        return (new Uri($this->baseUrl))->addParam('r', $path)->getUri();
    }
}
