<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Import;

use Psr\Container\ContainerInterface;
use SP\Core\Exceptions\SPException;

defined('APP_ROOT') || die();

/**
 * Clase XmlImport para usarla como envoltorio para llamar a la clase que corresponda
 * según el tipo de archivo XML detectado.
 *
 * @package SP
 */
final class XmlImport implements ImportInterface
{
    protected XmlFileImport $xmlFileImport;
    protected ImportParams $importParams;
    private ContainerInterface $dic;

    /**
     * XmlImport constructor.
     *
     * @param ContainerInterface $dic
     * @param XmlFileImport      $xmlFileImport
     * @param ImportParams       $importParams
     */
    public function __construct(
        ContainerInterface $dic,
        XmlFileImport      $xmlFileImport,
        ImportParams       $importParams
    )
    {
        $this->xmlFileImport = $xmlFileImport;
        $this->importParams = $importParams;
        $this->dic = $dic;
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
     * @param string $format
     *
     * @return KeepassImport|SyspassImport
     * @throws \SP\Services\Import\ImportException
     */
    protected function selectImportType(string $format)
    {
        switch ($format) {
            case 'syspass':
                return new SyspassImport(
                    $this->dic,
                    $this->xmlFileImport,
                    $this->importParams
                );
            case 'keepass':
                return new KeepassImport(
                    $this->dic,
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