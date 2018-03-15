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

defined('APP_ROOT') || die();

use SP\Account\AccountUtil;
use SP\Core\DiFactory;
use SP\Core\Upgrade\Check;
use SP\Http\Request;
use SP\Services\Task\Task;
use SP\Util\Util;

/**
 * Clase encargada de mostrar el interface principal de la aplicación
 * e interfaces que requieren de un documento html completo
 *
 * @package Controller
 */
class MainController
{
    /**
     * Obtener los datos para el interface de actualización de componentes
     *
     * @param $version
     */
    public function getUpgrade($version)
    {
        $this->setPage('upgrade');

        $this->view->addTemplate('body-header');
        $this->view->addTemplate('upgrade');
        $this->view->addTemplate('body-footer');
        $this->view->addTemplate('body-end');

        $action = Request::analyze('a');
        $type = Request::analyze('type');

        $this->view->assign('action', $action);
        $this->view->assign('type', $type);
        $this->view->assign('version', $version);
        $this->view->assign('upgradeVersion', Util::getVersionStringNormalized());
        $this->view->assign('taskId', Task::genTaskId('masterpass'));

        if (Util::checkVersion($version, '130.16011001')) {
            $this->view->assign('checkConstraints', Check::checkConstraints());

            $constraints = [];

            foreach ($this->view->checkConstraints as $key => $val) {
                if ($val > 0) {
                    $constraints[] = sprintf('%s : %s', $key, $val);
                }
            }

            $this->view->assign('constraints', $constraints);
        }

        if (Util::checkVersion($version, '210.17022601')) {
            $this->view->assign('numAccounts', AccountUtil::getTotalNumAccounts());
        }

        $this->view();
        exit();
    }
}