<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Account;

use SP\Util\Filter;


/**
 * Class AccountSearchFilter
 *
 * @package SP\Account
 */
class AccountSearchFilter
{
    /**
     * Constantes de ordenación
     */
    const SORT_DIR_ASC = 0;
    const SORT_DIR_DESC = 1;
    const SORT_LOGIN = 3;
    const SORT_URL = 4;
    const SORT_CATEGORY = 2;
    const SORT_CLIENT = 5;
    const SORT_NAME = 1;

    /**
     * @var int El número de registros de la última consulta
     */
    public static $queryNumRows;
    /**
     * @var bool
     */
    private $globalSearch = false;
    /**
     * @var string
     */
    private $txtSearch;
    /**
     * @var int
     */
    private $clientId = 0;
    /**
     * @var int
     */
    private $categoryId = 0;
    /**
     * @var array
     */
    private $tagsId = [];
    /**
     * @var int
     */
    private $sortOrder = 0;
    /**
     * @var int
     */
    private $sortKey = 0;
    /**
     * @var int
     */
    private $limitStart = 0;
    /**
     * @var int
     */
    private $limitCount;
    /**
     * @var bool
     */
    private $sortViews;
    /**
     * @var bool
     */
    private $searchFavorites = false;
    /**
     * @var array
     */
    private $stringFilters;

    /**
     * @return boolean
     */
    public function isSearchFavorites()
    {
        return $this->searchFavorites;
    }

    /**
     * @param boolean $searchFavorites
     * @return $this
     */
    public function setSearchFavorites($searchFavorites)
    {
        $this->searchFavorites = (bool)$searchFavorites;

        return $this;
    }

    /**
     * @return int
     */
    public function getGlobalSearch()
    {
        return $this->globalSearch;
    }

    /**
     * @param int $globalSearch
     * @return $this
     */
    public function setGlobalSearch($globalSearch)
    {
        $this->globalSearch = $globalSearch;

        return $this;
    }

    /**
     * @return string
     */
    public function getTxtSearch()
    {
        return $this->txtSearch;
    }

    /**
     * @param string $txtSearch
     * @return $this
     */
    public function setTxtSearch($txtSearch)
    {
        $this->txtSearch = Filter::safeSearchString($txtSearch);

        return $this;
    }

    /**
     * @return int
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param int $clientId
     * @return $this
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimitStart()
    {
        return $this->limitStart;
    }

    /**
     * @param int $limitStart
     * @return $this
     */
    public function setLimitStart($limitStart)
    {
        $this->limitStart = $limitStart;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimitCount()
    {
        return $this->limitCount;
    }

    /**
     * @param int $limitCount
     * @return $this
     */
    public function setLimitCount($limitCount)
    {
        $this->limitCount = $limitCount;

        return $this;
    }

    /**
     * @return array
     */
    public function getTagsId()
    {
        return $this->tagsId;
    }

    /**
     * @param array $tagsId
     * @return $this
     */
    public function setTagsId($tagsId)
    {
        if (is_array($tagsId)) {
            $this->tagsId = $tagsId;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getStringFilters()
    {
        return $this->stringFilters;
    }

    /**
     * @param array $stringFilters
     */
    public function setStringFilters(array $stringFilters)
    {
        $this->stringFilters = $stringFilters;
    }

    /**
     * Devuelve la cadena de ordenación de la consulta
     *
     * @return string
     */
    public function getOrderString()
    {
        switch ($this->sortKey) {
            case self::SORT_NAME:
                $orderKey[] = 'name';
                break;
            case self::SORT_CATEGORY:
                $orderKey[] = 'categoryName';
                break;
            case self::SORT_LOGIN:
                $orderKey[] = 'login';
                break;
            case self::SORT_URL:
                $orderKey[] = 'url';
                break;
            case self::SORT_CLIENT:
                $orderKey[] = 'clientName';
                break;
            default :
                $orderKey[] = 'name';
                break;
        }

        if ($this->isSortViews() && !$this->getSortKey()) {
            array_unshift($orderKey, 'countView DESC');
            $this->setSortOrder(self::SORT_DIR_DESC);
        }

        $orderDir = ($this->sortOrder === self::SORT_DIR_ASC) ? 'ASC' : 'DESC';
        return sprintf('%s %s', implode(',', $orderKey), $orderDir);
    }

    /**
     * @return boolean
     */
    public function isSortViews()
    {
        return $this->sortViews;
    }

    /**
     * @param boolean $sortViews
     * @return $this
     */
    public function setSortViews($sortViews)
    {
        $this->sortViews = $sortViews;

        return $this;
    }

    /**
     * @return int
     */
    public function getSortKey()
    {
        return $this->sortKey;
    }

    /**
     * @param int $sortKey
     * @return $this
     */
    public function setSortKey($sortKey)
    {
        $this->sortKey = $sortKey;

        return $this;
    }
}