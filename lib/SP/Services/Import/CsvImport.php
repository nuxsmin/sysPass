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

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Storage\File\FileException;

defined('APP_ROOT') || die();

/**
 * Class CsvImport para importar cuentas desde archivos CSV
 *
 * @package SP
 */
final class CsvImport extends CsvImportBase implements ImportInterface
{
    /**
     * Iniciar la importación desde CSV
     *
     * @return $this|ImportInterface
     * @throws ImportException
     * @throws FileException
     */
    public function doImport()
    {
        $this->eventDispatcher->notifyEvent('run.import.csv',
            new Event($this, EventMessage::factory()
                ->addDescription(sprintf(__('Detected format: %s'), 'CSV')))
        );

        $this->processAccounts();

        return $this;
    }
}