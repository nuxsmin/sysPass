<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

use Import\XmlFileImport;
use SP\Core\Messages\LogMessage;

defined('APP_ROOT') || die();

/**
 * Clase XmlImport para usarla como envoltorio para llamar a la clase que corresponda
 * según el tipo de archivo XML detectado.
 *
 * @package SP
 */
class XmlImport
{
    /**
     * Iniciar la importación desde XML.
     *
     * @param FileImport $File
     * @param ImportParams $ImportParams
     * @param LogMessage $LogMessage
     * @return ImportBase|false
     */
    public function doImport(FileImport $File, ImportParams $ImportParams, LogMessage $LogMessage)
    {
        $XmlFileImport = new XmlFileImport($File);

        $format = $XmlFileImport->detectXMLFormat();

        switch ($format) {
            case 'syspass':
                $Import = new SyspassImport();
                break;
            case 'keepass':
                $Import = new KeepassImport();
                break;
            case 'keepassx':
                $Import = new KeepassXImport();
                break;
            default:
                return false;
        }

        $Import->setImportParams($ImportParams);
        $Import->setXmlDOM($XmlFileImport->getXmlDOM());
        $Import->setLogMessage($LogMessage);

        $LogMessage->addDescription(sprintf(__('Formato detectado: %s'), strtoupper($format)));

        return $Import;
    }
}