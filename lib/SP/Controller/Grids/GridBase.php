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

namespace SP\Controller\Grids;

use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\ActionsInterface;
use SP\Core\DiFactory;
use SP\Core\SessionUtil;
use SP\Core\Traits\InjectableTrait;
use SP\Core\UI\Theme;
use SP\Core\UI\ThemeIconsBase;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridPager;

/**
 * Class GridBase
 *
 * @package SP\Controller\Grids
 */
abstract class GridBase implements ActionsInterface
{
    use InjectableTrait;

    /**
     * @var ThemeIconsBase
     */
    protected $icons;
    /**
     * @var string
     */
    protected $sk;
    /**
     * @var int
     */
    protected $queryTimeStart;
    /**
     * @var bool
     */
    protected $filter = false;
    /**
     * @var ConfigData
     */
    protected $ConfigData;
    /**
     * @var Theme
     */
    protected $Theme;

    /**
     * Grids constructor.
     */
    public function __construct()
    {
        $this->injectDependencies();

        $this->sk = SessionUtil::getSessionKey();
        $this->icons = $this->Theme->getIcons();
    }

    /**
     * @param ConfigData $configData
     * @param Theme      $theme
     */
    public function inject(ConfigData $configData, Theme $theme)
    {
        $this->ConfigData = $configData;
        $this->Theme = $theme;
    }

    /**
     * Devolver el paginador por defecto
     *
     * @param DataGridActionSearch $sourceAction
     * @return DataGridPager
     */
    protected function getPager(DataGridActionSearch $sourceAction)
    {
        $GridPager = new DataGridPager();
        $GridPager->setSourceAction($sourceAction);
        $GridPager->setOnClickFunction('appMgmt/nav');
        $GridPager->setLimitStart(0);
        $GridPager->setLimitCount($this->ConfigData->getAccountCount());
        $GridPager->setIconPrev($this->icons->getIconNavPrev());
        $GridPager->setIconNext($this->icons->getIconNavNext());
        $GridPager->setIconFirst($this->icons->getIconNavFirst());
        $GridPager->setIconLast($this->icons->getIconNavLast());

        return $GridPager;
    }

    /**
     * @param boolean $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * @param int $queryTimeStart
     */
    public function setQueryTimeStart($queryTimeStart)
    {
        $this->queryTimeStart = $queryTimeStart;
    }
}