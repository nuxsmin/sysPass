<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers;

use SP\Html\Minify;
use SP\Http\Request;

/**
 * Class ResourceController
 *
 * @package SP\Modules\Web\Controllers
 */
class ResourceController extends SimpleControllerBase
{
    /**
     * @var Minify
     */
    protected $minify;

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function cssAction()
    {
        $file = Request::analyzeString('f');
        $base = Request::analyzeString('b');

        $minify = $this->dic->get(Minify::class);

        if ($file && $base) {
            $minify->setType(Minify::FILETYPE_CSS)
                ->setBase(urldecode($base), true)
                ->addFilesFromString(urldecode($file))
                ->getMinified();
        } else {
            $minify->setType(Minify::FILETYPE_CSS)
                ->setBase(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'css')
                ->addFiles(['reset.min.css',
                    'jquery-ui.min.css',
                    'jquery-ui.structure.min.css',
                    'jquery.tagsinput.min.css',
                    'fonts.min.css',
                    'material-icons.min.css',
                    'toastr.min.css',
                    'magnific-popup.min.css'
                ], false)
                ->getMinified();
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function jsAction()
    {
        $file = Request::analyzeString('f');
        $base = Request::analyzeString('b');

        $minify = $this->dic->get(Minify::class);

        if ($file && $base) {
            $minify->setType(Minify::FILETYPE_JS)
                ->setBase(urldecode($base), true)
                ->addFilesFromString(urldecode($file))
                ->getMinified();
        } else {
            $minify->setType(Minify::FILETYPE_JS)
                ->setBase(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'js');

            $group = Request::analyzeInt('g', 0);

            if ($group === 0) {
                $minify->addFiles([
                    'jquery-3.2.0.min.js',
                    'jquery-migrate-3.0.0.min.js',
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
                    'eventsource.min.js'], false);
            } elseif ($group === 1) {
                // FIXME: use MIN version
                $minify->addFiles([
                    'app.js',
                    'app-triggers.js',
                    'app-actions.js',
                    'app-requests.js',
                    'app-main.js'], false);
            }

            $minify->getMinified();
        }
    }
}