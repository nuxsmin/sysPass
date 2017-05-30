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

use SP\Core\Exceptions\SPException;
use SP\Core\Messages\LogMessage;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;

defined('APP_ROOT') || die();

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
     * @return LogMessage
     * @throws SPException
     */
    public function doImport(&$fileData)
    {
        set_time_limit(0);

        $LogMessage = new LogMessage();
        $LogMessage->setAction(__('Importar Cuentas', false));
        $Log = new Log($LogMessage);

        try {
            $file = new FileImport($fileData);

            switch ($file->getFileType()) {
                case 'text/csv':
                case 'application/vnd.ms-excel':
                    $Import = new CsvImport($file, $this->ImportParams, $LogMessage);
                    break;
                case 'text/xml':
                    $Import = new XmlImport($file, $this->ImportParams, $LogMessage);
                    break;
                default:
                    throw new SPException(
                        SPException::SP_WARNING,
                        sprintf(__('Tipo mime no soportado ("%s")'), $file->getFileType()),
                        __('Compruebe el formato del archivo', false)
                    );
            }

            if (!DB::beginTransaction()) {
                throw new SPException(SPException::SP_ERROR, __('No es posible iniciar una transacción', false));
            }

            $Import->doImport();

            if (!DB::endTransaction()) {
                throw new SPException(SPException::SP_ERROR, __('No es posible finalizar una transacción', false));
            }

            $LogMessage->addDetails(__('Cuentas importadas'), $Import->getCounter());
        } catch (SPException $e) {
            DB::rollbackTransaction();

            $LogMessage->addDescription($e->getMessage());
            $LogMessage->addDetails(__('Ayuda', false), $e->getHint());
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();

            throw $e;
        }

        $Log->writeLog(true);

        Email::sendEmail($LogMessage);

        $LogMessage->addDescription(__('Importación finalizada', false));
        $LogMessage->addDescription(__('Revise el registro de eventos para más detalles', false));

        return $LogMessage;
    }
}