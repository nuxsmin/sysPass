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

use Klein\Request;
use Klein\Response;
use SP\Domain\Html\Header;
use SP\Domain\Html\MinifyFile;
use SP\Domain\Html\MinifyInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;
use SplObjectStorage;

/**
 * Class Minify
 */
abstract class Minify implements MinifyInterface
{
    private const OFFSET = 3600 * 24 * 30;


    /**
     * @var SplObjectStorage<MinifyFile>
     */
    private SplObjectStorage $files;

    public function __construct(
        private readonly Response $response,
        private readonly Request $request
    ) {
        $this->files = new SplObjectStorage();
    }

    /**
     * Devolver al navegador archivos CSS y JS comprimidos
     * Método que devuelve un recurso CSS o JS comprimido. Si coincide el ETAG se
     * devuelve el código HTTP/304
     */
    public function getMinified(): void
    {
        if ($this->files->count() === 0) {
            return;
        }

        $this->setHeaders();

        if (!$this->response->isSent()) {
            $this->response->body($this->minify($this->files));
        }
    }

    /**
     * Sets HTTP headers
     */
    private function setHeaders(): void
    {
        if (($etag = $this->checkEtag()) === null) {
            return;
        }

        $this->response->header(Header::ETAG->value, $etag);
        $this->response->header(
            Header::CACHE_CONTROL->value,
            sprintf('public, max-age={%d}, must-revalidate', self::OFFSET)
        );
        $this->response->header(Header::PRAGMA->value, sprintf('public; maxage={%d}', self::OFFSET));
        $this->response->header(Header::EXPIRES->value, gmdate('D, d M Y H:i:s \G\M\T', time() + self::OFFSET));
        $this->response->header(Header::CONTENT_TYPE->value, $this->getContentTypeHeader());
    }

    private function checkEtag(): ?string
    {
        $etag = $this->getEtag();
        $headers = $this->request->headers();

        // Devolver código 304 si la versión es la misma y no se solicita refrescar
        if ($etag === $headers->get(Header::IF_NONE_MATCH->value)
            && !($headers->get(Header::CACHE_CONTROL->value) === 'no-cache'
                 || $headers->get(Header::CACHE_CONTROL->value) === 'max-age=0'
                 || $headers->get(Header::PRAGMA->value) === 'no-cache')
        ) {
            $this->response->header($this->request->server()->get('SERVER_PROTOCOL'), '304 Not Modified');
            $this->response->send();

            return null;
        }

        return $etag;
    }

    /**
     * Calcular el hash de varios archivos.
     *
     * @return string Con el hash
     */
    private function getEtag(): string
    {
        $etag = '';

        foreach ($this->files as $file) {
            $etag .= $file->getHash();
        }

        return sha1($etag);
    }

    abstract protected function getContentTypeHeader(): string;

    abstract protected function minify(SplObjectStorage $files): string;

    /**
     * @param FileHandlerInterface[] $files
     * @param bool $minify
     * @return MinifyInterface
     * @throws FileException
     */
    public function addFiles(array $files, bool $minify = true): MinifyInterface
    {
        array_walk($files, fn(FileHandlerInterface $fileHandler) => $this->addFile($fileHandler));

        return $this;
    }

    /**
     * Añadir un archivo
     *
     * @param FileHandlerInterface $fileHandler
     * @param bool $minify Si es necesario reducir
     *
     * @return MinifyInterface
     * @throws FileException
     */
    public function addFile(
        FileHandlerInterface $fileHandler,
        bool                 $minify = true
    ): MinifyInterface {
        $fileHandler->checkFileExists();

        $this->files->attach(new MinifyFile($fileHandler, $minify));

        return $this;
    }

    public function builder(): MinifyInterface
    {
        return clone $this;
    }
}
