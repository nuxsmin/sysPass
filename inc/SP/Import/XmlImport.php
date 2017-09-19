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

namespace SP\Import;

use SP\Core\Messages\LogMessage;

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
    protected $File;
    /**
     * @var ImportParams
     */
    protected $ImportParams;
    /**
     * @var LogMessage
     */
    protected $LogMessage;
    /**
     * @var ImportBase
     */
    protected $Import;

    /**
     * XmlImport constructor.
     *
     * @param FileImport   $File
     * @param ImportParams $ImportParams
     * @param LogMessage   $LogMessage
     */
    public function __construct(FileImport $File, ImportParams $ImportParams, LogMessage $LogMessage)
    {
        $this->File = $File;
        $this->ImportParams = $ImportParams;
        $this->LogMessage = $LogMessage;
    }

    /**
     * Iniciar la importación desde XML.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doImport()
    {
        $XmlFileImport = new XmlFileImport($this->File);

        $format = $XmlFileImport->detectXMLFormat();

        switch ($format) {
            case 'syspass':
                $this->Import = new SyspassImport();
                break;
            case 'keepass':
                $this->Import = new KeepassImport();
                break;
            case 'keepassx':
                $this->Import = new KeepassXImport();
                break;
            default:
                return;
        }

        $this->Import->setImportParams($this->ImportParams);
        $this->Import->setXmlDOM($XmlFileImport->getXmlDOM());
        $this->Import->setLogMessage($this->LogMessage);

        $this->LogMessage->addDescription(sprintf(__('Formato detectado: %s'), mb_strtoupper($format)));

        $this->Import->doImport();
    }

    /**
     * Devolver el contador de objetos importados
     *
     * @return int
     */
    public function getCounter()
    {
        return $this->Import->getCounter();
    }
}