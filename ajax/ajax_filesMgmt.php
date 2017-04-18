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

use SP\Account\AccountUtil;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\DataModel\FileData;
use SP\Html\Html;
use SP\Http\Request;
use SP\Http\Response;
use SP\Log\Log;
use SP\Mgmt\Files\File;
use SP\Mgmt\Files\FileUtil;
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
    Response::printJson(__('CONSULTA INVÁLIDA'));
}

if (!Checks::fileIsEnabled()) {
    Response::printJson(__('Gestión de archivos deshabilitada'));
}

$actionId = Request::analyze('actionId', 0);
$accountId = Request::analyze('itemId', 0);
$fileId = Request::analyze('fileId', 0);

$Log = new Log();
$LogMessage = $Log->getLogMessage();

if ($actionId === ActionsInterface::ACTION_ACC_FILES_UPLOAD) {
    if ($accountId === 0 || !is_array($_FILES['inFile'])) {
        Response::printJson(__('CONSULTA INVÁLIDA'));
    }

    $LogMessage->setAction(__('Subir Archivo', false));

    $allowedExts = Config::getConfig()->getFilesAllowedExts();
    $allowedSize = Config::getConfig()->getFilesAllowedSize();

    if (count($allowedExts) === 0) {
        $LogMessage->addDescription(__('No hay extensiones permitidas', false));
        $Log->writeLog();

        Response::printJson($LogMessage->getDescription());
    }

    $FileData = new FileData();
    $FileData->setAccfileAccountId($accountId);
    $FileData->setAccfileName(Html::sanitize($_FILES['inFile']['name']));
    $FileData->setAccfileSize($_FILES['inFile']['size']);
    $FileData->setAccfileType($_FILES['inFile']['type']);

    if ($FileData->getAccfileName() !== '') {
        // Comprobamos la extensión del archivo
        $FileData->setAccfileExtension(mb_strtoupper(pathinfo($FileData->getAccfileName(), PATHINFO_EXTENSION)));

        if (!in_array($FileData->getAccfileExtension(), $allowedExts)) {
            $LogMessage->addDescription(__('Tipo de archivo no soportado', false));
            $LogMessage->addDetails(__('Extensión', false), $FileData->getAccfileExtension());
            $Log->writeLog();

            Response::printJson($LogMessage->getDescription());
        }
    } else {
        $LogMessage->addDescription(__('Archivo inválido', false));
        $LogMessage->addDetails(__('Archivo', false), $FileData->getAccfileName());
        $Log->writeLog();

        Response::printJson($LogMessage->getDescription());
    }

    // Variables con información del archivo
    $tmpName = Html::sanitize($_FILES['inFile']['tmp_name']);

    if (!file_exists($tmpName)) {
        // Registramos el máximo tamaño permitido por PHP
        Util::getMaxUpload();

        $LogMessage->addDescription(__('Error interno al leer el archivo', false));
        $Log->writeLog();

        Response::printJson($LogMessage->getDescription());
    }

    if ($FileData->getAccfileSize() > ($allowedSize * 1000)) {
        $LogMessage->addDescription(__('Tamaño de archivo superado', false));
        $LogMessage->addDetails(__('Tamaño', false), $FileData->getRoundSize() . 'KB');
        $Log->writeLog();

        Response::printJson($LogMessage->getDescription());
    }

    // Leemos el archivo a una variable
    $FileData->setAccfileContent(file_get_contents($tmpName));

    if ($FileData->getAccfileContent() === false) {
        $LogMessage->addDescription(__('Error interno al leer el archivo', false));
        $Log->writeLog();

        Response::printJson($LogMessage->getDescription());
    }

    try {
        File::getItem($FileData)->add();

        Response::printJson(__('Archivo guardado'), 0);
    } catch (SPException $e) {
        Response::printJson(__('No se pudo guardar el archivo'));
    }
} elseif ($actionId === ActionsInterface::ACTION_ACC_FILES_DOWNLOAD
    || $actionId === ActionsInterface::ACTION_ACC_FILES_VIEW
    || $actionId === ActionsInterface::ACTION_MGM_FILES_VIEW
) {
    // Verificamos que el ID sea numérico
    if (!is_numeric($fileId) || $fileId === 0) {
        Response::printJson(__('No es un ID de archivo válido'));
    }

    $FileData = File::getItem()->getById($fileId);

    if (!$FileData) {
        Response::printJson(__('El archivo no existe'));
    }

    $LogMessage->setAction(__('Descargar Archivo', false));
    $LogMessage->addDetails(__('ID', false), $fileId);
    $LogMessage->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($FileData->getAccfileAccountId()));
    $LogMessage->addDetails(__('Archivo', false), $FileData->getAccfileName());
    $LogMessage->addDetails(__('Tipo', false), $FileData->getAccfileType());
    $LogMessage->addDetails(__('Tamaño', false), $FileData->getRoundSize() . 'KB');
    $Log->writeLog();

    if ($actionId === ActionsInterface::ACTION_ACC_FILES_DOWNLOAD) {
        // Enviamos el archivo al navegador
        header('Set-Cookie: fileDownload=true; path=/');
        header('Cache-Control: max-age=60, must-revalidate');
        header('Content-length: ' . $FileData->getAccfileSize());
        header('Content-type: ' . $FileData->getAccfileType());
        header('Content-Disposition: attachment; filename="' . $FileData->getAccfileName() . '"');
        header('Content-Description: PHP Generated Data');
        header('Content-transfer-encoding: binary');

        exit($FileData->getAccfileContent());
    } else {
        // FIXME: Usar JSON en respuestas
        if (FileUtil::isImage($FileData)) {
            $imgData = chunk_split(base64_encode($FileData->getAccfileContent()));
            exit('<img src="data:' . $FileData->getAccfileType() . ';base64, ' . $imgData . '" border="0" /><div class="title">' . $FileData->getAccfileName() . '</div>');
//            } elseif ( strtoupper($fileExt) == "PDF" ){
//                echo '<object data="data:application/pdf;base64, '.base64_encode($fileData).'" type="application/pdf"></object>';
        } elseif (mb_strtoupper($FileData->getAccfileExtension()) === 'TXT') {
            exit('<pre>' . htmlentities($FileData->getAccfileContent()) . '</pre>');
        } else {
            exit();
        }
    }
} elseif ($actionId === ActionsInterface::ACTION_ACC_FILES_DELETE) {
    // Verificamos que el ID sea numérico
    if (!is_numeric($fileId) || $fileId === 0) {
        Response::printJson(__('No es un ID de archivo válido'));
    }

    try {
        File::getItem()->delete($fileId);

        Response::printJson(__('Archivo eliminado'), 0);
    } catch (SPException $e) {
        Response::printJson(__('Error al eliminar el archivo'));
    }
} else {
    Response::printJson(__('Acción Inválida'));
}