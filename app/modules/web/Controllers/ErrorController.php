<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
use DI\DependencyException;
use DI\NotFoundException;
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
final class ErrorController
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
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(Container $container, $actionName)
    {
        $this->router = $container->get(Klein::class);

        $this->view = $container->get(Template::class);
        $this->view->setBase('error');

        $this->layoutHelper = $container->get(LayoutHelper::class);
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
            'description' => __('Application on maintenance'),
            'hint' => __('It will be running shortly')
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
            'description' => __('Error while checking the database'),
            'hint' => __('Please contact to the administrator')
        ]);

        $this->view();
    }

    /**
     * databaseErrorAction
     */
    public function databaseConnectionAction()
    {
        $this->layoutHelper->getPublicLayout('error-database');

        $this->view->append('errors', [
            'type' => SPException::CRITICAL,
            'description' => __('Unable to connect to DB'),
            'hint' => __('Please contact to the administrator')
        ]);

        $this->view();
    }
}