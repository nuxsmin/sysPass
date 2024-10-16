<?php

declare(strict_types=1);
/**
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

/**
 * Interface TemplateInterface
 */
interface TemplateInterface
{
    /**
     * Add a new content template
     *
     * @param string $name Template file name
     * @param string|null $base Template base directory
     */
    public function addContentTemplate(string $name, ?string $base = null): void;

    /**
     * Removes a template from the stack
     */
    public function remove(string $name): void;

    /**
     * Add a new template
     *
     * @param string $name Template file name
     * @param string|null $base Template base directory
     */
    public function addTemplate(string $name, ?string $base = null): void;

    /**
     * Add a new partial template
     *
     * @param string $name Template file name
     */
    public function includePartial(string $name): string;

    /**
     * Include a new template without adding to the templates list
     *
     * @param string $name Template file name
     * @param string|null $base Template base directory
     */
    public function includeTemplate(string $name, ?string $base = null): string;

    /**
     * Render the current templates. The output is buffered and then the content is returned
     *
     * @return string the templates content
     */
    public function render(): string;

    /**
     * Append the value to an existing array variable
     *
     * @param string $name
     * @param mixed $value
     */
    public function append(string $name, mixed $value): void;

    /**
     * Reset all the added templates
     */
    public function reset(): void;

    public function getBase(): string;


    public function getContentTemplates(): array;

    /**
     * Assigns the current templates to contentTemplates
     */
    public function upgrade(): void;

    /**
     * Create a template var.
     *
     * @param string $name variable name
     * @param mixed $value variable value
     */
    public function assign(string $name, mixed $value): void;

    /**
     * @param string $name
     * @return void
     */
    public function setLayout(string $name): void;

    /**
     * Add a new partial template
     *
     * @param string $name Template file name
     */
    public function addPartial(string $name): void;

    /**
     * Create a template var with a scope. It will preffix the variable name with the scope set.
     *
     * @param string $name variable name
     * @param mixed $value variable value
     * @param string $scope variable scope
     */
    public function assignWithScope(string $name, mixed $value, string $scope): void;
}
