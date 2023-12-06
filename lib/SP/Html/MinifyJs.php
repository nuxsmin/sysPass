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

use SP\Domain\Html\MinifyFile;
use SP\Infrastructure\File\FileException;
use SplObjectStorage;

/**
 * Class MinifyJs
 */
final class MinifyJs extends Minify
{

    /**
     * @param SplObjectStorage<MinifyFile> $files
     * @return string
     * @throws FileException
     */
    protected function minify(SplObjectStorage $files): string
    {
        $data = '';

        foreach ($files as $file) {
            $data .= sprintf('%s/* MINIFIED FILE: %s */%s', PHP_EOL, $file->getName(), PHP_EOL);
            $data .= $this->jsCompress($file->getContent());
        }

        return $data;
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

    protected function getContentTypeHeader(): string
    {
        return 'application/javascript; charset: UTF-8';
    }
}
