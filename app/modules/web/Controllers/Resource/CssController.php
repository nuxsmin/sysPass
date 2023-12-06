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

namespace SP\Modules\Web\Controllers\Resource;

use SP\Infrastructure\File\FileHandler;
use SP\Util\FileUtil;

/**
 * Class CssController
 */
final class CssController extends ResourceBase
{
    private const CSS_MIN_FILES = [
        'reset.min.css',
        'jquery-ui.min.css',
        'jquery-ui.structure.min.css',
        'material-icons.min.css',
        'toastr.min.css',
        'magnific-popup.min.css',
    ];

    /**
     * Return CSS resources
     */
    public function cssAction(): void
    {
        $file = $this->request->analyzeString('f');
        $base = $this->request->analyzeString('b');

        if ($file && $base) {
            $files = $this->buildFiles(urldecode($base), explode(',', urldecode($file)));

            $this->minify->builder(true)
                         ->addFiles($files)
                         ->getMinified();
        } else {
            $files = $this->buildFiles(FileUtil::buildPath(PUBLIC_PATH, 'vendor', 'css'), self::CSS_MIN_FILES);

            $this->minify->builder()
                         ->addFiles($files, false)
                         ->addFile(new FileHandler(FileUtil::buildPath(PUBLIC_PATH, 'css', 'fonts.min.css')), false)
                         ->getMinified();
        }
    }

    /**
     * @param string $base
     * @param array $files
     * @return FileHandler[]
     */
    private function buildFiles(string $base, array $files): array
    {
        return array_map(
            fn(string $file) => new FileHandler(FileUtil::buildPath($base, $file)),
            $files
        );
    }
}
