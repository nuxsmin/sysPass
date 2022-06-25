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

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountSearchVData;
use SP\DataModel\Dto\AccountAclDto;
use SP\DataModel\Dto\AccountCache;
use SP\Mvc\Model\QueryCondition;
use SP\Repositories\Account\AccountRepository;
use SP\Repositories\Account\AccountToTagRepository;
use SP\Repositories\Account\AccountToUserGroupRepository;
use SP\Repositories\Account\AccountToUserRepository;
use SP\Services\Service;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Storage\Database\QueryResult;
use SP\Storage\File\FileCache;
use SP\Storage\File\FileCacheInterface;
use SP\Storage\File\FileException;
use SP\Util\Filter;

defined('APP_ROOT') || die();

/**
 * Class AccountSearchService para la gestión de búsquedas de cuentas
 */
final class AccountSearchService extends Service
{
    /**
     * Regex filters for special searching
     */
    const FILTERS = [
        'condition' => [
            'subject' => ['is', 'not'],
            'condition' => ['expired', 'private']
        ],
        'items' => [
            'subject' => ['id', 'user', 'group', 'file', 'owner', 'maingroup', 'client', 'category', 'name_regex'],
            'condition' => null
        ],
        'operator' => [
            'subject' => ['op'],
            'condition' => ['and', 'or']
        ]
    ];

    const COLORS_CACHE_FILE = CACHE_PATH . DIRECTORY_SEPARATOR . 'colors.cache';

    /**
     * Cache expire time
     */
    const CACHE_EXPIRE = 86400;

    /**
     * Colores para resaltar las cuentas
     */
    const COLORS = [
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
    /**
     * @var AccountFilterUser
     */
    private $accountFilterUser;
    /**
     * @var AccountAclService
     */
    private $accountAclService;
    /**
     * @var ConfigData
     */
    private $configData;
    /**
     * @var AccountToTagRepository
     */
    private $accountToTagRepository;
    /**
     * @var AccountToUserRepository
     */
    private $accountToUserRepository;
    /**
     * @var AccountToUserGroupRepository
     */
    private $accountToUserGroupRepository;
    /**
     * @var FileCacheInterface
     */
    private $colorCache;
    /**
     * @var array
     */
    private $accountColor;
    /**
     * @var AccountRepository
     */
    private $accountRepository;
    /**
     * @var string
     */
    private $cleanString;
    /**
     * @var string
     */
    private $filterOperator;

    /**
     * Procesar los resultados de la búsqueda y crear la variable que contiene los datos de cada cuenta
     * a mostrar.
     *
     * @param AccountSearchFilter $accountSearchFilter
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function processSearchResults(AccountSearchFilter $accountSearchFilter)
    {
        $accountSearchFilter->setStringFilters($this->analyzeQueryFilters($accountSearchFilter->getTxtSearch()));

        if ($accountSearchFilter->getFilterOperator() === null
            || $this->filterOperator !== null
        ) {
            $accountSearchFilter->setFilterOperator($this->filterOperator);
        }

        $accountSearchFilter->setCleanTxtSearch($this->cleanString);

        $queryResult = $this->accountRepository->getByFilter($accountSearchFilter, $this->accountFilterUser->getFilter($accountSearchFilter->getGlobalSearch()));

        // Variables de configuración
        $maxTextLength = $this->configData->isResultsAsCards() ? 40 : 60;

        $accountLinkEnabled = $this->context->getUserData()->getPreferences()->isAccountLink() || $this->configData->isAccountLink();
        $favorites = $this->dic->get(AccountToFavoriteService::class)->getForUserId($this->context->getUserData()->getId());

        $accountsData = [];

        /** @var AccountSearchVData $accountSearchData */
        foreach ($queryResult->getDataAsArray() as $accountSearchData) {
            $cache = $this->getCacheForAccount($accountSearchData);

            // Obtener la ACL de la cuenta
            $accountAcl = $this->accountAclService->getAcl(
                Acl::ACCOUNT_SEARCH,
                AccountAclDto::makeFromAccountSearch($accountSearchData, $cache->getUsers(), $cache->getUserGroups())
            );

            // Propiedades de búsqueda de cada cuenta
            $accountsSearchItem = new AccountSearchItem($accountSearchData, $accountAcl, $this->configData);

            if (!$accountSearchData->getIsPrivate()) {
                $accountsSearchItem->setUsers($cache->getUsers());
                $accountsSearchItem->setUserGroups($cache->getUserGroups());
            }

            $accountsSearchItem->setTags($this->accountToTagRepository->getTagsByAccountId($accountSearchData->getId())->getDataAsArray());
            $accountsSearchItem->setTextMaxLength($maxTextLength);
            $accountsSearchItem->setColor($this->pickAccountColor($accountSearchData->getClientId()));
            $accountsSearchItem->setLink($accountLinkEnabled);
            $accountsSearchItem->setFavorite(isset($favorites[$accountSearchData->getId()]));

            $accountsData[] = $accountsSearchItem;
        }

        return QueryResult::fromResults($accountsData, $queryResult->getTotalNumRows());
    }

    /**
     * Analizar la cadena de consulta por eqituetas especiales y devolver un objeto
     * QueryCondition con los filtros
     *
     * @param $string
     *
     * @return QueryCondition
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function analyzeQueryFilters($string)
    {
        $this->cleanString = null;
        $this->filterOperator = null;

        $queryCondition = new QueryCondition();

        $match = preg_match_all(
            '/(?<search>(?<!:)\b[^:]+\b(?!:))|(?<filter_subject>[a-zа-я_]+):(?!\s]*)"?(?<filter_condition>[^":]+)"?/u',
            $string,
            $filters
        );

        if ($match !== false && $match > 0) {
            if (!empty($filters['search'][0])) {
                $this->cleanString = Filter::safeSearchString(trim($filters['search'][0]));
            }

            $filtersAndValues = array_filter(
                array_combine(
                    $filters['filter_subject'],
                    $filters['filter_condition']
                )
            );

            if (!empty($filtersAndValues)) {
                $filtersItem = array_filter($filtersAndValues, function ($value, $key) {
                    return in_array($key, self::FILTERS['items']['subject'], true)
                        && $value !== '';
                }, ARRAY_FILTER_USE_BOTH);

                if (!empty($filtersItem)) {
                    $this->processFilterItems($filtersItem, $queryCondition);
                }

                $filtersOperator = array_filter($filtersAndValues, function ($value, $key) {
                    return in_array($key, self::FILTERS['operator']['subject'], true)
                        && in_array($value, self::FILTERS['operator']['condition'], true);
                }, ARRAY_FILTER_USE_BOTH);

                if (!empty($filtersOperator)) {
                    $this->processFilterOperator($filtersOperator);
                }

                $filtersCondition = array_filter(array_map(function ($subject, $condition) {
                    if (in_array($subject, self::FILTERS['condition']['subject'], true)
                        && in_array($condition, self::FILTERS['condition']['condition'], true)
                    ) {
                        return $subject . ':' . $condition;
                    }

                    return null;
                }, $filters['filter_subject'], $filters['filter_condition']));

                if (!empty($filtersCondition)) {
                    $this->processFilterIs($filtersCondition, $queryCondition);
                }
            }
        }

        return $queryCondition;
    }

    /**
     * @param array          $filters
     * @param QueryCondition $queryCondition
     */
    private function processFilterItems(array $filters, QueryCondition $queryCondition)
    {
        foreach ($filters as $filter => $text) {
            try {
                switch ($filter) {
                    case 'user':
                        $userData = $this->dic->get(UserService::class)->getByLogin(Filter::safeSearchString($text));

                        if (is_object($userData)) {
                            $queryCondition->addFilter(
                                'Account.userId = ? OR Account.userGroupId = ? OR Account.id IN 
                                        (SELECT AccountToUser.accountId FROM AccountToUser WHERE AccountToUser.accountId = Account.id AND AccountToUser.userId = ? 
                                        UNION 
                                        SELECT AccountToUserGroup.accountId FROM AccountToUserGroup WHERE AccountToUserGroup.accountId = Account.id AND AccountToUserGroup.userGroupId = ?)',
                                [$userData->getId(), $userData->getUserGroupId(), $userData->getId(), $userData->getUserGroupId()]);
                        }
                        break;
                    case 'owner':
                        $text = '%' . Filter::safeSearchString($text) . '%';
                        $queryCondition->addFilter(
                            'Account.userLogin LIKE ? OR Account.userName LIKE ?',
                            [$text, $text]);
                        break;
                    case 'group':
                        $userGroupData = $this->dic->get(UserGroupService::class)->getByName(Filter::safeSearchString($text));

                        if (is_object($userGroupData)) {
                            $queryCondition->addFilter(
                                'Account.userGroupId = ? OR Account.id IN (SELECT AccountToUserGroup.accountId FROM AccountToUserGroup WHERE AccountToUserGroup.accountId = id AND AccountToUserGroup.userGroupId = ?)',
                                [$userGroupData->getId(), $userGroupData->getId()]);
                        }
                        break;
                    case 'maingroup':
                        $queryCondition->addFilter('Account.userGroupName LIKE ?', ['%' . Filter::safeSearchString($text) . '%']);
                        break;
                    case 'file':
                        $queryCondition->addFilter('Account.id IN (SELECT AccountFile.accountId FROM AccountFile WHERE AccountFile.name LIKE ?)', ['%' . $text . '%']);
                        break;
                    case 'id':
                        $queryCondition->addFilter('Account.id = ?', [(int)$text]);
                        break;
                    case 'client':
                        $queryCondition->addFilter('Account.clientName LIKE ?', ['%' . Filter::safeSearchString($text) . '%']);
                        break;
                    case 'category':
                        $queryCondition->addFilter('Account.categoryName LIKE ?', ['%' . Filter::safeSearchString($text) . '%']);
                        break;
                    case 'name_regex':
                        $queryCondition->addFilter('Account.name REGEXP ?', [$text]);
                        break;
                }
            } catch (Exception $e) {
                processException($e);
            }
        }
    }

    /**
     * @param array $filters
     */
    private function processFilterOperator(array $filters)
    {
        switch ($filters['op']) {
            case 'and':
                $this->filterOperator = QueryCondition::CONDITION_AND;
                break;
            case 'or':
                $this->filterOperator = QueryCondition::CONDITION_OR;
                break;
        }
    }

    /**
     * @param array          $filters
     * @param QueryCondition $queryCondition
     */
    private function processFilterIs(array $filters, QueryCondition $queryCondition)
    {
        foreach ($filters as $filter) {
            switch ($filter) {
                case 'is:expired':
                    $queryCondition->addFilter(
                        'Account.passDateChange > 0 AND UNIX_TIMESTAMP() > Account.passDateChange',
                        []);
                    break;
                case 'not:expired':
                    $queryCondition->addFilter(
                        'Account.passDateChange = 0 OR Account.passDateChange IS NULL OR UNIX_TIMESTAMP() < Account.passDateChange',
                        []);
                    break;
                case 'is:private':
                    $queryCondition->addFilter(
                        '(Account.isPrivate = 1 AND Account.userId = ?) OR (Account.isPrivateGroup = 1 AND Account.userGroupId = ?)',
                        [$this->context->getUserData()->getId(), $this->context->getUserData()->getUserGroupId()]);
                    break;
                case 'not:private':
                    $queryCondition->addFilter(
                        '(Account.isPrivate = 0 OR Account.isPrivate IS NULL) AND (Account.isPrivateGroup = 0 OR Account.isPrivateGroup IS NULL)');
                    break;
            }
        }
    }

    /**
     * Devolver los accesos desde la caché
     *
     * @param AccountSearchVData $accountSearchData
     *
     * @return AccountCache
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getCacheForAccount(AccountSearchVData $accountSearchData)
    {
        $accountId = $accountSearchData->getId();

        /** @var AccountCache[] $cache */
        $cache = $this->context->getAccountsCache();

        $hasCache = $cache !== null;

        if ($cache === false
            || !isset($cache[$accountId])
            || $cache[$accountId]->getTime() < (int)strtotime($accountSearchData->getDateEdit())
        ) {
            $cache[$accountId] = new AccountCache(
                $accountId,
                $this->accountToUserRepository->getUsersByAccountId($accountId)->getDataAsArray(),
                $this->accountToUserGroupRepository->getUserGroupsByAccountId($accountId)->getDataAsArray());

            if ($hasCache) {
                $this->context->setAccountsCache($cache);
            }
        }

        return $cache[$accountId];
    }

    /**
     * Seleccionar un color para la cuenta
     *
     * @param int $id El id del elemento a asignar
     *
     * @return string
     */
    private function pickAccountColor($id)
    {
        if ($this->accountColor !== null && isset($this->accountColor[$id])) {
            return $this->accountColor[$id];
        }

        // Se asigna el color de forma aleatoria a cada id
        $this->accountColor[$id] = '#' . self::COLORS[array_rand(self::COLORS)];

        try {
            $this->colorCache->save($this->accountColor);

            logger('Saved accounts color cache');

            return $this->accountColor[$id];
        } catch (FileException $e) {
            processException($e);

            return '';
        }
    }

    /**
     * @return string
     */
    public function getCleanString()
    {
        return $this->cleanString;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->accountRepository = $this->dic->get(AccountRepository::class);
        $this->accountToTagRepository = $this->dic->get(AccountToTagRepository::class);
        $this->accountToUserRepository = $this->dic->get(AccountToUserRepository::class);
        $this->accountToUserGroupRepository = $this->dic->get(AccountToUserGroupRepository::class);
        $this->colorCache = new FileCache(self::COLORS_CACHE_FILE);
        $this->accountAclService = $this->dic->get(AccountAclService::class);
        $this->accountFilterUser = $this->dic->get(AccountFilterUser::class);
        $this->configData = $this->config->getConfigData();

        $this->loadColors();
    }

    /**
     * Load colors from cache
     */
    private function loadColors()
    {
        try {
            $this->accountColor = $this->colorCache->load();

            logger('Loaded accounts color cache');
        } catch (FileException $e) {
            processException($e);
        }
    }
}