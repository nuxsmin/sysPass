<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Domain\Account\Dtos;

use SP\Domain\Account\Ports\AccountSearchConstants;

/**
 * Class AccountSearchFilter
 */
final class AccountSearchFilterDto
{
    /**
     * @var int|null El número de registros de la última consulta
     */
    public static ?int $queryNumRows;
    private bool       $globalSearch = false;
    private ?string    $txtSearch    = null;
    /**
     * @var string|null Search string without special filters
     */
    private ?string $cleanTxtSearch  = null;
    private ?int    $clientId        = null;
    private ?int    $categoryId      = null;
    private ?array  $tagsId          = null;
    private int $sortOrder = AccountSearchConstants::SORT_DIR_ASC;
    private int $sortKey   = AccountSearchConstants::SORT_DEFAULT;
    private int     $limitStart      = 0;
    private ?int    $limitCount      = null;
    private ?bool   $sortViews       = null;
    private bool    $searchFavorites = false;
    private ?string $filterOperator  = null;

    /**
     * @param string $txtSearch
     *
     * @return AccountSearchFilterDto
     */
    public static function build(string $txtSearch): AccountSearchFilterDto
    {
        return (new self())->setTxtSearch($txtSearch);
    }

    public function isSearchFavorites(): bool
    {
        return $this->searchFavorites;
    }

    public function setSearchFavorites(bool $searchFavorites): AccountSearchFilterDto
    {
        $this->searchFavorites = $searchFavorites;

        return $this;
    }

    public function getGlobalSearch(): bool
    {
        return $this->globalSearch;
    }

    public function setGlobalSearch(bool $globalSearch): AccountSearchFilterDto
    {
        $this->globalSearch = $globalSearch;

        return $this;
    }

    public function getTxtSearch(): ?string
    {
        return $this->txtSearch;
    }

    public function setTxtSearch(?string $txtSearch): AccountSearchFilterDto
    {
        $this->txtSearch = $txtSearch;

        return $this;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function setClientId(?int $clientId): AccountSearchFilterDto
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): AccountSearchFilterDto
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): AccountSearchFilterDto
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getLimitStart(): int
    {
        return $this->limitStart;
    }

    public function setLimitStart(int $limitStart): AccountSearchFilterDto
    {
        $this->limitStart = $limitStart;

        return $this;
    }

    public function getLimitCount(): ?int
    {
        return $this->limitCount;
    }

    public function setLimitCount(?int $limitCount): AccountSearchFilterDto
    {
        $this->limitCount = $limitCount;

        return $this;
    }

    public function getTagsId(): ?array
    {
        return $this->tagsId;
    }

    public function setTagsId(?array $tagsId): AccountSearchFilterDto
    {
        $this->tagsId = $tagsId;

        return $this;
    }

    public function hasTags(): bool
    {
        return null !== $this->tagsId && count($this->tagsId) !== 0;
    }

    public function isSortViews(): bool
    {
        return $this->sortViews ?? false;
    }

    public function getSortKey(): int
    {
        return $this->sortKey;
    }

    public function setSortKey(int $sortKey): AccountSearchFilterDto
    {
        $this->sortKey = $sortKey;

        return $this;
    }

    public function setSortViews(?bool $sortViews): AccountSearchFilterDto
    {
        $this->sortViews = $sortViews;

        return $this;
    }

    public function getCleanTxtSearch(): ?string
    {
        return $this->cleanTxtSearch;
    }

    public function setCleanTxtSearch(?string $cleanTxtSearch): void
    {
        $this->cleanTxtSearch = $cleanTxtSearch;
    }

    public function getFilterOperator(): string
    {
        return $this->filterOperator ?? AccountSearchConstants::FILTER_CHAIN_AND;
    }

    public function setFilterOperator(?string $filterOperator): void
    {
        $this->filterOperator = $filterOperator;
    }

    /**
     * Resets internal variables
     */
    public function reset(): void
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
        $this->sortOrder = AccountSearchConstants::SORT_DEFAULT;
        $this->sortKey = AccountSearchConstants::SORT_DIR_ASC;
    }
}
