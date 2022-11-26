<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Account\Services;

use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountSearchVData;
use SP\DataModel\Dto\AccountAclDto;
use SP\DataModel\Dto\AccountCache;
use SP\Domain\Account\Ports\AccountAclServiceInterface;
use SP\Domain\Account\Ports\AccountSearchRepositoryInterface;
use SP\Domain\Account\Ports\AccountSearchServiceInterface;
use SP\Domain\Account\Ports\AccountToFavoriteServiceInterface;
use SP\Domain\Account\Ports\AccountToTagRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserGroupRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserRepositoryInterface;
use SP\Domain\Account\Search\AccountSearchConstants;
use SP\Domain\Account\Search\AccountSearchFilter;
use SP\Domain\Account\Search\AccountSearchTokenizer;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileCache;
use SP\Infrastructure\File\FileCacheInterface;
use SP\Infrastructure\File\FileException;
use SP\Util\Filter;

defined('APP_ROOT') || die();

/**
 * Class AccountSearchService para la gestión de búsquedas de cuentas
 */
final class AccountSearchService extends Service implements AccountSearchServiceInterface
{
    private const COLORS_CACHE_FILE = CACHE_PATH.DIRECTORY_SEPARATOR.'colors.cache';

    /**
     * Colores para resaltar las cuentas
     */
    private const COLORS = [
        '2196F3',
        '03A9F4',
        '00BCD4',
        '009688',
        '4CAF50',
        '8BC34A',
        'CDDC39',
        'FFC107',
        '795548',
        '607D8B',
        '9E9E9E',
        'FF5722',
        'F44336',
        'E91E63',
        '9C27B0',
        '673AB7',
        '3F51B5',
    ];
    private AccountAclServiceInterface            $accountAclService;
    private ConfigDataInterface                   $configData;
    private AccountToTagRepositoryInterface       $accountToTagRepository;
    private AccountToUserRepositoryInterface      $accountToUserRepository;
    private AccountToUserGroupRepositoryInterface $accountToUserGroupRepository;
    private AccountToFavoriteServiceInterface     $accountToFavoriteService;
    private UserServiceInterface                  $userService;
    private UserGroupServiceInterface             $userGroupService;
    private FileCacheInterface                    $colorCache;
    private AccountSearchRepositoryInterface      $accountSearchRepository;
    private ?array                                $accountColor   = null;
    private ?string                               $cleanString    = null;
    private ?string                               $filterOperator = null;

    public function __construct(
        Application $application,
        AccountAclServiceInterface $accountAclService,
        AccountToTagRepositoryInterface $accountToTagRepository,
        AccountToUserRepositoryInterface $accountToUserRepository,
        AccountToUserGroupRepositoryInterface $accountToUserGroupRepository,
        AccountToFavoriteServiceInterface $accountToFavoriteService,
        UserServiceInterface $userService,
        UserGroupServiceInterface $userGroupService,
        AccountSearchRepositoryInterface $accountSearchRepository,
    ) {
        parent::__construct($application);
        $this->accountAclService = $accountAclService;
        $this->userService = $userService;
        $this->userGroupService = $userGroupService;
        $this->accountToFavoriteService = $accountToFavoriteService;
        $this->accountToTagRepository = $accountToTagRepository;
        $this->accountToUserRepository = $accountToUserRepository;
        $this->accountToUserGroupRepository = $accountToUserGroupRepository;
        $this->accountSearchRepository = $accountSearchRepository;

        // TODO: use IoC
        $this->colorCache = new FileCache(self::COLORS_CACHE_FILE);
        $this->configData = $this->config->getConfigData();

        $this->loadColors();

    }

    /**
     * Load colors from cache
     */
    private function loadColors(): void
    {
        try {
            $this->accountColor = $this->colorCache->load();

            logger('Loaded accounts color cache');
        } catch (FileException $e) {
            processException($e);
        }
    }

    /**
     * Procesar los resultados de la búsqueda y crear la variable que contiene los datos de cada cuenta
     * a mostrar.
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter): QueryResult
    {
        if (!empty($accountSearchFilter->getTxtSearch())) {
            $this->analyzeQueryFilters($accountSearchFilter->getTxtSearch());
        }

        if ($this->filterOperator !== null || $accountSearchFilter->getFilterOperator() === null) {
            $accountSearchFilter->setFilterOperator($this->filterOperator);
        }

        if (!empty($this->cleanString)) {
            $accountSearchFilter->setCleanTxtSearch($this->cleanString);
        }

        $queryResult = $this->accountSearchRepository->getByFilter($accountSearchFilter);

        return QueryResult::fromResults($this->buildAccountsData($queryResult), $queryResult->getTotalNumRows());
    }

    /**
     * Analizar la cadena de consulta por eqituetas especiales y devolver un objeto
     * QueryCondition con los filtros
     */
    public function analyzeQueryFilters(string $string): void
    {
        $tokenizer = new AccountSearchTokenizer();
        $tokens = $tokenizer->tokenizeFrom($string);

        $this->cleanString = $tokens->getSearch();
        $this->filterOperator = $tokens->getOperator();

        $this->processFilterItems($tokens->getItems());
        $this->processFilterConditions($tokens->getConditions());
    }

    private function processFilterItems(array $filters): void
    {
        foreach ($filters as $filter => $text) {
            try {
                switch ($filter) {
                    case AccountSearchConstants::FILTER_USER_NAME:
                        $userData = $this->userService->getByLogin(Filter::safeSearchString($text));

                        $this->accountSearchRepository->withFilterForUser(
                            $userData->getId(),
                            $userData->getUserGroupId()
                        );
                        break;
                    case AccountSearchConstants::FILTER_OWNER:
                        $this->accountSearchRepository->withFilterForOwner($text);
                        break;
                    case AccountSearchConstants::FILTER_GROUP_NAME:
                        $userGroupData = $this->userGroupService->getByName(Filter::safeSearchString($text));

                        $this->accountSearchRepository->withFilterForGroup($userGroupData->getId());
                        break;
                    case AccountSearchConstants::FILTER_MAIN_GROUP:
                        $this->accountSearchRepository->withFilterForMainGroup($text);
                        break;
                    case AccountSearchConstants::FILTER_FILE_NAME:
                        $this->accountSearchRepository->withFilterForFile($text);
                        break;
                    case AccountSearchConstants::FILTER_ACCOUNT_ID:
                        $this->accountSearchRepository->withFilterForAccountId((int)$text);
                        break;
                    case AccountSearchConstants::FILTER_CLIENT_NAME:
                        $this->accountSearchRepository->withFilterForClient($text);
                        break;
                    case AccountSearchConstants::FILTER_CATEGORY_NAME:
                        $this->accountSearchRepository->withFilterForCategory($text);
                        break;
                    case AccountSearchConstants::FILTER_ACCOUNT_NAME_REGEX:
                        $this->accountSearchRepository->withFilterForAccountNameRegex($text);
                        break;
                }
            } catch (Exception $e) {
                processException($e);
            }
        }
    }

    private function processFilterConditions(array $filters,): void
    {
        foreach ($filters as $filter) {
            switch ($filter) {
                case AccountSearchConstants::FILTER_IS_EXPIRED:
                    $this->accountSearchRepository->withFilterForIsExpired();
                    break;
                case AccountSearchConstants::FILTER_NOT_EXPIRED:
                    $this->accountSearchRepository->withFilterForIsNotExpired();
                    break;
                case AccountSearchConstants::FILTER_IS_PRIVATE:
                    $this->accountSearchRepository->withFilterForIsPrivate(
                        $this->context->getUserData()->getId(),
                        $this->context->getUserData()->getUserGroupId()
                    );
                    break;
                case AccountSearchConstants::FILTER_NOT_PRIVATE:
                    $this->accountSearchRepository->withFilterForIsNotPrivate();
                    break;
            }
        }
    }

    /**
     * @param  \SP\Infrastructure\Database\QueryResult  $queryResult
     *
     * @return array
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    private function buildAccountsData(QueryResult $queryResult): array
    {
        $maxTextLength = $this->configData->isResultsAsCards() ? 40 : 60;
        $accountLinkEnabled = $this->context->getUserData()->getPreferences()->isAccountLink()
                              || $this->configData->isAccountLink();
        $favorites = $this->accountToFavoriteService->getForUserId($this->context->getUserData()->getId());
        $accountsData = [];

        /** @var AccountSearchVData $accountSearchData */
        foreach ($queryResult->getDataAsArray() as $accountSearchData) {
            $cache = $this->getCacheForAccount($accountSearchData);

            // Obtener la ACL de la cuenta
            $accountAcl = $this->accountAclService->getAcl(
                ActionsInterface::ACCOUNT_SEARCH,
                AccountAclDto::makeFromAccountSearch(
                    $accountSearchData,
                    $cache->getUsers(),
                    $cache->getUserGroups()
                )
            );

            // Propiedades de búsqueda de cada cuenta
            $accountsSearchItem = new AccountSearchItem(
                $accountSearchData,
                $accountAcl,
                $this->configData
            );

            if (!$accountSearchData->getIsPrivate()) {
                $accountsSearchItem->setUsers($cache->getUsers());
                $accountsSearchItem->setUserGroups($cache->getUserGroups());
            }

            $accountsSearchItem->setTags(
                $this->accountToTagRepository
                    ->getTagsByAccountId($accountSearchData->getId())
                    ->getDataAsArray()
            );
            $accountsSearchItem->setTextMaxLength($maxTextLength);
            $accountsSearchItem->setColor(
                $this->pickAccountColor($accountSearchData->getClientId())
            );
            $accountsSearchItem->setLink($accountLinkEnabled);
            $accountsSearchItem->setFavorite(
                isset($favorites[$accountSearchData->getId()])
            );

            $accountsData[] = $accountsSearchItem;
        }

        return $accountsData;
    }

    /**
     * Devolver los accesos desde la caché
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function getCacheForAccount(AccountSearchVData $accountSearchData): AccountCache
    {
        $accountId = $accountSearchData->getId();

        /** @var AccountCache[] $cache */
        $cache = $this->context->getAccountsCache();

        $hasCache = $cache !== null;

        if ($hasCache === false
            || !isset($cache[$accountId])
            || $cache[$accountId]->getTime() < (int)strtotime($accountSearchData->getDateEdit())
        ) {
            $cache[$accountId] = new AccountCache(
                $accountId,
                $this->accountToUserRepository->getUsersByAccountId($accountId)->getDataAsArray(),
                $this->accountToUserGroupRepository->getUserGroupsByAccountId($accountId)->getDataAsArray()
            );

            if ($hasCache) {
                $this->context->setAccountsCache($cache);
            }
        }

        return $cache[$accountId];
    }

    /**
     * Seleccionar un color para la cuenta
     *
     * @param  int  $id  El id del elemento a asignar
     */
    private function pickAccountColor(int $id): string
    {
        if ($this->accountColor !== null
            && isset($this->accountColor[$id])) {
            return $this->accountColor[$id];
        }

        // Se asigna el color de forma aleatoria a cada id
        $this->accountColor[$id] = '#'.self::COLORS[array_rand(self::COLORS)];

        try {
            $this->colorCache->save($this->accountColor);

            logger('Saved accounts color cache');

            return $this->accountColor[$id];
        } catch (FileException $e) {
            processException($e);

            return '';
        }
    }

    public function getCleanString(): ?string
    {
        return $this->cleanString;
    }
}
