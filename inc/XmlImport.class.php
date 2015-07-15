<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://${PROJECT_LINK}
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@${PROJECT_LINK}
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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
        $format = $this->detectXMLFormat();

        switch ($format) {
            case 'syspass':
                $import = new SyspassImport($this->_file);
                break;
            case 'keepass':
                $import = new KeepassImport($this->_file);
                break;
            case 'keepassx':
                $import = new KeepassXImport($this->_file);
                break;
        }

        if (is_object($import)){
            $import->doImport();
        }
    }
}