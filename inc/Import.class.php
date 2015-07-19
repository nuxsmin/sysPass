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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de importar cuentas.
 */
class Import
{
    /**
     * @var string
     */
    static $importPwd = '';
    /**
     * @var int
     */
    static $defUser = 0;
    /**
     * @var int
     */
    static $defGroup = 0;
    /**
     * @var string
     */
    static $csvDelimiter = '';

    /**
     * @param string $importPwd
     */
    public static function setImportPwd($importPwd)
    {
        self::$importPwd = $importPwd;
    }

    /**
     * @param int $defUser
     */
    public static function setDefUser($defUser)
    {
        self::$defUser = $defUser;
    }

    /**
     * @param int $defGroup
     */
    public static function setDefGroup($defGroup)
    {
        self::$defGroup = $defGroup;
    }

    /**
     * @param string $csvDelimiter
     */
    public static function setCsvDelimiter($csvDelimiter)
    {
        self::$csvDelimiter = $csvDelimiter;
    }

    /**
     * Iniciar la importación de cuentas.
     *
     * @param array  $fileData  Los datos del archivo
     * @return array resultado del proceso
     */
    public static function doImport(&$fileData)
    {
        try {
            $file = new FileImport($fileData);

            switch ($file->getFileType()) {
                case 'text/csv':
                case 'application/vnd.ms-excel':
                    $import = new CsvImport($file);
                    $import->setFieldDelimiter(self::$csvDelimiter);
                    break;
                case 'text/xml':
                    $import = new XmlImport($file);
                    $import->setImportPass(self::$importPwd);
                    break;
                default:
                    throw new SPException(
                        SPException::SP_WARNING,
                        _('Tipo mime no soportado'),
                        _('Compruebe el formato del archivo')
                    );
            }

            $import->setUserId(self::$defUser);
            $import->setUserGroupId(self::$defGroup);
            $import->doImport();
        } catch (SPException $e) {
            Log::writeNewLog(_('Importar Cuentas'), $e->getMessage() . ';;' . $e->getHint());

            $result['error'] = array('description' => $e->getMessage(), 'hint' => $e->getHint());
            return $result;
        }

        Log::writeNewLog(_('Importar Cuentas'), _('Importación finalizada'));

        $result['ok'] = array(
            _('Importación finalizada'),
            _('Revise el registro de eventos para más detalles')
        );

        return $result;
    }
}