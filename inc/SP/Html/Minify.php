<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Html;

use CssMin;
use SP\Core\Exceptions\SPException;
use SP\Http\Request;
use SP\Util\Util;

defined('APP_ROOT') || die();

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
    private $files = array();
    /**
     * Tipos de archivos a procesar
     *
     * @var int
     */
    private $type = 0;
    /**
     * Base relativa de búsqueda de los archivos
     *
     * @var string
     */
    private $base = '';

    /**
     * @param string $path
     * @param bool $checkPath
     * @return $this
     */
    public function setBase($path, $checkPath = false)
    {
        $this->base = $checkPath === true ? Request::getSecureAppPath($path) : $path;

        return $this;
    }

    /**
     * Devolver al navegador archivos CSS y JS comprimidos
     * Método que devuelve un recurso CSS o JS comprimido. Si coincide el ETAG se
     * devuelve el código HTTP/304
     *
     * @param bool $disableMinify Deshabilitar minimizar
     */
    public function getMinified($disableMinify = false)
    {
        if (count($this->files) === 0) {
            return;
        }

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
        if ($etag === $etagMatch
            && !($cacheControl === 'no-cache'
                || $cacheControl === 'max-age=0'
                || $pragma === 'no-cache')
        ) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
            exit();
        }

        if ($this->type === self::FILETYPE_JS) {
            header('Content-type: application/x-javascript; charset: UTF-8');
        } elseif ($this->type === self::FILETYPE_CSS) {
            header('Content-type: text/css; charset: UTF-8');
        }

        flush();

        if ($this->checkZlib() || !ob_start('ob_gzhandler')) {
            ob_start();
        }

        foreach ($this->files as $file) {
            $filePath = $file['base'] . DIRECTORY_SEPARATOR . $file['name'];

            // Obtener el recurso desde una URL
            if ($file['type'] === 'url') {
                try {
                    $data = Util::getDataFromUrl($file['name']);
                    echo '/* URL: ' . $file['name'] . ' */' . PHP_EOL;
                    echo $data;
                } catch (SPException $e) {
                    error_log($e->getMessage());
                }
            } else {

                if ($file['min'] === true && $disableMinify === false) {
                    echo '/* MINIFIED FILE: ' . $file['name'] . ' */' . PHP_EOL;
                    if ($this->type === self::FILETYPE_JS) {
                        echo $this->jsCompress(file_get_contents($filePath));
                    } elseif ($this->type === self::FILETYPE_CSS) {
                        echo CssMin::minify(file_get_contents($filePath));
                    }
                } else {
                    echo '/* FILE: ' . $file['name'] . ' */' . PHP_EOL;
                    echo file_get_contents($filePath);
                }
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

        foreach ($this->files as $file) {
            $md5Sum .= $file['md5'];
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
     * Añadir un archivo
     *
     * @param string $file
     * @param bool $minify Si es necesario reducir
     * @return $this
     */
    public function addFile($file, $minify = true)
    {
        if (strrpos($file, ',')) {
            $files = explode(',', $file);

            foreach ($files as $filename) {
                $this->addFile($filename, $minify);
            }
        } else {
            $filePath = $this->base . DIRECTORY_SEPARATOR . $file;

            if (file_exists($filePath)) {
                $this->files[] = array(
                    'type' => 'file',
                    'base' => $this->base,
                    'name' => Request::getSecureAppFile($file, $this->base),
                    'min' => $minify === true && $this->needsMinify($file),
                    'md5' => md5_file($filePath)
                );
            } else {
                debugLog('File not found: ' . $filePath);
            }
        }

        return $this;
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

    /**
     * Añadir un recurso desde URL
     *
     * @param $url
     * @return $this
     */
    public function addUrl($url)
    {
        $this->files[] = array(
            'type' => 'url',
            'base' => $this->base,
            'name' => $url,
            'min' => false,
            'md5' => ''
        );

        return $this;
    }

    /**
     * Establecer el tipo de recurso a procesar
     *
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = (int)$type;

        return $this;
    }
}