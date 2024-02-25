<?php
/*
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

namespace SP\Domain\Import\Services;

use SP\Core\Application;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Ports\ImportParams;
use SP\Domain\Import\Ports\ImportService;
use SP\Domain\Import\Ports\XmlFileService;
use SP\Domain\Import\Ports\XmlImportService;

use function SP\__u;

/**
 * Clase XmlImport para usarla como envoltorio para llamar a la clase que corresponda
 * según el tipo de archivo XML detectado.
 *
 * @package SP
 */
final class XmlImport implements XmlImportService
{
    /**
     * XmlImport constructor.
     */
    public function __construct(
        private readonly Application    $application,
        private readonly ImportHelper   $importHelper,
        private readonly XmlFileService $xmlFileService,
        private readonly CryptInterface $crypt
    ) {
    }

    /**
     * Iniciar la importación desde XML.
     *
     * @throws ImportException
     * @throws SPException
     */
    public function doImport(ImportParams $importParams): ImportService
    {
        $format = $this->xmlFileService->detectXMLFormat();

        return $this->factory($format)->doImport($importParams);
    }

    protected function factory(string $format): ImportService
    {
        switch ($format) {
            case 'syspass':
                return new SyspassImport(
                    $this->application,
                    $this->importHelper,
                    $this->crypt,
                    $this->xmlFileService
                );
            case 'keepass':
                return new KeepassImport(
                    $this->application,
                    $this->importHelper,
                    $this->crypt,
                    $this->xmlFileService
                );
        }

        throw ImportException::error(__u('Format not detected'));
    }

    /**
     * @throws ImportException
     */
    public function getCounter(): int
    {
        throw ImportException::error(__u('Not implemented'));
    }
}
