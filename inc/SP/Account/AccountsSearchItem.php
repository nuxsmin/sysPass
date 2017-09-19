<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Account;

defined('APP_ROOT') || die();

use SP\Config\Config;
use SP\Core\Session;
use SP\DataModel\AccountSearchData;
use SP\Html\Html;
use SP\Mgmt\Groups\GroupAccountsUtil;
use SP\Util\Checks;

/**
 * Class AccountsSearchData para contener los datos de cada cuenta en la búsqueda
 *
 * @package SP\Controller
 */
class AccountsSearchItem
{
    /** @var bool */
    public static $accountLink = false;
    /** @var bool */
    public static $topNavbar = false;
    /** @var bool */
    public static $optionalActions = false;
    /** @var bool */
    public static $requestEnabled = true;
    /** @var bool */
    public static $wikiEnabled = false;
    /** @var bool */
    public static $dokuWikiEnabled = false;
    /** @var bool */
    public static $isDemoMode = false;

    /**
     * @var AccountSearchData
     */
    protected $AccountSearchData;
    /** @var string */
    protected $color;
    /** @var string */
    protected $link;
    /** @var bool */
    protected $url_islink = false;
    /** @var  string */
    protected $numFiles;
    /** @var bool */
    protected $favorite = false;
    /** @var bool */
    protected $showView = false;
    /** @var bool */
    protected $showViewPass = false;
    /** @var bool */
    protected $showEdit = false;
    /** @var bool */
    protected $showCopy = false;
    /** @var bool */
    protected $showDelete = false;
    /** @var int */
    protected $textMaxLength = 60;

    /**
     * AccountsSearchItem constructor.
     *
     * @param AccountSearchData $AccountSearchData
     */
    public function __construct(AccountSearchData $AccountSearchData)
    {
        $this->AccountSearchData = $AccountSearchData;
    }

    /**
     * @return boolean
     */
    public function isFavorite()
    {
        return $this->favorite;
    }

    /**
     * @param boolean $favorite
     */
    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;
    }

    /**
     * @return boolean
     */
    public function isShowRequest()
    {
        return ((!$this->showView || !$this->showEdit || !$this->showDelete)
            && AccountsSearchItem::$requestEnabled);
    }

    /**
     * @return boolean
     */
    public function isShow()
    {
        return ($this->showView || $this->showEdit || $this->showViewPass || $this->showCopy || $this->showDelete);
    }

    /**
     * @return bool
     */
    public function isShowCopyPass()
    {
        return ($this->isShowViewPass() && !Checks::accountPassToImageIsEnabled());
    }

    /**
     * @return boolean
     */
    public function isShowViewPass()
    {
        return $this->showViewPass;
    }

    /**
     * @param boolean $showViewPass
     */
    public function setShowViewPass($showViewPass)
    {
        $this->showViewPass = $showViewPass;
    }

    /**
     * @return bool
     */
    public function isShowOptional()
    {
        return (!AccountsSearchItem::$optionalActions
            && ($this->showEdit || $this->showViewPass || $this->showCopy || $this->showDelete));
    }

    /**
     * @param int $textMaxLength
     */
    public function setTextMaxLength($textMaxLength)
    {
        $this->textMaxLength = $textMaxLength;
    }

    /**
     * @return string
     */
    public function getShortUrl()
    {
        return Html::truncate($this->AccountSearchData->getAccountUrl(), $this->textMaxLength);
    }

    /**
     * @return boolean
     */
    public function isUrlIslink()
    {
        return preg_match('#^https?://#i', $this->AccountSearchData->getAccountUrl());
    }

    /**
     * @return string
     */
    public function getShortLogin()
    {
        return Html::truncate($this->AccountSearchData->getAccountLogin(), $this->textMaxLength);
    }

    /**
     * @return string
     */
    public function getShortCustomerName()
    {
        return Html::truncate($this->AccountSearchData->getCustomerName(), $this->textMaxLength / 3);
    }

    /**
     * @return string
     */
    public function getCustomerLink()
    {
        return self::$wikiEnabled ? Config::getConfig()->getWikiSearchurl() . $this->AccountSearchData->getCustomerName() : '';
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getAccesses()
    {
        $accesses = sprintf('<em>(G) %s*</em><br>', $this->AccountSearchData->getUsergroupName());

        foreach ($this->getCacheGroups() as $group) {
            $accesses .= sprintf('<em>(G) %s</em><br>', $group->getUsergroupName());
        }

        foreach ($this->getCacheUsers() as $user) {
            $accesses .= sprintf('<em>(U) %s</em><br>', $user->getUserLogin());
        }

        return $accesses;
    }

    /**
     * Devuelve los grupos de la cuenta desde la cache
     *
     * @param bool $keys
     * @return array
     */
    public function getCacheGroups($keys = false)
    {
        $cache = $this->getCache();

        return $keys === true ? array_keys($cache['groups']) : $cache['groups'];
    }

    /**
     * Devolver los accesos desde la caché
     *
     * @return array
     */
    protected function getCache()
    {
        $accountId = $this->AccountSearchData->getAccountId();
        $cacheName = 'accountsCache';

        if (!isset($_SESSION[$cacheName][$accountId])
            || $_SESSION[$cacheName][$accountId]['time'] < (int)strtotime($this->AccountSearchData->getAccountDateEdit())
        ) {
            $session =& $_SESSION[$cacheName][$accountId];

            $session['users'] = [];
            $session['groups'] = [];

            foreach (UserAccounts::getUsersInfoForAccount($accountId) as $UserData) {
                $session['users'][$UserData->getUserId()] = $UserData;
            }

            foreach (GroupAccountsUtil::getGroupsInfoForAccount($accountId) as $GroupData) {
                $session['groups'][$GroupData->getUsergroupId()] = $GroupData;
            }

            $session['time'] = time();
        }

        return $_SESSION[$cacheName][$accountId];
    }

    /**
     * Devuelve los usuarios de la cuenta desde la cache
     *
     * @param bool $keys
     * @return array
     */
    public function getCacheUsers($keys = false)
    {
        $cache = $this->getCache();

        return $keys === true ? array_keys($cache['users']) : $cache['users'];
    }

    /**
     * @return string
     */
    public function getNumFiles()
    {
        return Checks::fileIsEnabled() ? $this->AccountSearchData->getNumFiles() : 0;
    }

    /**
     * @param string $numFiles
     */
    public function setNumFiles($numFiles)
    {
        $this->numFiles = $numFiles;
    }

    /**
     * @return boolean
     */
    public function isShowView()
    {
        return $this->showView;
    }

    /**
     * @param boolean $showView
     */
    public function setShowView($showView)
    {
        $this->showView = $showView;
    }

    /**
     * @return boolean
     */
    public function isShowEdit()
    {
        return $this->showEdit;
    }

    /**
     * @param boolean $showEdit
     */
    public function setShowEdit($showEdit)
    {
        $this->showEdit = $showEdit;
    }

    /**
     * @return boolean
     */
    public function isShowCopy()
    {
        return $this->showCopy;
    }

    /**
     * @param boolean $showCopy
     */
    public function setShowCopy($showCopy)
    {
        $this->showCopy = $showCopy;
    }

    /**
     * @return boolean
     */
    public function isShowDelete()
    {
        return $this->showDelete;
    }

    /**
     * @param boolean $showDelete
     */
    public function setShowDelete($showDelete)
    {
        $this->showDelete = $showDelete;
    }

    /**
     * @return AccountSearchData
     */
    public function getAccountSearchData()
    {
        return $this->AccountSearchData;
    }

    /**
     * @param AccountSearchData $AccountSearchData
     */
    public function setAccountSearchData($AccountSearchData)
    {
        $this->AccountSearchData = $AccountSearchData;
    }

    /**
     * @return string
     */
    public function getShortNotes()
    {
        $accountNotes = '';

        if ($this->AccountSearchData->getAccountNotes()) {
            $accountNotes = (strlen($this->AccountSearchData->getAccountNotes()) > 300) ? substr($this->AccountSearchData->getAccountNotes(), 0, 300) . '...' : $this->AccountSearchData->getAccountNotes();
            $accountNotes = nl2br(wordwrap(htmlspecialchars($accountNotes), 50, '<br>', true));
        }

        return $accountNotes;
    }

    /**
     * Develve si la clave ha caducado
     *
     * @return bool
     */
    public function isPasswordExpired()
    {
        return $this->AccountSearchData->getAccountPassDateChange() > 0 && time() > $this->AccountSearchData->getAccountPassDateChange();
    }
}