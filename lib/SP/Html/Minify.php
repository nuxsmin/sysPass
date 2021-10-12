<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Html;

use Klein\Klein;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Http\Request;

defined('APP_ROOT') || die();

/**
 * Class Minify para la gestión de archivos JS y CSS
 *
 * @package SP
 */
final class Minify
{
    /**
     * Constantes para tipos de archivos
     */
    public const FILETYPE_JS = 1;
    public const FILETYPE_CSS = 2;
    public const OFFSET = 3600 * 24 * 30;

    protected Klein $router;

    /**
     * Array con los archivos a procesar
     */
    private array $files = [];
    /**
     * Tipos de archivos a procesar
     */
    private int $type = 0;
    /**
     * Base relativa de búsqueda de los archivos
     */
    private string $base = '';

    public function __construct(Klein $router)
    {
        $this->router = $router;
    }

    public function setBase(string $path, bool $checkPath = false): Minify
    {
        $this->base = $checkPath === true
            ? Request::getSecureAppPath($path) :
            $path;

        return $this;
    }

    /**
     * Devolver al navegador archivos CSS y JS comprimidos
     * Método que devuelve un recurso CSS o JS comprimido. Si coincide el ETAG se
     * devuelve el código HTTP/304
     *
     * @param bool $disableMinify Deshabilitar minimizar
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getMinified(bool $disableMinify = false): void
    {
        if (count($this->files) === 0) {
            return;
        }

        $this->setHeaders();

        $data = '';

        foreach ($this->files as $file) {
            $filePath = $file['base'] . DIRECTORY_SEPARATOR . $file['name'];

            // Obtener el recurso desde una URL
            if ($file['type'] === 'url') {
                logger('URL:' . $file['name']);

//                    $data .= '/* URL: ' . $file['name'] . ' */' . PHP_EOL . Util::getDataFromUrl($file['name']);
            } else if ($file['min'] === true && $disableMinify === false) {
                $data .= '/* MINIFIED FILE: ' . $file['name'] . ' */' . PHP_EOL;

                if ($this->type === self::FILETYPE_JS) {
                    $data .= $this->jsCompress(file_get_contents($filePath));
                }
            } else {
                $data .= PHP_EOL . '/* FILE: ' . $file['name'] . ' */' . PHP_EOL . file_get_contents($filePath);
            }
        }

        $this->router->response()->body($data);
    }

    /**
     * Sets HTTP headers
     */
    protected function setHeaders(): void
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

        switch ($this->type) {
            case self::FILETYPE_JS;
                $response->header('Content-type', 'application/javascript; charset: UTF-8');
                break;
            case self::FILETYPE_CSS:
                $response->header('Content-type', 'text/css; charset: UTF-8');
                break;
        }
    }

    /**
     * Calcular el hash MD5 de varios archivos.
     *
     * @return string Con el hash
     */
    private function getEtag(): string
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
     *
     * @return string
     */
    private function jsCompress(string $buffer): string
    {
        $regexReplace = [
            '#/\*[^*]*\*+([^/][^*]*\*+)*/#',
            '#^[\s\t]*//.*$#m',
            '#[\s\t]+$#m',
            '#^[\s\t]+#m',
            '#\s*//\s.*$#m'
        ];

        return str_replace(["\r\n", "\r", "\n", "\t"], '', preg_replace($regexReplace, '', $buffer));
    }

    public function addFilesFromString(
        string $files,
        bool   $minify = true
    ): Minify
    {
        if (strrpos($files, ',')) {
            $filesList = explode(',', $files);

            foreach ($filesList as $filename) {
                $this->addFile($filename, $minify);
            }
        } else {
            $this->addFile($files, $minify);
        }

        return $this;
    }

    /**
     * Añadir un archivo
     *
     * @param string      $file
     * @param bool        $minify Si es necesario reducir
     * @param string|null $base
     *
     * @return \SP\Html\Minify
     */
    public function addFile(
        string  $file,
        bool    $minify = true,
        ?string $base = null
    ): Minify
    {
        if ($base === null) {
            $base = $this->base;
            $filePath = $this->base . DIRECTORY_SEPARATOR . $file;
        } else {
            $filePath = $base . DIRECTORY_SEPARATOR . $file;
        }

        if (file_exists($filePath)) {
            $this->files[] = [
                'type' => 'file',
                'base' => $base,
                'name' => Request::getSecureAppFile($file, $base),
                'min' => $minify === true && $this->needsMinify($file),
                'md5' => md5_file($filePath)
            ];
        } else {
            logger('File not found: ' . $filePath);
        }

        return $this;
    }

    /**
     * Comprobar si es necesario reducir
     */
    private function needsMinify(string $file): bool
    {
        return !preg_match('/\.min|pack\.css|js/', $file);
    }

    public function addFiles(array $files, bool $minify = true): Minify
    {
        foreach ($files as $filename) {
            $this->processFile($filename, $minify);
        }

        return $this;
    }

    protected function processFile(string $file, bool $minify = true): void
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
            logger('File not found: ' . $filePath);
        }
    }

    /**
     * Añadir un recurso desde URL
     */
    public function addUrl(string $url): Minify
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
     */
    public function setType(int $type): Minify
    {
        $this->type = $type;

        return $this;
    }
}