<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\Container;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
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
    /**
     * @var FileImport
     */
    protected $xmlFileImport;
    /**
     * @var ImportParams
     */
    protected $importParams;
    /**
     * @var Container
     */
    private $dic;

    /**
     * XmlImport constructor.
     *
     * @param ContainerInterface $dic
     * @param XmlFileImport      $xmlFileImport
     * @param ImportParams       $importParams
     */
    public function __construct(ContainerInterface $dic, XmlFileImport $xmlFileImport, ImportParams $importParams)
    {
        $this->xmlFileImport = $xmlFileImport;
        $this->importParams = $importParams;
        $this->dic = $dic;
    }

    /**
     * Iniciar la importación desde XML.
     *
     * @return ImportInterface
     * @throws ImportException
     * @throws SPException
     */
    public function doImport()
    {
        $format = $this->xmlFileImport->detectXMLFormat();

        return $this->selectImportType($format)->doImport();
    }

    /**
     * @param $format
     *
     * @return KeepassImport|SyspassImport
     * @throws ImportException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function selectImportType($format)
    {
        switch ($format) {
            case 'syspass':
                return new SyspassImport($this->dic, $this->xmlFileImport, $this->importParams);
            case 'keepass':
                return new KeepassImport($this->dic, $this->xmlFileImport, $this->importParams);
        }

        throw new ImportException(__u('Format not detected'));
    }

    /**
     * @throws ImportException
     */
    public function getCounter()
    {
        throw new ImportException(__u('Not implemented'));
    }
}