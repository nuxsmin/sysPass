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

namespace SP\Core\UI;

use SP\Html\Assets\FontIcon;
use SP\Html\Assets\IconInterface;

defined('APP_ROOT') || die();

/**
 * Class ThemeIconsBase para la implementación de los iconos del tema visual
 *
 * @package SP\Core
 */
final class ThemeIcons
{
    /**
     * @var IconInterface[]
     */
    private $icons = [];

    /**
     * @return IconInterface
     */
    public function getIconWarning()
    {
        return $this->getIconByName('warning');
    }

    /**
     * @param string $name
     *
     * @return IconInterface
     */
    public function getIconByName(string $name)
    {
        if (isset($this->icons[$name])) {
            return $this->icons[$name];
        }

        return new FontIcon($name, 'mdl-color-text--indigo-A200');
    }

    /**
     * @return IconInterface
     */
    public function getIconDownload()
    {
        return $this->getIconByName('download');
    }

    /**
     * @return IconInterface
     */
    public function getIconClear()
    {
        return $this->getIconByName('clear');
    }

    /**
     * @return IconInterface
     */
    public function getIconPlay()
    {
        return $this->getIconByName('play');
    }

    /**
     * @return IconInterface
     */
    public function getIconHelp()
    {
        return $this->getIconByName('help');
    }

    /**
     * @return IconInterface
     */
    public function getIconPublicLink()
    {
        return $this->getIconByName('publicLink');
    }

    /**
     * @return IconInterface
     */
    public function getIconBack()
    {
        return $this->getIconByName('back');
    }

    /**
     * @return IconInterface
     */
    public function getIconRestore()
    {
        return $this->getIconByName('restore');
    }

    /**
     * @return IconInterface
     */
    public function getIconSave()
    {
        return $this->getIconByName('save');
    }

    /**
     * @return IconInterface
     */
    public function getIconUp()
    {
        return $this->getIconByName('up');
    }

    /**
     * @return IconInterface
     */
    public function getIconDown()
    {
        return $this->getIconByName('down');
    }

    /**
     * @return IconInterface
     */
    public function getIconViewPass()
    {
        return $this->getIconByName('viewPass');
    }

    /**
     * @return IconInterface
     */
    public function getIconCopy()
    {
        return $this->getIconByName('copy');
    }

    /**
     * @return IconInterface
     */
    public function getIconClipboard()
    {
        return $this->getIconByName('clipboard');
    }

    /**
     * @return IconInterface
     */
    public function getIconEmail()
    {
        return $this->getIconByName('email');
    }

    /**
     * @return IconInterface
     */
    public function getIconRefresh()
    {
        return $this->getIconByName('refresh');
    }

    /**
     * @return IconInterface
     */
    public function getIconEditPass()
    {
        return $this->getIconByName('editPass');
    }

    /**
     * @return IconInterface
     */
    public function getIconAppAdmin()
    {
        return $this->getIconByName('appAdmin');
    }

    /**
     * @return IconInterface
     */
    public function getIconAccAdmin()
    {
        return $this->getIconByName('accAdmin');
    }

    /**
     * @return IconInterface
     */
    public function getIconLdapUser()
    {
        return $this->getIconByName('ldapUser');
    }

    /**
     * @return IconInterface
     */
    public function getIconDisabled()
    {
        return $this->getIconByName('disabled');
    }

    /**
     * @return IconInterface
     */
    public function getIconNavPrev()
    {
        return $this->getIconByName('previous');
    }

    /**
     * @return IconInterface
     */
    public function getIconNavNext()
    {
        return $this->getIconByName('next');
    }

    /**
     * @return IconInterface
     */
    public function getIconNavFirst()
    {
        return $this->getIconByName('first');
    }

    /**
     * @return IconInterface
     */
    public function getIconNavLast()
    {
        return $this->getIconByName('last');
    }

    /**
     * @return IconInterface
     */
    public function getIconAdd()
    {
        return $this->getIconByName('add');
    }

    /**
     * @return IconInterface
     */
    public function getIconView()
    {
        return $this->getIconByName('view');
    }

    /**
     * @return IconInterface
     */
    public function getIconEdit()
    {
        return $this->getIconByName('edit');
    }

    /**
     * @return IconInterface
     */
    public function getIconDelete()
    {
        return $this->getIconByName('delete');
    }

    /**
     * @return IconInterface
     */
    public function getIconOptional()
    {
        return $this->getIconByName('optional');
    }

    /**
     * @return IconInterface
     */
    public function getIconCheck()
    {
        return $this->getIconByName('check');
    }

    /**
     * @return IconInterface
     */
    public function getIconSearch()
    {
        return $this->getIconByName('search');
    }

    /**
     * @return IconInterface
     */
    public function getIconAccount()
    {
        return $this->getIconByName('account');
    }

    /**
     * @return IconInterface
     */
    public function getIconGroup()
    {
        return $this->getIconByName('group');
    }

    /**
     * @return IconInterface
     */
    public function getIconSettings()
    {
        return $this->getIconByName('settings');
    }

    /**
     * @return IconInterface
     */
    public function getIconHeadline()
    {
        return $this->getIconByName('headline');
    }

    /**
     * @return IconInterface
     */
    public function getIconInfo()
    {
        return $this->getIconByName('info');
    }

    /**
     * @return IconInterface
     */
    public function getIconCritical()
    {
        return $this->getIconByName('critical');
    }

    /**
     * @return IconInterface
     */
    public function getIconEnabled()
    {
        return $this->getIconByName('enabled');
    }

    /**
     * @return IconInterface
     */
    public function getIconNotices()
    {
        return $this->getIconByName('notices');
    }

    /**
     * @return IconInterface
     */
    public function getIconRemove()
    {
        return $this->getIconByName('remove');
    }

    /**
     * @param string        $alias
     * @param IconInterface $icon
     */
    public function addIcon(string $alias, IconInterface $icon)
    {
        $this->icons[$alias] = $icon;
    }
}