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

use CssMin;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class Minify para la gestión de archivos JS y CSS
 *
 * @package SP
 */
class Minify
{
    /**
     * Constantes para tipos de archivos
     */
    const FILETYPE_JS = 1;
    const FILETYPE_CSS = 2;

    /**
     * Array con los archivos a procesar
     *
     * @var array
     */
    private $_files = array();
    /**
     * Tipos de archivos a procesar
     *
     * @var int
     */
    private $_type = 0;
    /**
     * Base relativa de búsqueda de los archivos
     *
     * @var string
     */
    private $_base = '';

    /**
     * @param string $base
     * @param bool   $checkPath
     */
    public function setBase($base, $checkPath = false)
    {
        $this->_base = $checkPath === true ? Request::getSecureAppPath($base) : $base;
    }


    /**
     * Devolver al navegador archivos CSS y JS comprimidos
     * Método que devuelve un recurso CSS o JS comprimido. Si coincide el ETAG se
     * devuelve el código HTTP/304
     *
     * @param bool   $disableMinify Deshabilitar minimizar
     */
    public function getMinified($disableMinify = false)
    {
        $offset = 3600 * 24 * 30;
        $nextCheck = time() + $offset;
        $expire = 'Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $nextCheck);
        $etag = $this->getEtag();
        $etagMatch = Request::getRequestHeaders('If-None-Match');
        $cacheControl = Request::getRequestHeaders('Cache-Control');
        $pragma = Request::getRequestHeaders('Pragma');

        header('Etag: ' . $etag);
        header("Cache-Control: public, max-age={$offset}, must-revalidate");
        header("Pragma: public; maxage={$offset}");
        header($expire);

        // Devolver código 304 si la versión es la misma y no se solicita refrescar
        if ($etag == $etagMatch && !($cacheControl == 'no-cache' || $pragma == 'no-cache')) {
            header($_SERVER["SERVER_PROTOCOL"] . " 304 Not Modified");
            exit;
        }

        if ($this->_type === self::FILETYPE_JS) {
            header("Content-type: application/x-javascript; charset: UTF-8");
        } elseif ($this->_type === self::FILETYPE_CSS) {
            header("Content-type: text/css; charset: UTF-8");
        }

        flush();

        if ($this->checkZlib() || !ob_start('ob_gzhandler')) {
            ob_start();
        }

        foreach ($this->_files as $file) {
            $filePath = $file['base'] . DIRECTORY_SEPARATOR . $file['name'];

            // Obtener el recurso desde una URL
            if (preg_match('#^https?://.*#', $file['name'])) {
                $data = Util::getDataFromUrl($file['name']);

                if ($data !== false) {
                    echo '/* URL: ' . $file['name'] . ' */' . PHP_EOL;
                    echo $data;
                }

                continue;
            }

            if (!file_exists($filePath)) {
                echo '/* ERROR: FILE NOT FOUND: ' . $file['name'] . ' */' . PHP_EOL;
                error_log('File not found: ' . $filePath);
                continue;
            }

            if ($file['min'] === true && $disableMinify === false) {
                echo '/* MINIFIED FILE: ' . $file['name'] . ' */' . PHP_EOL;
                if ($this->_type === self::FILETYPE_JS) {
                    echo $this->jsCompress(file_get_contents($filePath));
                } elseif ($this->_type === self::FILETYPE_CSS) {
                    echo CssMin::minify(file_get_contents($filePath));
                }
            } else {
                echo '/* FILE: ' . $file['name'] . ' */' . PHP_EOL;
                echo file_get_contents($filePath);
            }

            echo PHP_EOL;
        }

        ob_end_flush();
    }

    /**
     * Calcular el hash MD5 de varios archivos.
     *
     * @return string Con el hash
     */
    private function getEtag()
    {
        $md5Sum = '';

        foreach ($this->_files as $file) {
            if (preg_match('#^https?://#', $file['name'])) {
                continue;
            }

            $filePath = $file['base'] . DIRECTORY_SEPARATOR . $file['name'];
            $md5Sum .= md5_file($filePath);
        }

        return md5($md5Sum);
    }

    /**
     * Comprobar si la salida comprimida en con zlib está activada.
     * No es compatible con ob_gzhandler()
     *
     * @return bool
     */
    private function checkZlib()
    {
        return Util::boolval(ini_get('zlib.output_compression'));
    }

    /**
     * Comprimir código javascript.
     *
     * @param string $buffer código a comprimir
     * @return string
     */
    private function jsCompress($buffer)
    {
        $regexReplace = array(
            '#/\*[^*]*\*+([^/][^*]*\*+)*/#',
            '#^[\s\t]*//.*$#m',
            '#[\s\t]+$#m',
            '#^[\s\t]+#m',
            '#\s*//\s.*$#m'
        );
        $buffer = preg_replace($regexReplace, '', $buffer);
        // remove tabs, spaces, newlines, etc.
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t"), '', $buffer);
        return $buffer;
    }

    /**
     * @param string $file
     * @param bool   $minify Si es necesario reducir
     */
    public function addFile($file, $minify = false)
    {
        if (strrpos($file, ',')) {
            $files = explode(',', $file);

            foreach ($files as $file){
                $this->_files[] = array(
                    'base' => $this->_base,
                    'name' => Request::getSecureAppFile($file, $this->_base),
                    'min' => $this->needsMinify($file)
                );
            }
        } else {
            $this->_files[] = array(
                'base' => $this->_base,
                'name' => Request::getSecureAppFile($file, $this->_base),
                'min' => $this->needsMinify($file)
            );
        }
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->_type = $type;
    }

    /**
     * Comprobar si es necesario reducir
     *
     * @param string $file El nombre del archivo
     * @return bool
     */
    private function needsMinify($file)
    {
        return !preg_match('/\.(min|pack)\./', $file);
    }
}