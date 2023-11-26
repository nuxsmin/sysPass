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

namespace SP\Domain\Export\Ports;

use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Infrastructure\File\FileException;

/**
 * Clase XmlExport para realizar la exportación de las cuentas de sysPass a formato XML
 *
 * @package SP
 */
interface XmlExportServiceInterface
{
    /**
     * Realiza la exportación de las cuentas a XML
     *
     * @param  string  $exportPath
     * @param  string|null  $pass  La clave de exportación
     *
     * @throws ServiceException
     * @throws FileException
     */
    public function doExport(string $exportPath, ?string $pass = null): void;

    /**
     * @throws CheckException
     * @throws FileException
     */
    public function createArchive(): void;

    public function getExportFile(): string;

    public function isEncrypted(): bool;
}
