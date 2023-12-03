<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use Klein\DataCollection\HeaderDataCollection;
use Klein\Request;
use Klein\Response;
use SP\Domain\Html\Header;
use SP\Domain\Html\MinifyInterface;
use SP\Http\Request as HttpRequest;
use SP\Util\FileUtil;

use function SP\logger;

/**
 * Class Minify
 */
abstract class Minify implements MinifyInterface
{
    private const OFFSET = 3600 * 24 * 30;

    /**
     * Array con los archivos a procesar
     */
    private array $files = [];
    /**
     * Base relativa de búsqueda de los archivos
     */
    private string $base = '';

    public function __construct(private readonly Response $response, private readonly Request $request)
    {
    }

    /**
     * Devolver al navegador archivos CSS y JS comprimidos
     * Método que devuelve un recurso CSS o JS comprimido. Si coincide el ETAG se
     * devuelve el código HTTP/304
     */
    public function getMinified(): void
    {
        if (count($this->files) === 0) {
            return;
        }

        $this->setHeaders();
        $this->response->body($this->minify($this->files));
    }

    /**
     * Sets HTTP headers
     */
    private function setHeaders(): void
    {
        $headers = $this->request->headers();

        $etag = $this->getEtag();
        $this->checkEtag($headers, $etag);

        $this->response->header(Header::ETAG->value, $etag);
        $this->response->header(
            Header::CACHE_CONTROL->value,
            sprintf('public, max-age={%d}, must-revalidate', self::OFFSET)
        );
        $this->response->header(Header::PRAGMA->value, sprintf('public; maxage={%d}', self::OFFSET));
        $this->response->header(Header::EXPIRES->value, gmdate('D, d M Y H:i:s \G\M\T', time() + self::OFFSET));
        $this->response->header(Header::CONTENT_TYPE->value, $this->getContentTypeHeader());
    }

    /**
     * Calcular el hash de varios archivos.
     *
     * @return string Con el hash
     */
    private function getEtag(): string
    {
        return sha1(array_reduce($this->files, static fn(string $out, array $file) => $out . $file['hash'], ''));
    }

    /**
     * @param HeaderDataCollection $headers
     * @param string $etag
     * @return void
     */
    private function checkEtag(HeaderDataCollection $headers, string $etag): void
    {
        // Devolver código 304 si la versión es la misma y no se solicita refrescar
        if ($etag === $headers->get(Header::IF_NONE_MATCH->value)
            && !($headers->get(Header::CACHE_CONTROL->value) === 'no-cache'
                 || $headers->get(Header::CACHE_CONTROL->value) === 'max-age=0'
                 || $headers->get(Header::PRAGMA->value) === 'no-cache')
        ) {
            $this->response->header($_SERVER['SERVER_PROTOCOL'], '304 Not Modified');
            $this->response->send();
            exit();
        }
    }

    abstract protected function getContentTypeHeader(): string;

    abstract protected function minify(array $files): string;

    public function addFilesFromString(
        string $files,
        bool   $minify = true
    ): MinifyInterface {
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
     * @param string $file
     * @param bool $minify Si es necesario reducir
     * @param string|null $base
     *
     * @return MinifyInterface
     */
    public function addFile(
        string  $file,
        bool    $minify = true,
        ?string $base = null
    ): MinifyInterface {
        $filePath = FileUtil::buildPath($base ?? $this->base, $file);

        if (file_exists($filePath)) {
            $this->files[] = Minify::buildFile($base, $file, $minify, $filePath);
        } else {
            logger('File not found: ' . $filePath);
        }

        return $this;
    }

    private static function buildFile(string $base, string $file, bool $minify, string $filePath): array
    {
        return [
            'type' => 'file',
            'base' => $base,
            'name' => HttpRequest::getSecureAppFile($file, $base),
            'min' => $minify === true && Minify::needsMinify($file),
            'hash' => sha1_file($filePath)
        ];
    }

    /**
     * Comprobar si es necesario reducir
     * @param string $file
     * @return bool
     */
    private static function needsMinify(string $file): bool
    {
        return !preg_match('/\.min|pack\.css|js/', $file);
    }

    public function addFiles(array $files, bool $minify = true): MinifyInterface
    {
        foreach ($files as $filename) {
            $this->processFile($filename, $minify);
        }

        return $this;
    }

    private function processFile(string $file, bool $minify = true): void
    {
        $filePath = FileUtil::buildPath($this->base, $file);

        if (file_exists($filePath)) {
            $this->files[] = Minify::buildFile($this->base, $file, $minify, $filePath);
        } else {
            logger('File not found: ' . $filePath);
        }
    }

    public function builder(string $base, bool $insecure = false): MinifyInterface
    {
        $clone = clone $this;
        $clone->setBase($base, $insecure);

        return $clone;
    }

    private function setBase(string $path, bool $insecure = false): void
    {
        $this->base = $insecure ? HttpRequest::getSecureAppPath($path) : $path;
    }
}
