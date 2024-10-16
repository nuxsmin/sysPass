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

namespace SP\Modules\Web\Util;

use Exception;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Mvc\View\TemplateInterface;

use function SP\__;
use function SP\processException;

/**
 * Class ErrorUtil
 */
final class ErrorUtil
{
    /**
     * Constantes de errores
     */
    public const ERR_UNAVAILABLE             = 0;
    public const ERR_ACCOUNT_NO_PERMISSION   = 1;
    public const ERR_PAGE_NO_PERMISSION      = 2;
    public const ERR_UPDATE_MPASS            = 3;
    public const ERR_OPERATION_NO_PERMISSION = 4;
    public const ERR_EXCEPTION               = 5;

    /**
     * Establecer la plantilla de error con el código indicado.
     *
     * @param TemplateInterface $view
     * @param Exception $e
     * @param string|null $replace Template replacement
     * @param bool $render
     */
    public static function showExceptionInView(
        TemplateInterface $view,
        Exception $e,
        ?string   $replace = null,
        bool      $render = true
    ): void {
        switch (get_class($e)) {
            case UpdatedMasterPassException::class:
                self::showErrorInView($view, self::ERR_UPDATE_MPASS, $render, $replace);
                break;
            case UnauthorizedPageException::class:
                self::showErrorInView($view, self::ERR_PAGE_NO_PERMISSION, $render, $replace);
                break;
            case AccountPermissionException::class:
                self::showErrorInView($view, self::ERR_ACCOUNT_NO_PERMISSION, $render, $replace);
                break;
            default:
                self::showErrorInView($view, self::ERR_EXCEPTION, $render, $replace);
        }
    }

    /**
     * Establecer la plantilla de error con el código indicado.
     *
     * @param TemplateInterface $view
     * @param int $type int con el tipo de error
     * @param bool $render
     * @param string|null $replace
     */
    public static function showErrorInView(
        TemplateInterface $view,
        int     $type,
        bool    $render = true,
        ?string $replace = null
    ): void {
        self::addErrorTemplate($view, $replace);

        $error = self::getErrorTypes($type);

        $view->append(
            'errors',
            [
                'type' => SPException::WARNING,
                'description' => $error['txt'],
                'hint' => $error['hint'],
            ]
        );

        if ($render) {
            try {
                echo $view->render();
            } catch (FileNotFoundException $e) {
                processException($e);

                echo $e->getMessage();
            }
        }
    }

    private static function addErrorTemplate(TemplateInterface $view, string $replace = null): void
    {
        if ($replace === null) {
            $view->reset();
        } else {
            $view->remove($replace);
        }

        $view->addPartial('error');
    }

    /**
     * Return error message by type
     */
    protected static function getErrorTypes(int $type): array
    {
        $errorTypes = [
            self::ERR_UNAVAILABLE => [
                'txt' => __('Option unavailable'),
                'hint' => __('Please contact to the administrator'),
            ],
            self::ERR_ACCOUNT_NO_PERMISSION => [
                'txt' => __('You don\'t have permission to access this account'),
                'hint' => __('Please contact to the administrator'),
            ],
            self::ERR_PAGE_NO_PERMISSION => [
                'txt' => __('You don\'t have permission to access this page'),
                'hint' => __('Please contact to the administrator'),
            ],
            self::ERR_OPERATION_NO_PERMISSION => [
                'txt' => __('You don\'t have permission to do this operation'),
                'hint' => __('Please contact to the administrator'),
            ],
            self::ERR_UPDATE_MPASS => [
                'txt' => __('Master password updated'),
                'hint' => __('Please, restart the session for update it'),
            ],
            self::ERR_EXCEPTION => [
                'txt' => __('An exception occured'),
                'hint' => __('Please contact to the administrator'),
            ],
        ];

        return $errorTypes[$type] ?? [
            'txt' => __('An exception occured'),
            'hint' => __('Please contact to the administrator'),
        ];
    }
}
