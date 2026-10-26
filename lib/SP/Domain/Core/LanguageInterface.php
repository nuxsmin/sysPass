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

namespace SP\Domain\Core;


/**
 * Class Language para el manejo del lenguaje utilizado por la aplicación
 *
 * @package SP
 */
interface LanguageInterface
{
    /**
     * Devolver los lenguajes disponibles
     */
    public static function getAvailableLanguages(): array;

    /**
     * Establecer el lenguaje a utilizar
     *
     * @param bool $force Forzar la detección del lenguaje para los inicios de sesión
     */
    public function setLanguage(bool $force = false): void;

    /**
     * Establecer las locales de gettext
     */
    public function setLocales(string $lang): void;

    /**
     * Establecer el lenguaje global para las traducciones
     */
    public function setAppLocales(): void;

    /**
     * Restablecer el lenguaje global para las traducciones
     */
    public function unsetAppLocales(): void;
}
