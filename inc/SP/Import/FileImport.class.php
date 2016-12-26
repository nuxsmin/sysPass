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

namespace SP\Import;

use SP\Core\Exceptions\SPException;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase FileImport encargada el leer archivos para su importación
 *
 * @package SP
 */
class FileImport
{
    /**
     * Contenido del archivo leído
     *
     * @var string|array
     */
    protected $fileContent = null;

    /**
     * Archivo temporal utilizado en la subida HTML
     *
     * @var string
     */
    protected $tmpFile = '';

    /**
     * Tipo Mime del archivo
     *
     * @var string
     */
    protected $fileType = '';

    /**
     * @return array
     */
    public function getFileContent()
    {
        return $this->fileContent;
    }

    /**
     * @return string
     */
    public function getTmpFile()
    {
        return $this->tmpFile;
    }

    /**
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }


    /**
     * FileImport constructor.
     */
    public function __construct(&$fileData)
    {
        try {
            $this->checkFile($fileData);
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * Leer los datos del archivo.
     *
     * @param array $fileData con los datos del archivo
     * @throws SPException
     * @return bool
     */
    private function checkFile(&$fileData)
    {
        if (!is_array($fileData)) {
            throw new SPException(SPException::SP_CRITICAL, _('Archivo no subido correctamente'), _('Verifique los permisos del usuario del servidor web'));
        }

        if ($fileData['name']) {
            // Comprobamos la extensión del archivo
            $fileExtension = strtoupper(pathinfo($fileData['name'], PATHINFO_EXTENSION));

            if ($fileExtension != 'CSV' && $fileExtension != 'XML') {
                throw new SPException(
                    SPException::SP_CRITICAL,
                    _('Tipo de archivo no soportado'),
                    _('Compruebe la extensión del archivo')
                );
            }
        }

        // Variables con información del archivo
        $this->tmpFile = $fileData['tmp_name'];
        $this->fileType = $fileData['type'];

        if (!file_exists($this->tmpFile) || !is_readable($this->tmpFile)) {
            // Registramos el máximo tamaño permitido por PHP
            Util::getMaxUpload();

            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno al leer el archivo'),
                _('Compruebe la configuración de PHP para subir archivos')
            );
        }
    }

    /**
     * Leer los datos de un archivo subido a un array
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function readFileToArray()
    {
        $this->fileContent = file($this->tmpFile, FILE_SKIP_EMPTY_LINES);

        if ($this->fileContent === false){
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno al leer el archivo'),
                _('Compruebe los permisos del directorio temporal')
            );
        }
    }

    /**
     * Leer los datos de un archivo subido a una cadena
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function readFileToString()
    {
        $this->fileContent = file_get_contents($this->tmpFile);

        if ($this->fileContent === false){
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno al leer el archivo'),
                _('Compruebe los permisos del directorio temporal')
            );
        }
    }
}