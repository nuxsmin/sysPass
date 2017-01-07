<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Import;

use SP\Core\Exceptions\SPException;
use SP\Log\Log;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase XmlImport para usarla como envoltorio para llamar a la clase que corresponda
 * según el tipo de archivo XML detectado.
 *
 * @package SP
 */
class XmlImport extends XmlImportBase
{
    /**
     * Iniciar la importación desde XML.
     *
     * @throws SPException
     * @return bool
     */
    public function doImport()
    {
        $Import = null;
        $format = $this->detectXMLFormat();

        switch ($format) {
            case 'syspass':
                $Import = new SyspassImport($this->file, $this->ImportParams);
                break;
            case 'keepass':
                $Import = new KeepassImport($this->file, $this->ImportParams);
                break;
            case 'keepassx':
                $Import = new KeepassXImport($this->file, $this->ImportParams);
                break;
        }

        if (is_object($Import)){
            Log::writeNewLog(_('Importar Cuentas'), _('Inicio'));
            Log::writeNewLog(_('Importar Cuentas'), sprintf(_('Formato detectado: %s'), strtoupper($format)));

            $Import->doImport();
        }
    }
}