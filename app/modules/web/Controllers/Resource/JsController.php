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
 * Class JsController
 */
final class JsController extends ResourceBase
{
    private const JS_MIN_FILES = [
        'jquery-3.3.1.min.js',
        'jquery.fileDownload.min.js',
        'clipboard.min.js',
        'selectize.min.js',
        'selectize-plugins.min.js',
        'zxcvbn-async.min.js',
        'jsencrypt.min.js',
        'spark-md5.min.js',
        'moment.min.js',
        'moment-timezone.min.js',
        'toastr.min.js',
        'jquery.magnific-popup.min.js',
        'eventsource.min.js',
    ];
    private const JS_APP_MIN_FILES = [
        'app.min.js',
        'app-config.min.js',
        'app-triggers.min.js',
        'app-actions.min.js',
        'app-requests.min.js',
        'app-util.min.js',
        'app-main.min.js',
    ];

    #[Action(ResponseType::CALLBACK)]
    public function jsAction(): ActionResponse
    {
        $file = $this->request->analyzeString('f');
        $base = $this->request->analyzeString('b');
        $group = $this->request->analyzeInt('g', 0);

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

        if ($group === 0) {
            $files = $this->buildFiles(
                FileSystem::buildPath($this->pathsContext[Path::PUBLIC], 'vendor', 'js'),
                self::JS_MIN_FILES
            );
        } elseif ($group === 1) {
            $files = $this->buildFiles(
                FileSystem::buildPath($this->pathsContext[Path::PUBLIC], 'js'),
                self::JS_APP_MIN_FILES
            );
        } else {
            return ActionResponse::ok('');
        }

        return new ActionResponse(
            ResponseStatus::OK,
            function () use ($files) {
                $this->minify->builder()
                             ->addFiles($files, false)
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
