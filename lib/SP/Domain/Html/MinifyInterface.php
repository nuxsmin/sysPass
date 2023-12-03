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

namespace SP\Domain\Html;

/**
 * Interface MinifyInterface
 */
interface MinifyInterface
{
    /**
     * Devolver al navegador archivos CSS y JS comprimidos
     * Método que devuelve un recurso CSS o JS comprimido. Si coincide el ETAG se
     * devuelve el código HTTP/304
     */
    public function getMinified(): void;

    public function addFilesFromString(string $files, bool $minify = true): MinifyInterface;

    /**
     * Añadir un archivo
     *
     * @param string $file
     * @param bool $minify Si es necesario reducir
     * @param string|null $base
     *
     * @return MinifyInterface
     */
    public function addFile(string $file, bool $minify = true, ?string $base = null): MinifyInterface;

    public function addFiles(array $files, bool $minify = true): MinifyInterface;

    /**
     * @param string $base
     * @param bool $insecure Whether the $base path is insecure
     * @return mixed
     */
    public function builder(string $base, bool $insecure = false): MinifyInterface;
}
