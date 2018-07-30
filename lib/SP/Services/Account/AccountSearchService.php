<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Services\Account;

use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
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
use SP\Storage\File\FileException;

defined('APP_ROOT') || die();

/**
 * Class AccountSearchService para la gestión de búsquedas de cuentas
 */
final class AccountSearchService extends Service
{
    /**
     * Regex filters for special searching
     */
    const FILTERS_REGEX_IS = '#(?<filter>(?:is|not):(?:expired|private))#';
    const FILTERS_REGEX = '#(?<type>id|user|group|file|owner|maingroup):(?:"(?<filter_quoted>[\w\s\.]+)"|(?<filter>[\w\.]+))#';
    const FILTERS_REGEX_OPERATOR = '#op:(?<operator>and|or)#';

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
     * @var FileCache
     */
    private $fileCache;
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function processSearchResults(AccountSearchFilter $accountSearchFilter)
    {
        $accountSearchFilter->setStringFilters($this->analyzeQueryFilters($accountSearchFilter->getTxtSearch()));

        if ($accountSearchFilter->getFilterOperator() === null) {
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function analyzeQueryFilters($string)
    {
        $this->cleanString = $string;

        $queryCondition = new QueryCondition();

        // Do not search for special parameters if there isn't any ":" within the string
        if (strpos($string, ':') === false) {
            return $queryCondition;
        }

        $this->extractFilterOperator($string);
        $this->extractFilterIs($string, $queryCondition);
        $this->extractFilterItems($string, $queryCondition);

        return $queryCondition;
    }

    /**
     * @param $string
     */
    private function extractFilterOperator($string)
    {
        if (preg_match(self::FILTERS_REGEX_OPERATOR, $string, $matches)) {
            // Removes the operator from the string to increase regex performance
            $this->cleanString = trim(str_replace($matches[0], '', $this->cleanString));

            switch ($matches['operator']) {
                case 'and':
                    $this->filterOperator = QueryCondition::CONDITION_AND;
                    break;
                case 'or':
                    $this->filterOperator = QueryCondition::CONDITION_OR;
                    break;
            }
        }
    }

    /**
     * @param string         $string
     * @param QueryCondition $queryCondition
     */
    private function extractFilterIs($string, QueryCondition $queryCondition)
    {
        if (preg_match_all(self::FILTERS_REGEX_IS, $string, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $filter) {
                // Removes the current filter from the string to increase regex performance
                $this->cleanString = trim(str_replace($filter['filter'], '', $this->cleanString));

                switch ($filter['filter']) {
                    case 'is:expired':
                        $queryCondition->addFilter('Account.passDateChange > 0 AND UNIX_TIMESTAMP() > Account.passDateChange', []);
                        break;
                    case 'not:expired':
                        $queryCondition->addFilter('Account.passDateChange = 0 OR UNIX_TIMESTAMP() < Account.passDateChange', []);
                        break;
                    case 'is:private':
                        $queryCondition->addFilter('(Account.isPrivate = 1 AND Account.userId = ?) OR (Account.isPrivateGroup = 1 AND Account.userGroupId = ?)', [$this->context->getUserData()->getId(), $this->context->getUserData()->getUserGroupId()]);
                        break;
                    case 'not:private':
                        $queryCondition->addFilter('Account.isPrivate = 0 AND Account.isPrivateGroup = 0');
                        break;
                }
            }
        }
    }

    /**
     * @param string         $string
     * @param QueryCondition $queryCondition
     */
    private function extractFilterItems($string, QueryCondition $queryCondition)
    {
        if (preg_match_all(self::FILTERS_REGEX, $string, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $filter) {
                // Removes the current filter from the string to increase regex performance
                $this->cleanString = trim(str_replace($filter[0], '', $this->cleanString));

                $text = !empty($filter['filter_quoted']) ? $filter['filter_quoted'] : $filter['filter'];

                if ($text !== '') {
                    try {
                        switch ($filter['type']) {
                            case 'user':
                                if (is_object(($userData = $this->dic->get(UserService::class)->getByLogin($text)))) {
                                    $queryCondition->addFilter(
                                        'Account.userId = ? OR Account.id IN 
                                        (SELECT AccountToUser.accountId FROM AccountToUser WHERE AccountToUser.accountId = Account.id AND AccountToUser.userId = ? 
                                        UNION ALL 
                                        SELECT AccountToUserGroup.accountId FROM AccountToUserGroup WHERE AccountToUserGroup.accountId = Account.id AND AccountToUserGroup.userGroupId = ?)',
                                        [$userData->getId(), $userData->getId(), $userData->getUserGroupId()]);
                                }
                                break;
                            case 'owner':
                                $queryCondition->addFilter('Account.userLogin LIKE ?', ['%' . $text . '%']);
                                break;
                            case 'group':
                                if (is_object(($userGroupData = $this->dic->get(UserGroupService::class)->getByName($text)))) {
                                    $queryCondition->addFilter(
                                        'Account.userGroupId = ? OR Account.id IN (SELECT AccountToUserGroup.accountId FROM AccountToUserGroup WHERE AccountToUserGroup.accountId = id AND AccountToUserGroup.userGroupId = ?)',
                                        [$userGroupData->getId(), $userGroupData->getId()]);
                                }
                                break;
                            case 'maingroup':
                                $queryCondition->addFilter('Account.userGroupName LIKE ?', ['%' . $text . '%']);
                                break;
                            case 'file':
                                $queryCondition->addFilter('Account.id IN (SELECT AccountFile.accountId FROM AccountFile WHERE AccountFile.name LIKE ?)', ['%' . $text . '%']);
                                break;
                            case 'id':
                                $queryCondition->addFilter('Account.id = ?', [(int)$text]);
                                break;
                        }

                    } catch (\Exception $e) {
                        processException($e);
                    }
                }
            }
        }
    }

    /**
     * Devolver los accesos desde la caché
     *
     * @param AccountSearchVData $accountSearchData
     *
     * @return AccountCache
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
                $this->accountToUserRepository->getUsersByAccountId($accountId),
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
            $this->fileCache->save(self::COLORS_CACHE_FILE, $this->accountColor);

            logger('Saved accounts color cache');

            return $this->accountColor[$id];
        } catch (FileException $e) {
            processException($e);

            return '';
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->accountRepository = $this->dic->get(AccountRepository::class);
        $this->accountToTagRepository = $this->dic->get(AccountToTagRepository::class);
        $this->accountToUserRepository = $this->dic->get(AccountToUserRepository::class);
        $this->accountToUserGroupRepository = $this->dic->get(AccountToUserGroupRepository::class);
        $this->fileCache = $this->dic->get(FileCache::class);
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
            $this->accountColor = $this->fileCache->load(self::COLORS_CACHE_FILE);

            logger('Loaded accounts color cache');
        } catch (FileException $e) {
            processException($e);
        }
    }
}