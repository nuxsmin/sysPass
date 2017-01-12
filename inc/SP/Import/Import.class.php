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

use SP\Core\Exceptions\SPException;
use SP\Http\Message;
use SP\Log\Email;
use SP\Log\Log;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de importar cuentas.
 */
class Import
{
    /**
     * @var ImportParams Parámetros de importación
     */
    protected $ImportParams;

    /**
     * Import constructor.
     *
     * @param ImportParams $ImportParams
     */
    public function __construct(ImportParams $ImportParams)
    {
        $this->ImportParams = $ImportParams;
    }

    /**
     * Iniciar la importación de cuentas.
     *
     * @param array $fileData Los datos del archivo
     * @return Message
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doImport(&$fileData)
    {
        $Log = new Log(_('Importar Cuentas'));

        try {
            $file = new FileImport($fileData);

            switch ($file->getFileType()) {
                case 'text/csv':
                case 'application/vnd.ms-excel':
                    $Import = new CsvImport($file, $this->ImportParams);
                    break;
                case 'text/xml':
                    $Import = new XmlImport($file, $this->ImportParams);
                    break;
                default:
                    throw new SPException(
                        SPException::SP_WARNING,
                        sprintf(_('Tipo mime no soportado ("%s")'), $file->getFileType()),
                        _('Compruebe el formato del archivo')
                    );
            }

            $Import->doImport();
        } catch (SPException $e) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription($e->getMessage());
            $Log->addDetails(_('Ayuda'), $e->getHint());
            $Log->writeLog();

            throw $e;
        }

        $Log->addDescription(_('Importación finalizada'));
        $Log->writeLog();

        Email::sendEmail($Log);

        $Message = new Message();
        $Message->setDescription(_('Importación finalizada'));
        $Message->setHint(_('Revise el registro de eventos para más detalles'));

        return $Message;
    }
}