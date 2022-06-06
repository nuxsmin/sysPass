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
final class CssController extends SimpleControllerBase
{
    private const CSS_MIN_FILES = [
        'reset.min.css',
        'jquery-ui.min.css',
        'jquery-ui.structure.min.css',
        'material-icons.min.css',
        'toastr.min.css',
        'magnific-popup.min.css',
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
     * Returns CSS resources
     */
    public function cssAction(): void
    {
        $file = $this->request->analyzeString('f');
        $base = $this->request->analyzeString('b');

        if ($file && $base) {
            $this->minify
                ->setType(Minify::FILETYPE_CSS)
                ->setBase(urldecode($base), true)
                ->addFilesFromString(urldecode($file))
                ->getMinified();
        } else {
            $this->minify->setType(Minify::FILETYPE_CSS)
                ->setBase(PUBLIC_PATH.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'css')
                ->addFiles(self::CSS_MIN_FILES, false)
                ->addFile('fonts.min.css', false, PUBLIC_PATH.DIRECTORY_SEPARATOR.'css')
                ->getMinified();
        }
    }
}