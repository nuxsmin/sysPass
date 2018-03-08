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

namespace SP\Core\UI;

use SP\Html\Assets\FontIcon;
use SP\Html\Assets\ImageIcon;

defined('APP_ROOT') || die();

/**
 * Class ThemeIconsBase para la implementación de los iconos del tema visual
 *
 * @package SP\Core
 */
class ThemeIcons
{
    /** @var  FontIcon|ImageIcon */
    public $iconAdd;
    /** @var  FontIcon|ImageIcon */
    public $iconView;
    /** @var  FontIcon|ImageIcon */
    public $iconEdit;
    /** @var  FontIcon|ImageIcon */
    public $iconDelete;
    /** @var  FontIcon|ImageIcon */
    public $iconNavPrev;
    /** @var  FontIcon|ImageIcon */
    public $iconNavNext;
    /** @var  FontIcon|ImageIcon */
    public $iconNavFirst;
    /** @var  FontIcon|ImageIcon */
    public $iconNavLast;
    /** @var  FontIcon|ImageIcon */
    public $iconEditPass;
    /** @var  FontIcon|ImageIcon */
    public $iconAppAdmin;
    /** @var  FontIcon|ImageIcon */
    public $iconAccAdmin;
    /** @var  FontIcon|ImageIcon */
    public $iconLdapUser;
    /** @var  FontIcon|ImageIcon */
    public $iconDisabled;
    /** @var  FontIcon|ImageIcon */
    public $iconEnabled;
    /** @var  FontIcon|ImageIcon */
    public $iconViewPass;
    /** @var  FontIcon|ImageIcon */
    public $iconCopy;
    /** @var  FontIcon|ImageIcon */
    public $iconClipboard;
    /** @var  FontIcon|ImageIcon */
    public $iconEmail;
    /** @var  FontIcon|ImageIcon */
    public $iconOptional;
    /** @var  FontIcon|ImageIcon */
    public $iconUp;
    /** @var  FontIcon|ImageIcon */
    public $iconDown;
    /** @var  FontIcon|ImageIcon */
    public $iconRefresh;
    /** @var  FontIcon|ImageIcon */
    public $iconPublicLink;
    /** @var  FontIcon|ImageIcon */
    public $iconBack;
    /** @var  FontIcon|ImageIcon */
    public $iconRestore;
    /** @var  FontIcon|ImageIcon */
    public $iconSave;
    /** @var  FontIcon|ImageIcon */
    public $iconHelp;
    /** @var  FontIcon|ImageIcon */
    public $iconClear;
    /** @var  FontIcon|ImageIcon */
    public $iconPlay;
    /** @var  FontIcon|ImageIcon */
    public $iconDownload;
    /** @var  FontIcon|ImageIcon */
    public $iconWarning;
    /** @var  FontIcon|ImageIcon */
    public $iconCheck;
    /** @var  FontIcon|ImageIcon */
    public $iconSearch;
    /** @var  FontIcon|ImageIcon */
    public $iconAccount;
    /** @var  FontIcon|ImageIcon */
    public $iconGroup;
    /** @var  FontIcon|ImageIcon */
    public $iconSettings;
    /** @var  FontIcon|ImageIcon */
    public $iconHeadline;
    /** @var  FontIcon|ImageIcon */
    public $iconInfo;
    /** @var  FontIcon|ImageIcon */
    public $iconCritical;
    /** @var  FontIcon|ImageIcon */
    public $iconNotices;
    /** @var  FontIcon|ImageIcon */
    public $iconRemove;

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconWarning()
    {
        return $this->iconWarning;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconDownload()
    {
        return $this->iconDownload;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconClear()
    {
        return $this->iconClear;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconPlay()
    {
        return $this->iconPlay;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconHelp()
    {
        return $this->iconHelp;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconPublicLink()
    {
        return $this->iconPublicLink;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconBack()
    {
        return $this->iconBack;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconRestore()
    {
        return $this->iconRestore;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconSave()
    {
        return $this->iconSave;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconUp()
    {
        return $this->iconUp;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconDown()
    {
        return $this->iconDown;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconViewPass()
    {
        return $this->iconViewPass;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconCopy()
    {
        return $this->iconCopy;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconClipboard()
    {
        return $this->iconClipboard;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconEmail()
    {
        return $this->iconEmail;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconRefresh()
    {
        return $this->iconRefresh;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconEditPass()
    {
        return $this->iconEditPass;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconAppAdmin()
    {
        return $this->iconAppAdmin;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconAccAdmin()
    {
        return $this->iconAccAdmin;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconLdapUser()
    {
        return $this->iconLdapUser;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconDisabled()
    {
        return $this->iconDisabled;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconNavPrev()
    {
        return $this->iconNavPrev;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconNavNext()
    {
        return $this->iconNavNext;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconNavFirst()
    {
        return $this->iconNavFirst;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconNavLast()
    {
        return $this->iconNavLast;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconAdd()
    {
        return $this->iconAdd;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconView()
    {
        return $this->iconView;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconEdit()
    {
        return $this->iconEdit;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconDelete()
    {
        return $this->iconDelete;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconOptional()
    {
        return $this->iconOptional;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconCheck()
    {
        return $this->iconCheck;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconSearch()
    {
        return $this->iconSearch;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconAccount()
    {
        return $this->iconAccount;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconGroup()
    {
        return $this->iconGroup;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconSettings()
    {
        return $this->iconSettings;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconHeadline()
    {
        return $this->iconHeadline;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconInfo()
    {
        return $this->iconInfo;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconCritical()
    {
        return $this->iconCritical;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconEnabled()
    {
        return $this->iconEnabled;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconNotices()
    {
        return $this->iconNotices;
    }

    /**
     * @return FontIcon|ImageIcon
     */
    public function getIconRemove()
    {
        return $this->iconRemove;
    }
}