<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\Controller;

use SP\Html\DataGrid\DataGridIcon;

/**
 * Class Icons para establecer los iconos de las vistas
 *
 * @package SP\Controller
 */
class Icons
{
    /**
     * @var DataGridIcon
     */
    private $_iconAdd;
    /**
     * @var DataGridIcon
     */
    private $_iconView;
    /**
     * @var DataGridIcon
     */
    private $_iconEdit;
    /**
     * @var DataGridIcon
     */
    private $_iconDelete;
    /**
     * @var DataGridIcon
     */
    private $_iconNavPrev;
    /**
     * @var DataGridIcon
     */
    private $_iconNavNext;
    /**
     * @var DataGridIcon
     */
    private $_iconNavFirst;
    /**
     * @var DataGridIcon
     */
    private $_iconNavLast;

    /**
     * Icons constructor.
     */
    public function __construct()
    {
        $this->setIcons();
    }

    /**
     * Establecer los iconos utilizados en el DataGrid
     */
    private function setIcons()
    {
        $this->_iconAdd = new DataGridIcon('add', 'imgs/new.png', 'fg-blue80');
        $this->_iconView = new DataGridIcon('visibility', 'imgs/view.png', 'fg-blue80');
        $this->_iconEdit = new DataGridIcon('mode_edit', 'imgs/edit.png', 'fg-orange80');
        $this->_iconDelete = new DataGridIcon('delete', 'imgs/delete.png', 'fg-red80');
        $this->_iconNavPrev = new DataGridIcon('chevron_left', 'imgs/arrow_left.png');
        $this->_iconNavPrev->setTitle(_('Página anterior'));
        $this->_iconNavNext = new DataGridIcon('chevron_right', 'imgs/arrow_right.png');
        $this->_iconNavNext->setTitle(_('Página siguiente'));
        $this->_iconNavFirst = new DataGridIcon('arrow_back', 'imgs/arrow_first.png');
        $this->_iconNavFirst->setTitle(_('Primera página'));
        $this->_iconNavLast = new DataGridIcon('arrow_forward', 'imgs/arrow_last.png');
        $this->_iconNavLast->setTitle(_('Última página'));
    }

    /**
     * @return DataGridIcon
     */
    public function getIconNavPrev()
    {
        return $this->_iconNavPrev;
    }

    /**
     * @return DataGridIcon
     */
    public function getIconNavNext()
    {
        return $this->_iconNavNext;
    }

    /**
     * @return DataGridIcon
     */
    public function getIconNavFirst()
    {
        return $this->_iconNavFirst;
    }

    /**
     * @return DataGridIcon
     */
    public function getIconNavLast()
    {
        return $this->_iconNavLast;
    }

    /**
     * @return DataGridIcon
     */
    public function getIconAdd()
    {
        return $this->_iconAdd;
    }

    /**
     * @return DataGridIcon
     */
    public function getIconView()
    {
        return $this->_iconView;
    }

    /**
     * @return DataGridIcon
     */
    public function getIconEdit()
    {
        return $this->_iconEdit;
    }

    /**
     * @return DataGridIcon
     */
    public function getIconDelete()
    {
        return $this->_iconDelete;
    }

}