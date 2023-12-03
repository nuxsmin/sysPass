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

use SP\Util\FileUtil;

/**
 * Class MinifyCss
 */
final class MinifyCss extends Minify
{

    protected function minify(array $files): string
    {
        $data = '';

        foreach ($files as $file) {
            $filePath = FileUtil::buildPath($file['base'], $file['name']);
            $data .= sprintf('%s/* FILE: %s */%s%s', PHP_EOL, $file['name'], PHP_EOL, file_get_contents($filePath));
        }

        return $data;
    }

    protected function getContentTypeHeader(): string
    {
        return 'text/css; charset: UTF-8';
    }
}
