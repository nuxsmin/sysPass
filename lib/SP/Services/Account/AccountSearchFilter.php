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

namespace SP\Services\Account;

use SP\Mvc\Model\QueryCondition;


/**
 * Class AccountSearchFilter
 *
 * @package SP\Account
 */
final class AccountSearchFilter
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
    const SORT_DEFAULT = 0;

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
     * @var string Search string without special filters
     */
    private $cleanTxtSearch;
    /**
     * @var int
     */
    private $clientId;
    /**
     * @var int
     */
    private $categoryId;
    /**
     * @var array
     */
    private $tagsId;
    /**
     * @var int
     */
    private $sortOrder = self::SORT_DEFAULT;
    /**
     * @var int
     */
    private $sortKey = self::SORT_DIR_ASC;
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
     * @var QueryCondition
     */
    private $stringFilters;
    /**
     * @var string
     */
    private $filterOperator;

    /**
     * @return boolean
     */
    public function isSearchFavorites()
    {
        return $this->searchFavorites;
    }

    /**
     * @param boolean $searchFavorites
     *
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
     *
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
     *
     * @return $this
     */
    public function setTxtSearch($txtSearch)
    {
        $this->txtSearch = $txtSearch;

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
     *
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
     *
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
     *
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
     *
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
     *
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
        return $this->tagsId ?: [];
    }

    /**
     * @param array $tagsId
     *
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
     * @return bool
     */
    public function hasTags()
    {
        return !empty($this->tagsId);
    }

    /**
     * @return QueryCondition
     */
    public function getStringFilters()
    {
        return $this->stringFilters ?: new QueryCondition();
    }

    /**
     * @param QueryCondition $stringFilters
     */
    public function setStringFilters(QueryCondition $stringFilters)
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
                $orderKey[] = 'Account.name';
                break;
            case self::SORT_CATEGORY:
                $orderKey[] = 'Account.categoryName';
                break;
            case self::SORT_LOGIN:
                $orderKey[] = 'Account.login';
                break;
            case self::SORT_URL:
                $orderKey[] = 'Account.url';
                break;
            case self::SORT_CLIENT:
                $orderKey[] = 'Account.clientName';
                break;
            case self::SORT_DEFAULT:
            default:
                $orderKey[] = 'Account.clientName, Account.name';
                break;
        }

        if ($this->isSortViews() && !$this->getSortKey()) {
            array_unshift($orderKey, 'Account.countView DESC');
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
     *
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
     *
     * @return $this
     */
    public function setSortKey($sortKey)
    {
        $this->sortKey = $sortKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getCleanTxtSearch()
    {
        return $this->cleanTxtSearch;
    }

    /**
     * @param string $cleanTxtSearch
     */
    public function setCleanTxtSearch($cleanTxtSearch)
    {
        $this->cleanTxtSearch = $cleanTxtSearch;
    }

    /**
     * @return string
     */
    public function getFilterOperator()
    {
        return $this->filterOperator ?: QueryCondition::CONDITION_AND;
    }

    /**
     * @param string $filterOperator
     */
    public function setFilterOperator($filterOperator)
    {
        $this->filterOperator = $filterOperator;
    }

    /**
     * Resets internal variables
     */
    public function reset()
    {
        self::$queryNumRows = null;
        $this->categoryId = null;
        $this->clientId = null;
        $this->filterOperator = null;
        $this->globalSearch = false;
        $this->txtSearch = null;
        $this->cleanTxtSearch = null;
        $this->tagsId = null;
        $this->limitCount = null;
        $this->sortViews = null;
        $this->searchFavorites = false;
        $this->sortOrder = self::SORT_DEFAULT;
        $this->sortKey = self::SORT_DIR_ASC;
    }
}