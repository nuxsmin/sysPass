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

namespace SP\Account;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Html\Html;
use SP\Util\Checks;

/**
 * Class AccountsSearchData para contener los datos de cada cuenta en la búsqueda
 *
 * @package SP\Controller
 */
class AccountsSearchData
{
    /** @var bool */
    public static $accountLink = false;
    /** @var bool */
    public static $topNavbar = false;
    /** @var bool */
    public static $optionalActions = false;
    /** @var bool */
    public static $requestEnabled = false;
    /** @var bool */
    public static $wikiEnabled = false;
    /** @var bool */
    public static $dokuWikiEnabled = false;
    /** @var bool */
    public static $isDemoMode = false;

    /** @var int */
    private $_id = 0;
    /** @var string */
    private $_name;
    /** @var string */
    private $_login;
    /** @var string */
    private $_category_name;
    /** @var string */
    private $_customer_name;
    /** @var string */
    private $_customer_link;
    /** @var string */
    private $_color;
    /** @var string */
    private $_link;
    /** @var string */
    private $_url;
    /** @var string */
    private $_url_short;
    /** @var bool */
    private $_url_islink = false;
    /** @var string */
    private $_notes;
    /** @var string */
    private $_accesses;
    /** @var  string */
    private $_numFiles;
    /** @var bool */
    private $_favorite = false;
    /** @var bool */
    private $_showView = false;
    /** @var bool */
    private $_showViewPass = false;
    /** @var bool */
    private $_showEdit = false;
    /** @var bool */
    private $_showCopy = false;
    /** @var bool */
    private $_showDelete = false;
    /** @var int */
    private $_textMaxLength = 60;

    /**
     * @return boolean
     */
    public function isFavorite()
    {
        return $this->_favorite;
    }

    /**
     * @param boolean $favorite
     */
    public function setFavorite($favorite)
    {
        $this->_favorite = $favorite;
    }

    /**
     * @return boolean
     */
    public function isShowRequest()
    {
        return (!$this->isShow()
            && (AccountsSearchData::$requestEnabled || AccountsSearchData::$isDemoMode));
    }

    /**
     * @return boolean
     */
    public function isShow()
    {
        return ($this->_showView || $this->_showEdit || $this->_showViewPass || $this->_showCopy || $this->_showDelete);
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
        return $this->_showViewPass;
    }

    /**
     * @param boolean $showViewPass
     */
    public function setShowViewPass($showViewPass)
    {
        $this->_showViewPass = $showViewPass;
    }

    /**
     * @return bool
     */
    public function isShowOptional()
    {
        return (!AccountsSearchData::$optionalActions
            && ($this->_showEdit || $this->_showViewPass || $this->_showCopy || $this->_showDelete));
    }

    /**
     * @param int $textMaxLength
     */
    public function setTextMaxLength($textMaxLength)
    {
        $this->_textMaxLength = $textMaxLength;
    }

    /**
     * @return string
     */
    public function getUrlShort()
    {
        return $this->_url_short;
    }

    /**
     * @return boolean
     */
    public function isUrlIslink()
    {
        return $this->_url_islink;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->_login;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->_login = Html::truncate($login, $this->_textMaxLength);
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->_category_name;
    }

    /**
     * @param string $category_name
     */
    public function setCategoryName($category_name)
    {
        $this->_category_name = $category_name;
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->_customer_name;
    }

    /**
     * @param string $customer_name
     */
    public function setCustomerName($customer_name)
    {
        $this->_customer_name = Html::truncate($customer_name, $this->_textMaxLength);
    }

    /**
     * @return string
     */
    public function getCustomerLink()
    {
        return $this->_customer_link;
    }

    /**
     * @param string $customer_link
     */
    public function setCustomerLink($customer_link)
    {
        $this->_customer_link = $customer_link;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->_color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->_color = $color;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->_link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->_link = $link;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        $this->_url_short = Html::truncate($url, $this->_textMaxLength);
        $this->_url_islink = preg_match("#^https?://.*#i", $url);
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->_notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->_notes = $notes;
    }

    /**
     * @return string
     */
    public function getAccesses()
    {
        return $this->_accesses;
    }

    /**
     * @param string $accesses
     */
    public function setAccesses($accesses)
    {
        $this->_accesses = $accesses;
    }

    /**
     * @return string
     */
    public function getNumFiles()
    {
        return $this->_numFiles;
    }

    /**
     * @param string $numFiles
     */
    public function setNumFiles($numFiles)
    {
        $this->_numFiles = $numFiles;
    }

    /**
     * @return boolean
     */
    public function isShowView()
    {
        return $this->_showView;
    }

    /**
     * @param boolean $showView
     */
    public function setShowView($showView)
    {
        $this->_showView = $showView;
    }

    /**
     * @return boolean
     */
    public function isShowEdit()
    {
        return $this->_showEdit;
    }

    /**
     * @param boolean $showEdit
     */
    public function setShowEdit($showEdit)
    {
        $this->_showEdit = $showEdit;
    }

    /**
     * @return boolean
     */
    public function isShowCopy()
    {
        return $this->_showCopy;
    }

    /**
     * @param boolean $showCopy
     */
    public function setShowCopy($showCopy)
    {
        $this->_showCopy = $showCopy;
    }

    /**
     * @return boolean
     */
    public function isShowDelete()
    {
        return $this->_showDelete;
    }

    /**
     * @param boolean $showDelete
     */
    public function setShowDelete($showDelete)
    {
        $this->_showDelete = $showDelete;
    }
}