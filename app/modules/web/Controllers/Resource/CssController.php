<?php
/*
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

namespace SP\Modules\Web\Controllers\Resource;

use SP\Core\Bootstrap\Path;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseStatus;
use SP\Domain\Common\Enums\ResponseType;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\FileSystem;

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

    #[Action(ResponseType::CALLBACK)]
    public function cssAction(): ActionResponse
    {
        $file = $this->request->analyzeString('f');
        $base = $this->request->analyzeString('b');

        if ($file && $base) {
            $files = $this->buildFiles(urldecode($base), explode(',', urldecode($file)), true);

            return new ActionResponse(
                ResponseStatus::OK,
                function () use ($files) {
                    $this->minify->builder()
                                 ->addFiles($files)
                                 ->getMinified();
                }
            );
        }

        $files = $this->buildFiles(
            FileSystem::buildPath($this->pathsContext[Path::PUBLIC], 'vendor', 'css'),
            self::CSS_MIN_FILES
        );

        $fontFile = new FileHandler(
            FileSystem::buildPath($this->pathsContext[Path::PUBLIC], 'css', 'fonts.min.css')
        );

        return new ActionResponse(
            ResponseStatus::OK,
            function () use ($files, $fontFile) {
                $this->minify->builder()
                             ->addFiles($files, false)
                             ->addFile($fontFile, false)
                             ->getMinified();
            }
        );
    }

    /**
     * @return FileHandler[]
     */
    private function buildFiles(string $base, array $files, bool $insecure = false): array
    {
        $base = $insecure ? $this->request->resolveSecureAppPath($base) : $base;

        return array_map(
            static fn(string $file) => new FileHandler(FileSystem::buildPath($base, $file)),
            $files
        );
    }
}
