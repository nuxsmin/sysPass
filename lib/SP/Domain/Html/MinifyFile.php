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

namespace SP\Domain\Html;

use SP\Http\Request as HttpRequest;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;

/**
 * Class MinifyFile
 */
final class MinifyFile
{
    public function __construct(
        private readonly FileHandlerInterface $fileHandler,
        private readonly bool                 $minify,
        private readonly bool                 $insecure
    ) {
    }

    public function getHash(): string
    {
        return $this->fileHandler->getHash();
    }

    public function needsMinify(): bool
    {
        return $this->minify === true && !preg_match('/\.min|pack\.css|js/', $this->fileHandler->getName());
    }

    public function getName(): string
    {
        return HttpRequest::getSecureAppFile($this->fileHandler->getName(), $this->fileHandler->getBase());
    }

    public function getBase(): string
    {
        return $this->insecure ? HttpRequest::getSecureAppPath(
            $this->fileHandler->getBase()
        ) : $this->fileHandler->getBase();
    }

    /**
     * @throws FileException
     */
    public function getContent(): string
    {
        return $this->fileHandler->readToString();
    }
}
