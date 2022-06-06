<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Html\Minify;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\SimpleControllerBase;

/**
 * Class ResourceController
 *
 * @package SP\Modules\Web\Controllers
 */
final class JsController extends SimpleControllerBase
{

    private const JS_MIN_FILES     = [
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

    private Minify $minify;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        Minify $minify
    ) {
        parent::__construct($application, $theme, $router, $acl, $request, $extensionChecker);

        $this->minify = $minify;

        $this->request->verifySignature($this->configData->getPasswordSalt());
    }

    /**
     * Returns JS resources
     */
    public function jsAction(): void
    {
        $file = $this->request->analyzeString('f');
        $base = $this->request->analyzeString('b');

        if ($file && $base) {
            $this->minify
                ->setType(Minify::FILETYPE_JS)
                ->setBase(urldecode($base), true)
                ->addFilesFromString(urldecode($file))
                ->getMinified();
        } else {
            $group = $this->request->analyzeInt('g', 0);

            if ($group === 0) {
                $this->minify->setType(Minify::FILETYPE_JS)
                    ->setBase(PUBLIC_PATH.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'js');

                $this->minify->addFiles(self::JS_MIN_FILES, false);
            } elseif ($group === 1) {
                $this->minify->setType(Minify::FILETYPE_JS)
                    ->setBase(PUBLIC_PATH.DIRECTORY_SEPARATOR.'js');

                $this->minify->addFiles(self::JS_APP_MIN_FILES, false);
            }

            $this->minify->getMinified();
        }
    }
}