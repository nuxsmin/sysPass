<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Services\Import;

defined('APP_ROOT') || die();

/**
 * Clase XmlImport para usarla como envoltorio para llamar a la clase que corresponda
 * según el tipo de archivo XML detectado.
 *
 * @package SP
 */
class XmlImport implements ImportInterface
{
    /**
     * @var FileImport
     */
    protected $xmlFileImport;
    /**
     * @var ImportParams
     */
    protected $importParams;

    /**
     * XmlImport constructor.
     *
     * @param XmlFileImport $xmlFileImport
     * @param ImportParams  $importParams
     */
    public function __construct(XmlFileImport $xmlFileImport, ImportParams $importParams)
    {
        $this->xmlFileImport = $xmlFileImport;
        $this->importParams = $importParams;
    }

    /**
     * Iniciar la importación desde XML.
     *
     * @throws ImportException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @return ImportInterface
     */
    public function doImport()
    {
        $format = $this->xmlFileImport->detectXMLFormat();

//        $this->LogMessage->addDescription(sprintf(__('Formato detectado: %s'), mb_strtoupper($format)));
        return $this->selectImportType($format)->doImport();
    }

    /**
     * @param $format
     * @return KeepassImport|KeepassXImport|SyspassImport
     * @throws ImportException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function selectImportType($format)
    {
        switch ($format) {
            case 'syspass':
                return new SyspassImport($this->xmlFileImport, $this->importParams);
            case 'keepass':
                return new KeepassImport($this->xmlFileImport, $this->importParams);
            case 'keepassx':
                return new KeepassXImport($this->xmlFileImport, $this->importParams);
        }

        throw new ImportException(__u('Formato no detectado'));
    }

    /**
     * @throws ImportException
     */
    public function getCounter()
    {
        throw new ImportException(__u('Not implemented'));
    }
}