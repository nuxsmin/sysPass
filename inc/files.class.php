<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
class SP_Files {

    /**
     * @brief Obtener el tamaño máximo de subida de PHP
     * @return none
     */ 
    public static function getMaxUpload() {
        $max_upload = (int) (ini_get('upload_max_filesize'));
        $max_post = (int) (ini_get('post_max_size'));
        $memory_limit = (int) (ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);

        $message['action'] = __FUNCTION__;
        $message['text'][] = "Max. PHP upload: " . $upload_mb . "MB";

        SP_Common::wrLogInfo($message);
    }

    /**
     * @brief Guardar un archivo en la BBDD
     * @param int $accountId
     * @param array $fileData con los datos y el contenido del archivo
     * @return bool
     */ 
    public static function fileUpload($accountId, $fileData = array()) {
        $strQuery = "INSERT INTO accFiles SET accfile_accountId = " . (int) $accountId . ", 
                    accfile_name = '" . $fileData['name'] . "',
                    accfile_type = '".$fileData['type']."',
                    accfile_size = '".$fileData['size']."',
                    accfile_content = '".$fileData['content']."',
                    accfile_extension = '" . DB::escape($fileData['extension']) . "'";

        if (DB::doQuery($strQuery, __FUNCTION__) !== FALSE) {
            $message['action'] = _('Subir Archivo');
            $message['text'][] = _('Cuenta') . ": " . $accountId;
            $message['text'][] = _('Archivo') . ": " . $fileData['name'];
            $message['text'][] = _('Tipo') . ": " . $fileData['type'];
            $message['text'][] = _('Tamaño') . ": " . round($fileData['size'] / 1024, 2) . " KB";

            SP_Common::wrLogInfo($message);
            SP_Common::sendEmail($message);

            return TRUE;
        }
        
        return FALSE;
    }

    /**
     * @brief Obtener un archivo desde la BBDD
     * @param int $fileId con el Id del archivo
     * @param bool $view si el es para ver el archivo
     * @return object con los datos del archivo
     * 
     * Función para obtener un archivo y pasarlo al navegador como descarga o imagen en línea
     */ 
    public static function fileDownload($fileId) {
        // Obtenemos el archivo de la BBDD
        $strQuery = "SELECT * FROM accFiles "
                . "WHERE accfile_id = " . (int) $fileId . " LIMIT 1";
        $resQuery = DB::getResults($strQuery, __FUNCTION__);

        if (!$resQuery || !is_array($resQuery)){
            return FALSE;
        }

        if (count(DB::$last_result) == 0) {
            return FALSE;
        }

        return $resQuery[0];
    }

    /**
     * @brief Obtener información de un archivo almacenado en la BBDD
     * @param int $fileId con el Id del archivo
     * @return object con el resultado de la consulta
     */ 
    public static function getFileInfo($fileId) {
        $strQuery = "SELECT accfile_name, accfile_size, accfile_type "
                . "FROM accFiles "
                . "WHERE accfile_id = " . (int) $fileId . " LIMIT 1";
        $resQuery = DB::getResults($strQuery, __FUNCTION__);

        if (!$resQuery || !is_array($resQuery)) {
            return FALSE;
        }

        if (count(DB::$last_result) === 0) {
            echo _('El archivo no existe');
            return FALSE;
        }

        return $resQuery[0];
    }

    /**
     * @brief Eliminar un archivo de la BBDD
     * @param int $fileId con el Id del archivo
     * @return bool
     */ 
    public static function fileDelete($fileId) {
        $fileInfo = self::getFileInfo($fileId);

        // Eliminamos el archivo de la BBDD
        $strQuery = "DELETE FROM accFiles "
                . "WHERE accfile_id = " . (int) $fileId . " LIMIT 1";
        $resQuery = DB::doQuery($strQuery, __FUNCTION__);

        if ($resQuery !== FALSE) {
            $message['action'] = _('Eliminar Archivo');
            $message['text'][] = _('ID') . ": " . $fileId;
            $message['text'][] = _('Archivo') . ": " . $fileInfo->accfile_name;
            $message['text'][] = _('Tipo') . ": " . $fileInfo->accfile_type;
            $message['text'][] = _('Tamaño') . ": " . round($fileInfo->accfile_size / 1024, 2) . " KB";

            SP_Common::wrLogInfo($message);
            SP_Common::sendEmail($message);

            return TRUE;
        } 
        
        return FALSE;
    }

    /**
     * @brief Obtener el listado de archivos de una cuenta
     * @param int $accountId con el Id de la cuenta
     * @param bool $blnDelete para mostrar o no el botón de eliminar
     * @return array con los archivos de la cuenta.
     */ 
    public static function getFileList($accountId) {
        $strQuery = "SELECT accfile_id, accfile_name, accfile_size "
                . "FROM accFiles WHERE accfile_accountId = " . (int) $accountId;
        $resQuery = DB::getResults($strQuery, __FUNCTION__);

        if (!$resQuery || !is_array($resQuery)){
            return FALSE;
        }

        $files = array();
        $fileNum = 0;
        
        foreach ($resQuery as $file) {
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
    public static function countFiles($accountId) {
        // Obtenemos los archivos de la BBDD para dicha cuenta
        $strQuery = "SELECT accfile_id "
                . "FROM accFiles "
                . "WHERE accfile_accountId = " . (int) $accountId;

        if (DB::doQuery($strQuery, __FUNCTION__) === FALSE){
            return FALSE;
        }

        return count(DB::$last_result);
    }
}