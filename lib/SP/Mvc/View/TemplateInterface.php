<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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


use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\UI\ThemeInterface;

/**
 * Class Template
 *
 * A very basic template engine...
 *
 * Idea original de http://www.sitepoint.com/author/agervasio/
 * publicada en http://www.sitepoint.com/flexible-view-manipulation-1/
 *
 */
interface TemplateInterface
{
    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param  string  $name  Con el nombre del archivo de plantilla
     * @param  string|null  $base  Directorio base para la plantilla
     */
    public function addContentTemplate(string $name, ?string $base = null): string;

    /**
     * Removes a template from the stack
     */
    public function removeTemplate(string $name): TemplateInterface;

    /**
     * Removes a template from the stack
     */
    public function removeContentTemplate(string $name): TemplateInterface;

    /**
     * Removes a template from the stack
     *
     * @param  string  $src  Source template
     * @param  string  $dst  Destination template
     * @param  string  $base
     *
     * @return mixed|string
     */
    public function replaceTemplate(string $src, string $dst, string $base);

    /**
     * Add partial template
     */
    public function addPartial(string $partial): void;

    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param  string  $name  Con el nombre del archivo de plantilla
     * @param  string|null  $base  Directorio base para la plantilla
     *
     * @return string
     */
    public function addTemplate(string $name, ?string $base = null): string;

    /**
     * Añadir una nueva plantilla dentro de una plantilla
     *
     * @param  string  $file  Con el nombre del archivo de plantilla
     *
     * @return bool
     */
    public function includePartial(string $file);

    /**
     * Añadir una nueva plantilla dentro de una plantilla
     *
     * @param  string  $file  Con el nombre del archivo de plantilla
     * @param  string|null  $base  Directorio base para la plantilla
     *
     * @return bool
     */
    public function includeTemplate(string $file, ?string $base = null);

    /**
     * Returns a variable value
     */
    public function get(string $name);

    /**
     * Mostrar la plantilla solicitada.
     * La salida se almacena en buffer y se devuelve el contenido
     *
     * @return string Con el contenido del buffer de salida
     * @throws FileNotFoundException
     */
    public function render(): string;

    /**
     * Anexar el valor de la variable al array de la misma en el array de variables
     *
     * @param  string  $name  nombre de la variable
     * @param  mixed  $value  valor de la variable
     * @param  string|null  $scope  string ámbito de la variable
     * @param  int|null  $index  string índice del array
     */
    public function append(string $name, $value, ?string $scope = null, int $index = null): void;

    /**
     * Reset de las plantillas añadidas
     */
    public function resetTemplates(): TemplateInterface;

    /**
     * Reset de las plantillas añadidas
     */
    public function resetContentTemplates(): TemplateInterface;

    public function getBase(): string;

    public function setBase(string $base): void;

    public function getTheme(): ThemeInterface;

    /**
     * Dumps current stored vars
     */
    public function dumpVars();

    public function getContentTemplates(): array;

    public function hasContentTemplates(): bool;

    public function getTemplates(): array;

    /**
     * Assigns the current templates to contentTemplates
     */
    public function upgrade(): TemplateInterface;

    /**
     * Crear la variable y asignarle un valor en el array de variables
     *
     * @param  string  $name  nombre de la variable
     * @param  mixed  $value  valor de la variable
     * @param  string|null  $scope  string ámbito de la variable
     */
    public function assign(string $name, $value = '', ?string $scope = null): void;

    public function isUpgraded(): bool;
}