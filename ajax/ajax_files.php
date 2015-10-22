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

use SP\Account\AccountUtil;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\Html\Html;
use SP\Http\Request;
use SP\Http\Response;
use SP\Log\Log;
use SP\Mgmt\Files;
use SP\Util\Checks;
use SP\Util\Util;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Util::logout();
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJSON(_('CONSULTA INVÁLIDA'));
}

if (!Checks::fileIsEnabled()) {
    Response::printJSON(_('Gestión de archivos deshabilitada'));
}

$actionId = Request::analyze('actionId', 0);
$accountId = Request::analyze('accountId', 0);
$fileId = Request::analyze('fileId', 0);

$Log = new Log();

if ($actionId === ActionsInterface::ACTION_ACC_FILES_UPLOAD) {
    if (!is_array($_FILES["inFile"]) || !$accountId === 0) {
        exit();
    }

    $Log->setAction(_('Subir Archivo'));

    $allowedExts = strtoupper(Config::getValue('files_allowed_exts'));
    $allowedSize = Config::getValue('files_allowed_size');

    if ($allowedExts) {
        // Extensiones aceptadas
        $extsOk = explode(",", $allowedExts);
    } else {
        $Log->addDescription(_('No hay extensiones permitidas'));
        $Log->writeLog();

        Response::printJSON($Log->getDescription());
    }

    if (is_array($_FILES) && $_FILES['inFile']['name']) {
        // Comprobamos la extensión del archivo
        $fileData['extension'] = strtoupper(pathinfo($_FILES['inFile']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileData['extension'], $extsOk)) {
            $Log->addDescription(_('Tipo de archivo no soportado'));
            $Log->addDetails(_('Extensión'), $fileData['extension']);
            $Log->writeLog();

            Response::printJSON($Log->getDescription());
        }
    } else {
        $Log->addDescription(_('Archivo inválido'));
        $Log->addDetails(_('Archivo'), $_FILES['inFile']['name']);
        $Log->writeLog();

        Response::printJSON($Log->getDescription());
    }

    // Variables con información del archivo
    $fileData['name'] = Html::sanitize($_FILES['inFile']['name']);
    $tmpName = Html::sanitize($_FILES['inFile']['tmp_name']);
    $fileData['size'] = $_FILES['inFile']['size'];
    $fileData['type'] = $_FILES['inFile']['type'];

    if (!file_exists($tmpName)) {
        // Registramos el máximo tamaño permitido por PHP
        Util::getMaxUpload();

        $Log->addDescription(_('Error interno al leer el archivo'));
        $Log->writeLog();

        Response::printJSON($Log->getDescription());
    }

    if ($fileData['size'] > ($allowedSize * 1000)) {
        $Log->addDescription(_('Tamaño de archivo superado'));
        $Log->addDetails(_('Tamaño'), round(($allowedSize / 1000), 1) . 'MB');
        $Log->writeLog();

        Response::printJSON($Log->getDescription());
    }

    // Leemos el archivo a una variable
    $fileData['content'] = file_get_contents($tmpName);

    if ($fileData['content'] === false) {
        $Log->addDescription(_('Error interno al leer el archivo'));
        $Log->writeLog();

        Response::printJSON($Log->getDescription());
    }

    if (Files::fileUpload($accountId, $fileData)) {
        Response::printJSON(_('Archivo guardado'), 0);
    } else {
        Response::printJSON(_('No se pudo guardar el archivo'));
    }
} elseif ($actionId === ActionsInterface::ACTION_ACC_FILES_DOWNLOAD
    || $actionId === ActionsInterface::ACTION_ACC_FILES_VIEW
    || $actionId === ActionsInterface::ACTION_MGM_FILES_VIEW
) {
    // Verificamos que el ID sea numérico
    if (!is_numeric($fileId) || $fileId === 0) {
        Response::printJSON(_('No es un ID de archivo válido'));
    }

    $file = Files::fileDownload($fileId);

    if (!$file) {
        Response::printJSON(_('El archivo no existe'));
    }

    $fileSize = $file->accfile_size;
    $fileType = $file->accfile_type;
    $fileName = $file->accfile_name;
    $fileExt = $file->accfile_extension;
    $fileData = $file->accfile_content;

    $Log->setAction(_('Descargar Archivo'));
    $Log->addDetails(_('ID'), $fileId);
    $Log->addDetails(_('Cuenta'), AccountUtil::getAccountNameById($file->accfile_accountId));
    $Log->addDetails(_('Archivo'), $fileName);
    $Log->addDetails(_('Tipo'), $fileType);
    $Log->addDetails(_('Tamaño'), round($fileSize / 1024, 2) . " KB");
    $Log->writeLog();

    if ($actionId === ActionsInterface::ACTION_ACC_FILES_DOWNLOAD) {

        // Enviamos el archivo al navegador
        header('Set-Cookie: fileDownload=true; path=/');
        header('Cache-Control: max-age=60, must-revalidate');
        header("Content-length: $fileSize");
        header("Content-type: $fileType");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Content-Description: PHP Generated Data");
        header("Content-transfer-encoding: binary");

        exit($fileData);
    } else {
        $extsOkImg = array("JPG", "GIF", "PNG");

        if (in_array(strtoupper($fileExt), $extsOkImg)) {
            $imgData = chunk_split(base64_encode($fileData));
            exit('<img src="data:' . $fileType . ';base64, ' . $imgData . '" border="0" />');
//            } elseif ( strtoupper($fileExt) == "PDF" ){
//                echo '<object data="data:application/pdf;base64, '.base64_encode($fileData).'" type="application/pdf"></object>';
        } elseif (strtoupper($fileExt) == "TXT") {
            exit('<div id="fancyView" class="backGrey"><pre>' . htmlentities($fileData) . '</pre></div>');
        } else {
            exit();
        }
    }
} elseif ($actionId === ActionsInterface::ACTION_ACC_FILES_DELETE) {
    // Verificamos que el ID sea numérico
    if (!is_numeric($fileId) || $fileId === 0) {
        Response::printJSON(_('No es un ID de archivo válido'));
    } elseif (Files::fileDelete($fileId)) {
        Response::printJSON(_('Archivo eliminado'), 0);
    }

    Response::printJSON(_('Error al eliminar el archivo'));
} else {
    Response::printJSON(_('Acción Inválida'));
}