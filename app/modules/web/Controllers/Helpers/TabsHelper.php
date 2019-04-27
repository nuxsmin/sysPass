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

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Mvc\View\Components\DataTab;

/**
 * Class TabsHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class TabsHelper extends HelperBase
{
    /**
     * Máximo numero de acciones antes de agrupar
     */
    const MAX_NUM_ACTIONS = 3;
    /**
     * @var DataTab[]
     */
    protected $tabs = [];

    /**
     * Inicializar las plantillas para las pestañas
     *
     * @param string $route
     * @param int    $activeTab
     */
    public function renderTabs($route, $activeTab = 0)
    {
        $this->view->addTemplate('datatabs', 'common');

        $this->view->assign('tabs', $this->tabs);
        $this->view->assign('activeTab', $activeTab);
        $this->view->assign('maxNumActions', self::MAX_NUM_ACTIONS);
        $this->view->assign('tabsRoute', $route);
    }

    /**
     * Add a new data tab
     *
     * @param DataTab $tab
     */
    public function addTab(DataTab $tab)
    {
        $this->tabs[] = $tab;
    }

}