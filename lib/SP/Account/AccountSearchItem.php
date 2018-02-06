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

use SP\Config\ConfigData;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\AccountSearchVData;
use SP\Html\Html;

/**
 * Class AccountSearchItem para contener los datos de cada cuenta en la búsqueda
 *
 * @package SP\Controller
 */
class AccountSearchItem
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
     * @var AccountSearchVData
     */
    protected $accountSearchVData;
    /**
     * @var string
     */
    protected $color;
    /**
     * @var string
     */
    protected $link;
    /**
     * @var bool
     */
    protected $url_islink = false;
    /**
     * @var  string
     */
    protected $numFiles;
    /**
     * @var bool
     */
    protected $favorite = false;
    /**
     * @var int
     */
    protected $textMaxLength = 60;
    /**
     * @var array
     */
    protected $users;
    /**
     * @var array
     */
    protected $tags;
    /**
     * @var array
     */
    protected $userGroups;
    /**
     * @var ConfigData
     */
    private $configData;
    /**
     * @var AccountAcl
     */
    private $accountAcl;

    use InjectableTrait;

    /**
     * AccountsSearchItem constructor.
     *
     * @param AccountSearchVData $accountSearchVData
     * @param AccountAcl         $accountAcl
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct(AccountSearchVData $accountSearchVData, AccountAcl $accountAcl)
    {
        $this->injectDependencies();

        $this->accountSearchVData = $accountSearchVData;
        $this->accountAcl = $accountAcl;
    }

    /**
     * @param ConfigData $configData
     */
    public function inject(ConfigData $configData)
    {
        $this->configData = $configData;
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
        return (!$this->accountAcl->isShow() && self::$requestEnabled);
    }

    /**
     * @return bool
     */
    public function isShowCopyPass()
    {
        return ($this->accountAcl->isShowViewPass() && !$this->configData->isAccountPassToImage());
    }

    /**
     * @return bool
     */
    public function isShowViewPass()
    {
        return $this->accountAcl->isShowViewPass();
    }


    /**
     * @return bool
     */
    public function isShowOptional()
    {
        return (!self::$optionalActions && $this->accountAcl->isShow());
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
        return Html::truncate($this->accountSearchVData->getUrl(), $this->textMaxLength);
    }

    /**
     * @return boolean
     */
    public function isUrlIslink()
    {
        return preg_match('#^https?://#i', $this->accountSearchVData->getUrl());
    }

    /**
     * @return string
     */
    public function getShortLogin()
    {
        return Html::truncate($this->accountSearchVData->getLogin(), $this->textMaxLength);
    }

    /**
     * @return string
     */
    public function getShortCustomerName()
    {
        return Html::truncate($this->accountSearchVData->getClientName(), $this->textMaxLength / 3);
    }

    /**
     * @return string
     */
    public function getCustomerLink()
    {
        return self::$wikiEnabled ? $this->configData->getWikiSearchurl() . $this->accountSearchVData->getClientName() : '';
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
        $accesses = sprintf('<em>(G) %s*</em><br>', $this->accountSearchVData->getUserGroupName());

        foreach ($this->userGroups as $group) {
            $accesses .= sprintf('<em>(G) %s</em><br>', $group->name);
        }

        foreach ($this->users as $user) {
            $accesses .= sprintf('<em>(U) %s</em><br>', $user->login);
        }

        return $accesses;
    }

    /**
     * @return string
     */
    public function getNumFiles()
    {
        return $this->configData->isFilesEnabled() ? $this->accountSearchVData->getNumFiles() : 0;
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
    public function isShow()
    {
        return $this->accountAcl->isShow();
    }

    /**
     * @return boolean
     */
    public function isShowView()
    {
        return $this->accountAcl->isShowView();
    }

    /**
     * @return boolean
     */
    public function isShowEdit()
    {
        return $this->accountAcl->isShowEdit();
    }

    /**
     * @return boolean
     */
    public function isShowCopy()
    {
        return $this->accountAcl->isShowCopy();
    }

    /**
     * @return boolean
     */
    public function isShowDelete()
    {
        return $this->accountAcl->isShowDelete();
    }

    /**
     * @return AccountSearchVData
     */
    public function getAccountSearchVData()
    {
        return $this->accountSearchVData;
    }

    /**
     * @return string
     */
    public function getShortNotes()
    {
        $accountNotes = '';

        if ($this->accountSearchVData->getNotes()) {
            $accountNotes = (strlen($this->accountSearchVData->getNotes()) > 300) ? substr($this->accountSearchVData->getNotes(), 0, 300) . '...' : $this->accountSearchVData->getNotes();
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
        return $this->accountSearchVData->getPassDateChange() > 0 && time() > $this->accountSearchVData->getPassDateChange();
    }

    /**
     * @param array $userGroups
     */
    public function setUserGroups(array $userGroups)
    {
        $this->userGroups = $userGroups;
    }

    /**
     * @param array $users
     */
    public function setUsers(array $users)
    {
        $this->users = $users;
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
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }
}