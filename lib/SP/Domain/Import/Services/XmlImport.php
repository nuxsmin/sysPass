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

namespace SP\Domain\Import\Services;

use SP\Core\Application;
use SP\Core\Exceptions\SPException;
use SP\Domain\Config\Ports\ConfigServiceInterface;

defined('APP_ROOT') || die();

/**
 * Clase XmlImport para usarla como envoltorio para llamar a la clase que corresponda
 * según el tipo de archivo XML detectado.
 *
 * @package SP
 */
final class XmlImport implements XmlImportInterface
{
    private XmlFileImport          $xmlFileImport;
    private ImportParams           $importParams;
    private ImportHelper           $importHelper;
    private ConfigServiceInterface $configService;
    private Application            $application;

    /**
     * XmlImport constructor.
     */
    public function __construct(
        Application $application,
        ImportHelper $importHelper,
        ConfigServiceInterface $configService,
        XmlFileImportInterface $xmlFileImport,
        ImportParams $importParams
    ) {
        $this->application = $application;
        $this->importHelper = $importHelper;
        $this->configService = $configService;
        $this->xmlFileImport = $xmlFileImport;
        $this->importParams = $importParams;
    }

    /**
     * Iniciar la importación desde XML.
     *
     * @throws ImportException
     * @throws SPException
     */
    public function doImport(): ImportInterface
    {
        $format = $this->xmlFileImport->detectXMLFormat();

        return $this->selectImportType($format)->doImport();
    }

    /**
     * @param  string  $format
     *
     * @return KeepassImport|SyspassImport
     * @throws \SP\Domain\Import\Services\ImportException
     */
    protected function selectImportType(string $format)
    {
        switch ($format) {
            case 'syspass':
                return new SyspassImport(
                    $this->application,
                    $this->importHelper,
                    $this->configService,
                    $this->xmlFileImport,
                    $this->importParams
                );
            case 'keepass':
                return new KeepassImport(
                    $this->application,
                    $this->importHelper,
                    $this->configService,
                    $this->xmlFileImport,
                    $this->importParams
                );
        }

        throw new ImportException(__u('Format not detected'));
    }

    /**
     * @throws ImportException
     */
    public function getCounter(): int
    {
        throw new ImportException(__u('Not implemented'));
    }
}
