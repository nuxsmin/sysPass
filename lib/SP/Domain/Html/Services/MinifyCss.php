<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Html\Services;

use SP\Domain\Http\Header;
use SP\Infrastructure\File\FileException;
use SplObjectStorage;

/**
 * Class MinifyCss
 */
final class MinifyCss extends Minify
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
            $data .= sprintf('%s/* FILE: %s */%s%s', PHP_EOL, $file->getName(), PHP_EOL, $file->getContent());
        }

        return $data;
    }

    protected function getContentTypeHeader(): string
    {
        return Header::CONTENT_TYPE_CSS->value;
    }
}
