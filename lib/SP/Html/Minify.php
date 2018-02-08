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

namespace SP\Html;

use Klein\Klein;
use SP\Core\Exceptions\SPException;
use SP\Core\Traits\InjectableTrait;
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
    use InjectableTrait;

    /**
     * Constantes para tipos de archivos
     */
    const FILETYPE_JS = 1;
    const FILETYPE_CSS = 2;
    const OFFSET = 3600 * 24 * 30;
    /**
     * @var Klein
     */
    protected $router;

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
     * Minify constructor.
     *
     * @throws \ReflectionException
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * @param Klein $router
     */
    public function inject(Klein $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $path
     * @param bool   $checkPath
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getMinified($disableMinify = false)
    {
        if (count($this->files) === 0) {
            return;
        }

        $this->setHeaders();

//        if ($this->checkZlib() || !ob_start('ob_gzhandler')) {
//            ob_start();
//        }

        $data = '';

        foreach ($this->files as $file) {
            $filePath = $file['base'] . DIRECTORY_SEPARATOR . $file['name'];

            // Obtener el recurso desde una URL
            if ($file['type'] === 'url') {
                try {
                    $data .= '/* URL: ' . $file['name'] . ' */' . PHP_EOL . Util::getDataFromUrl($file['name']);
                } catch (SPException $e) {
                    debugLog($e->getMessage());
                }
            } else {

                if ($file['min'] === true && $disableMinify === false) {
                    $data .= '/* MINIFIED FILE: ' . $file['name'] . ' */' . PHP_EOL;
                    if ($this->type === self::FILETYPE_JS) {
                        $data .= $this->jsCompress(file_get_contents($filePath));
                    }
                } else {
                    $data .= '/* FILE: ' . $file['name'] . ' */' . PHP_EOL . file_get_contents($filePath);
                }
            }
        }

        $this->router->response()->body($data);
    }

    /**
     * Sets HTTP headers
     */
    protected function setHeaders()
    {
        $response = $this->router->response();
        $headers = $this->router->request()->headers();

        $etag = $this->getEtag();

        // Devolver código 304 si la versión es la misma y no se solicita refrescar
        if ($etag === $headers->get('If-None-Match')
            && !($headers->get('Cache-Control') === 'no-cache'
                || $headers->get('Cache-Control') === 'max-age=0'
                || $headers->get('Pragma') === 'no-cache')
        ) {
            $response->header($_SERVER['SERVER_PROTOCOL'], '304 Not Modified');
            $response->send();
            exit();
        }

        $response->header('Etag', $etag);
        $response->header('Cache-Control', 'public, max-age={' . self::OFFSET . '}, must-revalidate');
        $response->header('Pragma', 'public; maxage={' . self::OFFSET . '}');
        $response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + self::OFFSET));

        if ($this->type === self::FILETYPE_JS) {
            $response->header('Content-type', 'application/x-javascript; charset: UTF-8');
        } elseif ($this->type === self::FILETYPE_CSS) {
            $response->header('Content-type', 'text/css; charset: UTF-8');
        }
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

        return str_replace(array("\r\n", "\r", "\n", "\t"), '', preg_replace($regexReplace, '', $buffer));
    }

    /**
     * @param      $files
     * @param bool $minify
     * @return Minify
     */
    public function addFilesFromString($files, $minify = true)
    {
        if (strrpos($files, ',')) {
            $files = explode(',', $files);

            foreach ($files as $filename) {
                $this->addFile($filename, $minify);
            }
        } else {
            throw new \RuntimeException('Invalid string format');
        }

        return $this;
    }

    /**
     * Añadir un archivo
     *
     * @param string $file
     * @param bool   $minify Si es necesario reducir
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
     * @param array $files
     * @param bool  $minify
     * @return Minify
     */
    public function addFiles(array $files, $minify = true)
    {
        foreach ($files as $filename) {
            $this->processFile($filename, $minify);
        }

        return $this;
    }

    /**
     * @param      $file
     * @param bool $minify
     */
    protected function processFile($file, $minify = true)
    {
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
}