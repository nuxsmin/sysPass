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

namespace SP\Mgmt;

use SP\Account\AccountUtil;
use SP\Storage\QueryData;
use SP\Util\ImageUtil;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar operaciones con archivos de las cuentas de sysPass
 */
class Files
{
    /**
     * Guardar un archivo en la BBDD.
     *
     * @param int   $accountId
     * @param array $fileData con los datos y el contenido del archivo
     * @return bool
     */
    public static function fileUpload($accountId, &$fileData = array())
    {
        $query = "INSERT INTO accFiles "
            . "SET accfile_accountId = :accountId,"
            . "accfile_name = :name,"
            . "accfile_type = :type,"
            . "accfile_size = :size,"
            . "accfile_content = :blobcontent,"
            . "accfile_extension = :extension,"
            . "accfile_thumb = :thumbnail";

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'accountId');
        $Data->addParam($fileData['name'], 'name');
        $Data->addParam($fileData['type'], 'type');
        $Data->addParam($fileData['size'], 'size');
        $Data->addParam($fileData['content'], 'blobcontent');
        $Data->addParam($fileData['extension'], 'extension');
        $Data->addParam(ImageUtil::createThumbnail($fileData['content']), 'thumbnail');

        $Log = new Log(_('Subir Archivo'));
        $Log->addDetails(_('Cuenta'), AccountUtil::getAccountNameById($accountId));
        $Log->addDetails(_('Archivo'), $fileData['name']);
        $Log->addDetails(_('Tipo'), $fileData['type']);
        $Log->addDetails(_('Tamaño'), round($fileData['size'] / 1024, 2) . " KB");

        if (DB::getQuery($Data) === true) {
            $Log->addDescription(_('Archivo subido'));
            $Log->writeLog();

            Email::sendEmail($Log);

            return true;
        } else {
            $Log->addDescription(_('No se pudo guardar el archivo'));
            $Log->writeLog();

            Email::sendEmail($Log);
        }

        return false;
    }

    /**
     * Obtener un archivo desde la BBDD.
     * Función para obtener un archivo y pasarlo al navegador como descarga o imagen en línea
     *
     * @param int $fileId con el Id del archivo
     * @return false|object con los datos del archivo
     */
    public static function fileDownload($fileId)
    {
        // Obtenemos el archivo de la BBDD
        $query = 'SELECT * FROM accFiles WHERE accfile_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($fileId, 'id');

        return DB::getResults($Data);
    }

    /**
     * Eliminar un archivo de la BBDD.
     *
     * @param int $fileId con el Id del archivo
     * @return bool
     */
    public static function fileDelete($fileId)
    {
        $fileInfo = self::getFileInfo($fileId);

        // Eliminamos el archivo de la BBDD
        $query = 'DELETE FROM accFiles WHERE accfile_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($fileId, 'id');

        $Log = new Log(_('Eliminar Archivo'));
        $Log->addDetails(_('ID'), $fileId);
        $Log->addDetails(_('Cuenta'), AccountUtil::getAccountNameById($fileInfo->accfile_accountId));
        $Log->addDetails(_('Archivo'), $fileInfo->accfile_name);
        $Log->addDetails(_('Tipo'), $fileInfo->accfile_type);
        $Log->addDetails(_('Tamaño'), round($fileInfo->accfile_size / 1024, 2) . " KB");

        if (DB::getQuery($Data) === true) {
            $Log->addDescription(_('Archivo eliminado'));
            $Log->writeLog();

            Email::sendEmail($Log);

            return true;
        } else {
            $Log->addDescription(_('Error al eliminar el archivo'));
            $Log->writeLog();

            Email::sendEmail($Log);
        }

        return false;
    }

    /**
     * Obtener información de un archivo almacenado en la BBDD.
     *
     * @param int $fileId con el Id del archivo
     * @return false|object con el resultado de la consulta
     */
    public static function getFileInfo($fileId)
    {
        $query = "SELECT accfile_name,"
            . "accfile_size,"
            . "accfile_type, "
            . "accfile_accountId "
            . "FROM accFiles "
            . "WHERE accfile_id = :id LIMIT 1";

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($fileId, 'id');

        $queryRes = DB::getResults($Data);

        return $queryRes;
    }

    /**
     * Obtener el listado de archivos de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|array con los archivos de la cuenta.
     */
    public static function getAccountFileList($accountId)
    {
        $query = "SELECT accfile_id,"
            . "accfile_name,"
            . "accfile_size, "
            . "accfile_thumb, "
            . "accfile_type "
            . "FROM accFiles "
            . "WHERE accfile_accountId = :id";

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        DB::setReturnArray();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        $files = array();

        foreach ($queryRes as $file) {
            $files[] = array(
                'id' => $file->accfile_id,
                'name' => $file->accfile_name,
                'size' => round($file->accfile_size / 1000, 2),
                'thumb' => $file->accfile_thumb,
                'type' => $file->accfile_type
            );
        }

        return $files;
    }

    /**
     * Obtener el número de archivo de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return int con el número de archivos
     */
    public static function countFiles($accountId)
    {
        // Obtenemos los archivos de la BBDD para dicha cuenta
        $query = 'SELECT accfile_id FROM accFiles WHERE accfile_accountId = :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        DB::getQuery($Data);

        return DB::$lastNumRows;
    }


    /**
     * Elimina los archivos de una cuenta en la BBDD.
     *
     * @param int $accountId con el Id de la cuenta
     * @return bool
     */
    public static function deleteAccountFiles($accountId)
    {
        $query = 'DELETE FROM accFiles WHERE accfile_accountId = :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        return DB::getQuery($Data);
    }

    /**
     * Obtener el listado de archivos
     *
     * @return false|array Con los archivos de las cuentas.
     */
    public static function getFileList()
    {
        $query = 'SELECT accfile_id,'
            . 'accfile_name,'
            . 'CONCAT(ROUND(accfile_size/1000, 2), " KB") AS accfile_size,'
            . 'accfile_thumb,'
            . 'accfile_type,'
            . 'account_name,'
            . 'customer_name '
            . 'FROM accFiles '
            . 'JOIN accounts ON account_id = accfile_accountId '
            . 'JOIN customers ON customer_id = account_customerId';

        DB::setReturnArray();

        $Data = new QueryData();
        $Data->setQuery($query);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        return $queryRes;
    }

    /**
     * Obtener el listado de archivos de una búsqueda
     *
     * @param int $limitCount
     * @param int $limitStart
     * @param string $search La cadena de búsqueda
     * @return array|false Con los archivos de las cuentas.
     */
    public static function getFilesMgmtSearch($limitCount, $limitStart = 0, $search = '')
    {
        $query = 'SELECT accfile_id,'
            . 'accfile_name,'
            . 'CONCAT(ROUND(accfile_size/1000, 2), " KB") AS accfile_size,'
            . 'accfile_thumb,'
            . 'accfile_type,'
            . 'account_name,'
            . 'customer_name '
            . 'FROM accFiles '
            . 'JOIN accounts ON account_id = accfile_accountId '
            . 'JOIN customers ON customer_id = account_customerId';

        $Data = new QueryData();

        if (!empty($search)) {
            $search = '%' . $search . '%';

            $query .= ' WHERE accfile_name LIKE ? '
                . 'OR accfile_type LIKE ? '
                . 'OR account_name LIKE ? '
                . 'OR customer_name LIKE ?';

            $Data->addParam($search);
            $Data->addParam($search);
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $query .= ' ORDER BY accfile_name';
        $query .= ' LIMIT ?,?';

        $Data->addParam($limitStart);
        $Data->addParam($limitCount);

        $Data->setQuery($query);

        DB::setReturnArray();
        DB::setFullRowCount();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        $queryRes['count'] = DB::$lastNumRows;

        return $queryRes;
    }
}