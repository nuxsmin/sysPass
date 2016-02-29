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
    private $id = 0;
    /** @var string */
    private $name;
    /** @var string */
    private $login;
    /** @var string */
    private $category_name;
    /** @var string */
    private $customer_name;
    /** @var string */
    private $customer_link;
    /** @var string */
    private $color;
    /** @var string */
    private $link;
    /** @var string */
    private $url;
    /** @var string */
    private $url_short;
    /** @var bool */
    private $url_islink = false;
    /** @var string */
    private $notes;
    /** @var string */
    private $accesses;
    /** @var  string */
    private $numFiles;
    /** @var bool */
    private $favorite = false;
    /** @var bool */
    private $showView = false;
    /** @var bool */
    private $showViewPass = false;
    /** @var bool */
    private $showEdit = false;
    /** @var bool */
    private $showCopy = false;
    /** @var bool */
    private $showDelete = false;
    /** @var int */
    private $textMaxLength = 60;
    /** @var array  */
    private $tags =[];

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
        return (!$this->isShow()
            && (AccountsSearchData::$requestEnabled || AccountsSearchData::$isDemoMode));
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
        return (!AccountsSearchData::$optionalActions
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
    public function getUrlShort()
    {
        return $this->url_short;
    }

    /**
     * @return boolean
     */
    public function isUrlIslink()
    {
        return $this->url_islink;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = Html::truncate($login, $this->textMaxLength);
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->category_name;
    }

    /**
     * @param string $category_name
     */
    public function setCategoryName($category_name)
    {
        $this->category_name = $category_name;
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customer_name;
    }

    /**
     * @param string $customer_name
     */
    public function setCustomerName($customer_name)
    {
        $this->customer_name = Html::truncate($customer_name, $this->textMaxLength);
    }

    /**
     * @return string
     */
    public function getCustomerLink()
    {
        return $this->customer_link;
    }

    /**
     * @param string $customer_link
     */
    public function setCustomerLink($customer_link)
    {
        $this->customer_link = $customer_link;
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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
        $this->url_short = Html::truncate($url, $this->textMaxLength);
        $this->url_islink = preg_match("#^https?://.*#i", $url);
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return string
     */
    public function getAccesses()
    {
        return $this->accesses;
    }

    /**
     * @param string $accesses
     */
    public function setAccesses($accesses)
    {
        $this->accesses = $accesses;
    }

    /**
     * @return string
     */
    public function getNumFiles()
    {
        return $this->numFiles;
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
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }
}