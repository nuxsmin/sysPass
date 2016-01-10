<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core;

use SP\Html\Assets\FontIcon;
use SP\Html\Assets\ImageIcon;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class ThemeIconsBase para la implementación de los iconos del tema visual
 *
 * @package SP\Core
 */
abstract class ThemeIconsBase implements ThemeIconsInterface
{
    /** @var  FontIcon|ImageIcon */
    protected $_iconAdd;
    /** @var  FontIcon|ImageIcon */
    protected $_iconView;
    /** @var  FontIcon|ImageIcon */
    protected $_iconEdit;

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconWarning()
    {
        return $this->_iconWarning;
    }
    /** @var  FontIcon|ImageIcon */
    protected $_iconDelete;
    /** @var  FontIcon|ImageIcon */
    protected $_iconNavPrev;
    /** @var  FontIcon|ImageIcon */
    protected $_iconNavNext;
    /** @var  FontIcon|ImageIcon */
    protected $_iconNavFirst;
    /** @var  FontIcon|ImageIcon */
    protected $_iconNavLast;
    /** @var  FontIcon|ImageIcon */
    protected $_iconEditPass;
    /** @var  FontIcon|ImageIcon */
    protected $_iconAppAdmin;
    /** @var  FontIcon|ImageIcon */
    protected $_iconAccAdmin;
    /** @var  FontIcon|ImageIcon */
    protected $_iconLdapUser;
    /** @var  FontIcon|ImageIcon */
    protected $_iconDisabled;
    /** @var  FontIcon|ImageIcon */
    protected $_iconViewPass;
    /** @var  FontIcon|ImageIcon */
    protected $_iconCopy;
    /** @var  FontIcon|ImageIcon */
    protected $_iconClipboard;
    /** @var  FontIcon|ImageIcon */
    protected $_iconEmail;
    /** @var  FontIcon|ImageIcon */
    protected $_iconOptional;
    /** @var  FontIcon|ImageIcon */
    protected $_iconUp;
    /** @var  FontIcon|ImageIcon */
    protected $_iconDown;
    /** @var  FontIcon|ImageIcon */
    protected $_iconRefresh;
    /** @var  FontIcon|ImageIcon */
    protected $_iconPublicLink;
    /** @var  FontIcon|ImageIcon */
    protected $_iconBack;
    /** @var  FontIcon|ImageIcon */
    protected $_iconRestore;
    /** @var  FontIcon|ImageIcon */
    protected $_iconSave;
    /** @var  FontIcon|ImageIcon */
    protected $_iconHelp;
    /** @var  FontIcon|ImageIcon */
    protected $_iconClear;
    /** @var  FontIcon|ImageIcon */
    protected $_iconPlay;
    /** @var  FontIcon|ImageIcon */
    protected $_iconDownload;
    /** @var  FontIcon|ImageIcon */
    protected $_iconWarning;

    /**
     * Icons constructor.
     */
    public function __construct()
    {
        $this->setIcons();
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconDownload()
    {
        return $this->_iconDownload;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconClear()
    {
        return $this->_iconClear;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconPlay()
    {
        return $this->_iconPlay;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconHelp()
    {
        return $this->_iconHelp;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconPublicLink()
    {
        return $this->_iconPublicLink;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconBack()
    {
        return $this->_iconBack;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconRestore()
    {
        return $this->_iconRestore;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconSave()
    {
        return $this->_iconSave;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconUp()
    {
        return $this->_iconUp;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconDown()
    {
        return $this->_iconDown;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconViewPass()
    {
        return $this->_iconViewPass;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconCopy()
    {
        return $this->_iconCopy;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconClipboard()
    {
        return $this->_iconClipboard;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconEmail()
    {
        return $this->_iconEmail;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconRefresh()
    {
        return $this->_iconRefresh;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconEditPass()
    {
        return $this->_iconEditPass;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconAppAdmin()
    {
        return $this->_iconAppAdmin;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconAccAdmin()
    {
        return $this->_iconAccAdmin;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconLdapUser()
    {
        return $this->_iconLdapUser;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconDisabled()
    {
        return $this->_iconDisabled;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconNavPrev()
    {
        return $this->_iconNavPrev;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconNavNext()
    {
        return $this->_iconNavNext;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconNavFirst()
    {
        return $this->_iconNavFirst;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconNavLast()
    {
        return $this->_iconNavLast;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconAdd()
    {
        return $this->_iconAdd;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconView()
    {
        return $this->_iconView;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconEdit()
    {
        return $this->_iconEdit;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconDelete()
    {
        return $this->_iconDelete;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconOptional()
    {
        return $this->_iconOptional;
    }
}