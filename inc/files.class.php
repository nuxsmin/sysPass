<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar operaciones con archivos de las cuentas de sysPass
 */
class SP_Files
{
    /**
     * @brief Guardar un archivo en la BBDD
     * @param int $accountId
     * @param array $fileData con los datos y el contenido del archivo
     * @return bool
     */
    public static function fileUpload($accountId, $fileData = array())
    {
        $query = "INSERT INTO accFiles "
            . "SET accfile_accountId = " . (int)$accountId . ","
            . "accfile_name = '" . $fileData['name'] . "',"
            . "accfile_type = '" . $fileData['type'] . "',"
            . "accfile_size = '" . $fileData['size'] . "',"
            . "accfile_content = '" . $fileData['content'] . "',"
            . "accfile_extension = '" . DB::escape($fileData['extension']) . "'";

        if (DB::doQuery($query, __FUNCTION__) !== false) {
            $message['action'] = _('Subir Archivo');
            $message['text'][] = _('Cuenta') . ": " . $accountId;
            $message['text'][] = _('Archivo') . ": " . $fileData['name'];
            $message['text'][] = _('Tipo') . ": " . $fileData['type'];
            $message['text'][] = _('Tamaño') . ": " . round($fileData['size'] / 1024, 2) . " KB";

            SP_Log::wrLogInfo($message);
            SP_Common::sendEmail($message);

            return true;
        }

        return false;
    }

    /**
     * @brief Obtener un archivo desde la BBDD
     * @param int $fileId con el Id del archivo
     * @return object con los datos del archivo
     *
     * Función para obtener un archivo y pasarlo al navegador como descarga o imagen en línea
     */
    public static function fileDownload($fileId)
    {
        // Obtenemos el archivo de la BBDD
        $query = "SELECT * FROM accFiles "
            . "WHERE accfile_id = " . (int)$fileId . " LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        if (DB::$num_rows == 0) {
            return false;
        }

        return $queryRes;
    }

    /**
     * @brief Eliminar un archivo de la BBDD
     * @param int $fileId con el Id del archivo
     * @return bool
     */
    public static function fileDelete($fileId)
    {
        $fileInfo = self::getFileInfo($fileId);

        // Eliminamos el archivo de la BBDD
        $query = "DELETE FROM accFiles "
            . "WHERE accfile_id = " . (int)$fileId . " LIMIT 1";
        $queryRes = DB::doQuery($query, __FUNCTION__);

        if ($queryRes !== false) {
            $message['action'] = _('Eliminar Archivo');
            $message['text'][] = _('ID') . ": " . $fileId;
            $message['text'][] = _('Archivo') . ": " . $fileInfo->accfile_name;
            $message['text'][] = _('Tipo') . ": " . $fileInfo->accfile_type;
            $message['text'][] = _('Tamaño') . ": " . round($fileInfo->accfile_size / 1024, 2) . " KB";

            SP_Log::wrLogInfo($message);
            SP_Common::sendEmail($message);

            return true;
        }

        return false;
    }

    /**
     * @brief Obtener información de un archivo almacenado en la BBDD
     * @param int $fileId con el Id del archivo
     * @return object con el resultado de la consulta
     */
    public static function getFileInfo($fileId)
    {
        $query = "SELECT accfile_name,"
            . "accfile_size,"
            . "accfile_type "
            . "FROM accFiles "
            . "WHERE accfile_id = " . (int)$fileId . " LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        if (DB::$num_rows === 0) {
            echo _('El archivo no existe');
            return false;
        }

        return $queryRes;
    }

    /**
     * @brief Obtener el listado de archivos de una cuenta
     * @param int $accountId con el Id de la cuenta
     * @return array con los archivos de la cuenta.
     */
    public static function getFileList($accountId)
    {
        $query = "SELECT accfile_id,"
            . "accfile_name,"
            . "accfile_size "
            . "FROM accFiles "
            . "WHERE accfile_accountId = " . (int)$accountId;
        $queryRes = DB::getResults($query, __FUNCTION__, true);

        if ($queryRes === false) {
            return false;
        }

        $files = array();
        $fileNum = 0;

        foreach ($queryRes as $file) {
            $files[$fileNum]['id'] = $file->accfile_id;
            $files[$fileNum]['name'] = $file->accfile_name;
            $files[$fileNum]['size'] = round($file->accfile_size / 1000, 2);
            $fileNum++;
        }

        return $files;
    }

    /**
     * @brief Obtener el número de archivo de una cuenta
     * @param int $accountId con el Id de la cuenta
     * @return int con el número de archivos
     */
    public static function countFiles($accountId)
    {
        // Obtenemos los archivos de la BBDD para dicha cuenta
        $query = "SELECT accfile_id "
            . "FROM accFiles "
            . "WHERE accfile_accountId = " . (int)$accountId;

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        return count(DB::$last_result);
    }


    /**
     * @brief Elimina los archivos de una cuenta en la BBDD
     * @param int $accountId con el Id de la cuenta
     * @return bool
     */
    public static function deleteAccountFiles($accountId)
    {
        $query = "DELETE FROM accFiles "
            . "WHERE accfile_accountId = " . (int)$accountId;

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        return true;
    }
}