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

use SP\Request;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Util::logout();
}

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    die(_('CONSULTA INVÁLIDA'));
}

if (!SP\Util::fileIsEnabled()) {
    exit(_('Gestión de archivos deshabilitada'));
}

$action = SP\Request::analyze('action');
$accountId = SP\Request::analyze('accountId', 0);
$fileId = SP\Request::analyze('fileId', 0);

$log = new \SP\Log();

if ($action == 'upload') {
    if (!is_array($_FILES["inFile"]) || !$accountId === 0) {
        exit();
    }

    $log->setAction(_('Subir Archivo'));

    $allowedExts = strtoupper(SP\Config::getValue('files_allowed_exts'));
    $allowedSize = SP\Config::getValue('files_allowed_size');

    if ($allowedExts) {
        // Extensiones aceptadas
        $extsOk = explode(",", $allowedExts);
    } else {
        $log->addDescription(_('No hay extensiones permitidas'));
        $log->writeLog();

        exit($log->getDescription());
    }

    if (is_array($_FILES) && $_FILES['inFile']['name']) {
        // Comprobamos la extensión del archivo
        $fileData['extension'] = strtoupper(pathinfo($_FILES['inFile']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileData['extension'], $extsOk)) {
            $log->addDescription(_('Tipo de archivo no soportado') . " '" . $fileData['extension'] . "' ");
            $log->writeLog();

            exit($log->getDescription());
        }
    } else {
        $log->addDescription(_('Archivo inválido') . ":<br>" . $_FILES['inFile']['name']);
        $log->writeLog();

        exit($log->getDescription());
    }

    // Variables con información del archivo
    $fileData['name'] = SP\Html::sanitize($_FILES['inFile']['name']);
    $tmpName = SP\Html::sanitize($_FILES['inFile']['tmp_name']);
    $fileData['size'] = $_FILES['inFile']['size'];
    $fileData['type'] = $_FILES['inFile']['type'];

    if (!file_exists($tmpName)) {
        // Registramos el máximo tamaño permitido por PHP
        SP\Util::getMaxUpload();

        $log->addDescription(_('Error interno al leer el archivo'));
        $log->writeLog();

        exit($log->getDescription());
    }

    if ($fileData['size'] > ($allowedSize * 1000)) {
        $log->addDescription(_('El archivo es mayor de ') . " " . round(($allowedSize / 1000), 1) . "MB");
        $log->writeLog();

        exit($log->getDescription());
    }

    // Leemos el archivo a una variable
    $fileData['content'] = file_get_contents($tmpName);

    if ($fileData['content'] === false) {
        $log->addDescription(_('Error interno al leer el archivo'));
        $log->writeLog();

        exit($log->getDescription());
    }

    if (SP\Files::fileUpload($accountId, $fileData)) {
        $log->addDescription(_('Archivo guardado'));
        $log->writeLog();

        exit($log->getDescription());
    } else {
        $log->addDescription(_('No se pudo guardar el archivo'));
        $log->writeLog();

        exit($log->getDescription());
    }
}

if ($action == 'download' || $action == 'view') {
    // Verificamos que el ID sea numérico
    if (!is_numeric($fileId) || $fileId === 0) {
        exit(_('No es un ID de archivo válido'));
    }

    $isView = ($action == 'view') ? true : false;

    $file = SP\Files::fileDownload($fileId);

    if (!$file) {
        exit(_('El archivo no existe'));
    }

    $fileSize = $file->accfile_size;
    $fileType = $file->accfile_type;
    $fileName = $file->accfile_name;
    $fileExt = $file->accfile_extension;
    $fileData = $file->accfile_content;

    $log->setAction(_('Descargar Archivo'));
    $log->addDescription(_('ID') . ": " . $fileId);
    $log->addDescription(_('Archivo') . ": " . $fileName);
    $log->addDescription(_('Tipo') . ": " . $fileType);
    $log->addDescription(_('Tamaño') . ": " . round($fileSize / 1024, 2) . " KB");

    if (!$isView) {
        $log->writeLog();

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
            $log->writeLog();

            $imgData = chunk_split(base64_encode($fileData));
            exit('<img src="data:' . $fileType . ';base64, ' . $imgData . '" border="0" />');
//            } elseif ( strtoupper($fileExt) == "PDF" ){
//                echo '<object data="data:application/pdf;base64, '.base64_encode($fileData).'" type="application/pdf"></object>';
        } elseif (strtoupper($fileExt) == "TXT") {
            $log->writeLog();

            exit('<div id="fancyView" class="backGrey"><pre>' . htmlentities($fileData) . '</pre></div>');
        } else {
            exit();
        }
    }
}

if ($action == "delete") {
    // Verificamos que el ID sea numérico
    if (!is_numeric($fileId) || $fileId === 0) {
        exit(_('No es un ID de archivo válido'));
    }

    if (SP\Files::fileDelete($fileId)) {
        $log->addDescription(_('Archivo eliminado'));
        $log->writeLog();

        exit($log->getDescription());
    } else {
        $log->addDescription(_('Error al eliminar el archivo'));
        $log->writeLog();

        exit($log->getDescription());
    }
}