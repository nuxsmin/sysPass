<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Util;

use SP\Core\Acl\AccountPermissionException;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SPException;
use SP\Mvc\View\Template;
use SP\Services\User\UpdatedMasterPassException;

/**
 * Class ErrorUtil
 *
 * @package SP\Util
 */
final class ErrorUtil
{
    /**
     * Constantes de errores
     */
    const ERR_UNAVAILABLE = 0;
    const ERR_ACCOUNT_NO_PERMISSION = 1;
    const ERR_PAGE_NO_PERMISSION = 2;
    const ERR_UPDATE_MPASS = 3;
    const ERR_OPERATION_NO_PERMISSION = 4;
    const ERR_EXCEPTION = 5;

    /**
     * Establecer la plantilla de error con el código indicado.
     *
     * @param \SP\Mvc\View\Template $view
     * @param \Exception            $e
     * @param  string               $replace Template replacement
     * @param bool                  $render
     */
    public static function showExceptionInView(Template $view,
                                               \Exception $e,
                                               $replace = null,
                                               $render = true)
    {
        if ($replace === null) {
            $view->resetTemplates();

            if ($view->hashContentTemplates()) {
                $view->resetContentTemplates();
                $view->addContentTemplate('error', Template::PARTIALS_DIR);
            } else {
                $view->addTemplate('error', Template::PARTIALS_DIR);
            }
        } else {
            if ($view->hashContentTemplates()) {
                $view->removeContentTemplate($replace);
                $view->addContentTemplate('error', Template::PARTIALS_DIR);
            } else {
                $view->removeTemplate($replace);
                $view->addTemplate('error', Template::PARTIALS_DIR);
            }
        }

        switch (get_class($e)) {
            case UpdatedMasterPassException::class:
                self::showErrorInView($view, self::ERR_UPDATE_MPASS, $render);
                break;
            case UnauthorizedPageException::class:
                self::showErrorInView($view, self::ERR_PAGE_NO_PERMISSION, $render);
                break;
            case AccountPermissionException::class:
                self::showErrorInView($view, self::ERR_ACCOUNT_NO_PERMISSION, $render);
                break;
            default;
                self::showErrorInView($view, self::ERR_EXCEPTION, $render);
        }
    }

    /**
     * Establecer la plantilla de error con el código indicado.
     *
     * @param \SP\Mvc\View\Template $view
     * @param int                   $type int con el tipo de error
     * @param bool                  $render
     */
    public static function showErrorInView(Template $view, $type, $render = true)
    {
        $error = self::getErrorTypes($type);

        $view->append('errors',
            [
                'type' => SPException::WARNING,
                'description' => $error['txt'],
                'hint' => $error['hint']
            ]);

        if ($render) {
            try {
                echo $view->render();
            } catch (FileNotFoundException $e) {
                processException($e);

                echo $e->getMessage();
            }
        }
    }

    /**
     * Return error message by type
     *
     * @param $type
     *
     * @return mixed
     */
    protected static function getErrorTypes($type)
    {
        $errorTypes = [
            self::ERR_UNAVAILABLE => [
                'txt' => __('Opción no disponible'),
                'hint' => __('Consulte con el administrador')
            ],
            self::ERR_ACCOUNT_NO_PERMISSION => [
                'txt' => __('No tiene permisos para acceder a esta cuenta'),
                'hint' => __('Consulte con el administrador')
            ],
            self::ERR_PAGE_NO_PERMISSION => [
                'txt' => __('No tiene permisos para acceder a esta página'),
                'hint' => __('Consulte con el administrador')
            ],
            self::ERR_OPERATION_NO_PERMISSION => [
                'txt' => __('No tiene permisos para realizar esta operación'),
                'hint' => __('Consulte con el administrador')
            ],
            self::ERR_UPDATE_MPASS => [
                'txt' => __('Clave maestra actualizada'),
                'hint' => __('Reinicie la sesión para cambiarla')
            ],
            self::ERR_EXCEPTION => [
                'txt' => __('Se ha producido una excepción'),
                'hint' => __('Consulte con el administrador')
            ]
        ];

        if (!isset($errorTypes[$type])) {
            return [
                'txt' => __('Se ha producido una excepción'),
                'hint' => __('Consulte con el administrador')
            ];
        }

        return $errorTypes[$type];
    }
}