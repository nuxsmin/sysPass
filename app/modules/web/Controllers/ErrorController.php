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

namespace SP\Modules\Web\Controllers;

use DI\Container;
use Klein\Klein;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SPException;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Mvc\View\Template;

/**
 * Class ErrorController
 *
 * @package SP\Modules\Web\Controllers
 */
class ErrorController
{
    /**
     * @var Template
     */
    protected $view;
    /**
     * @var Klein
     */
    protected $router;
    /**
     * @var LayoutHelper
     */
    protected $layoutHelper;

    /**
     * ErrorController constructor.
     *
     * @param Container $container
     * @param string    $actionName
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(Container $container, $actionName)
    {
        $this->view = $container->get(Template::class);
        $this->view->setBase('error');

        $this->router = $container->get(Klein::class);
        $this->layoutHelper = $container->get(LayoutHelper::class);
        $this->layoutHelper->getPublicLayout('error');
    }

    /**
     * indexAction
     */
    public function indexAction()
    {
        $this->layoutHelper->getPublicLayout('error');
        $this->view();
    }

    /**
     * Mostrar los datos de la plantilla
     */
    protected function view()
    {
        try {
            echo $this->view->render();
        } catch (FileNotFoundException $e) {
            processException($e);

            echo __($e->getMessage());
        }

        die();
    }

    /**
     * databaseErrorAction
     */
    public function maintenanceErrorAction()
    {
        $this->layoutHelper->getPublicLayout('error-maintenance');

        $this->view->append('errors', [
            'type' => SPException::WARNING,
            'description' => __('Aplicación en mantenimiento'),
            'hint' => __('En breve estará operativa')
        ]);

        $this->view();
    }

    /**
     * databaseErrorAction
     */
    public function databaseErrorAction()
    {
        $this->layoutHelper->getPublicLayout('error-database');

        $this->view->append('errors', [
            'type' => SPException::CRITICAL,
            'description' => __('Error en la verificación de la base de datos'),
            'hint' => __('Consulte con el administrador')
        ]);

        $this->view();
    }
}