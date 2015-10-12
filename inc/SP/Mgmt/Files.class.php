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

        $data['accountId'] = $accountId;
        $data['name'] = $fileData['name'];
        $data['type'] = $fileData['type'];
        $data['size'] = $fileData['size'];
        $data['blobcontent'] = $fileData['content'];
        $data['extension'] = $fileData['extension'];
        $data['thumbnail'] = ImageUtil::createThumbnail($fileData['content'], $fileData['type']);

        if (DB::getQuery($query, __FUNCTION__, $data) === true) {
            $log = new Log(_('Subir Archivo'));
            $log->addDescription(_('Cuenta') . ": " . $accountId);
            $log->addDescription(_('Archivo') . ": " . $fileData['name']);
            $log->addDescription(_('Tipo') . ": " . $fileData['type']);
            $log->addDescription(_('Tamaño') . ": " . round($fileData['size'] / 1024, 2) . " KB");
            $log->writeLog();

            Email::sendEmail($log);

            return true;
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

        $data['id'] = $fileId;

        return DB::getResults($query, __FUNCTION__, $data);
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

        $data['id'] = $fileId;

        if (DB::getQuery($query, __FUNCTION__, $data) === true) {
            $log = new Log(_('Eliminar Archivo'));
            $log->addDescription(_('ID') . ": " . $fileId);
            $log->addDescription(_('Archivo') . ": " . $fileInfo->accfile_name);
            $log->addDescription(_('Tipo') . ": " . $fileInfo->accfile_type);
            $log->addDescription(_('Tamaño') . ": " . round($fileInfo->accfile_size / 1024, 2) . " KB");
            $log->writeLog();

            Email::sendEmail($log);

            return true;
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
            . "accfile_type "
            . "FROM accFiles "
            . "WHERE accfile_id = :id LIMIT 1";

        $data['id'] = $fileId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        return $queryRes;
    }

    /**
     * Obtener el listado de archivos de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|array con los archivos de la cuenta.
     */
    public static function getFileList($accountId)
    {
        $query = "SELECT accfile_id,"
            . "accfile_name,"
            . "accfile_size, "
            . "accfile_thumb, "
            . "accfile_type "
            . "FROM accFiles "
            . "WHERE accfile_accountId = :id";

        $data['id'] = $accountId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

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

        $data['id'] = $accountId;

        DB::getQuery($query, __FUNCTION__, $data);

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

        $data['id'] = $accountId;

        return DB::getQuery($query, __FUNCTION__, $data);
    }
}