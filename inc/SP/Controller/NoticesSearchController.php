<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link http://syspass.org
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

namespace SP\Controller;

defined('APP_ROOT') || die();

use SP\Config\Config;
use SP\Controller\Grids\Notices;
use SP\Core\ActionsInterface;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\DataModel\ItemSearchData;
use SP\Http\Request;
use SP\Mgmt\Notices\NoticeSearch;
use SP\Util\Checks;
use SP\Util\Json;

/**
 * Class NoticesSearchController para la gestión de búsquedas de items de accesos
 *
 * @package SP\Controller
 */
class NoticesSearchController extends GridItemsSearchController implements ActionsInterface, ItemControllerInterface
{
    use RequestControllerTrait;

    /**
     * @var ItemSearchData
     */
    protected $ItemSearchData;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->grids = new Notices();
        $this->grids->setQueryTimeStart(microtime());
        $this->ItemSearchData = new ItemSearchData();

        $this->init();
        $this->setItemSearchData();
    }

    /**
     * Establecer las propiedades de búsqueda
     */
    protected function setItemSearchData()
    {
        $this->ItemSearchData->setSeachString(Request::analyze('search'));
        $this->ItemSearchData->setLimitStart(Request::analyze('start', 0));
        $this->ItemSearchData->setLimitCount(Request::analyze('count', Config::getConfig()->getAccountCount()));
    }

    /**
     * Realizar la acción solicitada en la la petición HTTP
     *
     * @param mixed $type Tipo de acción
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doAction($type = null)
    {
        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('index', $this->activeTab);

        try {
            switch ($this->actionId) {
                case ActionsInterface::ACTION_NOT_USER_SEARCH:
                    $this->getNoticesUser();
                    break;
                default:
                    $this->invalidAction();
            }

            $this->JsonResponse->setData(['html' => $this->render()]);
        } catch (\Exception $e) {
            $this->JsonResponse->setDescription($e->getMessage());
        }

        $this->JsonResponse->setCsrf($this->view->sk);

        Json::returnJson($this->JsonResponse);
    }

    /**
     * Obtener las notificaciones de una búsqueda
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \InvalidArgumentException
     */
    protected function getNoticesUser()
    {
        $this->setAction(self::ACTION_NOT_USER_SEARCH);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('datagrid-table', 'grid');

        $Grid = $this->getGrids()->getNoticesGrid();
        $Grid->getData()->setData(NoticeSearch::getItem()->getMgmtSearchUser($this->ItemSearchData));
        $Grid->updatePager();

        $this->updatePager($Grid->getPager(), $this->ItemSearchData);

        $this->view->assign('data', $Grid);
        $this->view->assign('actionId', self::ACTION_NOT_USER);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * @return Notices
     */
    public function getGrids()
    {
        return $this->grids;
    }
}