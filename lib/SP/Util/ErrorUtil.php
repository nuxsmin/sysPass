<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Exceptions\SPException;
use SP\Core\Template;

/**
 * Class ErrorUtil
 *
 * @package SP\Util
 */
class ErrorUtil
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
     * @param Template $view
     * @param int      $type int con el tipo de error
     */
    public static function showErrorInViewAndReset(Template $view, $type)
    {
        $view->resetTemplates();

        self::showErrorInView($view, $type);
    }

    /**
     * Establecer la plantilla de error con el código indicado.
     *
     * @param Template $view
     * @param int      $type int con el tipo de error
     */
    public static function showErrorInView(Template $view, $type)
    {
        $view->addPartial('error');

        $error = self::getErrorTypes($type);

        $view->append('errors',
            [
                'type' => SPException::SP_WARNING,
                'description' => $error['txt'],
                'hint' => $error['hint']
            ]);
    }

    /**
     * Return error message by type
     *
     * @param $type
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

        return $errorTypes[$type];
    }

    /**
     * Establecer la plantilla de error con el código indicado.
     *
     * @param Template $view
     * @param int      $type    int con el tipo de error
     * @param  string  $replace Template replacement
     */
    public static function showErrorFull(Template $view, $type, $replace)
    {
        $view->replaceTemplate('error-full', $replace, Template::PARTIALS_DIR);

        $error = self::getErrorTypes($type);

        $view->append('errors',
            [
                'type' => SPException::SP_WARNING,
                'description' => $error['txt'],
                'hint' => $error['hint']
            ]);
    }
}