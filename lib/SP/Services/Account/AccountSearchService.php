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

use SP\Account\AccountSearchFilter;
use SP\Account\AccountSearchItem;
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
use SP\Storage\FileCache;
use SP\Storage\FileException;

defined('APP_ROOT') || die();

/**
 * Class AccountSearchService para la gestión de búsquedas de cuentas
 */
class AccountSearchService extends Service
{
    /**
     * Regex filters for special searching
     */
    const FILTERS_REGEX_IS = '#(?<filter>(?:is|not):(?:expired|private))#';
    const FILTERS_REGEX = '#(?<type>id|user|group|file|owner|maingroup):"?(?<filter>[\w\.]+)"?#';
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
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function processSearchResults(AccountSearchFilter $accountSearchFilter)
    {
        $accountSearchFilter->setStringFilters($this->analyzeQueryFilters($accountSearchFilter->getTxtSearch()));
        $accountSearchFilter->setFilterOperator($this->filterOperator);
        $accountSearchFilter->setCleanTxtSearch($this->cleanString);

        $accountSearchResponse = $this->accountRepository->getByFilter($accountSearchFilter);

        // Variables de configuración
        $maxTextLength = $this->configData->isResultsAsCards() ? 40 : 60;

        $accountLinkEnabled = $this->context->getUserData()->getPreferences()->isAccountLink() || $this->configData->isAccountLink();
        $favorites = $this->dic->get(AccountFavoriteService::class)->getForUserId($this->context->getUserData()->getId());

        $accountAclService = $this->dic->get(AccountAclService::class);

        foreach ($accountSearchResponse->getData() as $accountSearchData) {
            $cache = $this->getCacheForAccount($accountSearchData);

            $accountAclDto = new AccountAclDto();
            $accountAclDto->setAccountId($accountSearchData->getId());
            $accountAclDto->setDateEdit(strtotime($accountSearchData->getDateEdit()));
            $accountAclDto->setUserId($accountSearchData->getUserId());
            $accountAclDto->setUserGroupId($accountSearchData->getUserGroupId());
            $accountAclDto->setUsersId($cache->getUsers());
            $accountAclDto->setUserGroupsId($cache->getUserGroups());

            // Obtener la ACL de la cuenta
            $accountAcl = $accountAclService->getAcl(Acl::ACCOUNT_SEARCH, $accountAclDto);

            // Propiedades de búsqueda de cada cuenta
            $accountsSearchItem = new AccountSearchItem($accountSearchData, $accountAcl, $this->configData);

            if (!$accountSearchData->getIsPrivate()) {
                $accountsSearchItem->setUsers($cache->getUsers());
                $accountsSearchItem->setUserGroups($cache->getUserGroups());
            }

            $accountsSearchItem->setTags($this->accountToTagRepository->getTagsByAccountId($accountSearchData->getId()));
            $accountsSearchItem->setTextMaxLength($maxTextLength);
            $accountsSearchItem->setColor($this->pickAccountColor($accountSearchData->getClientId()));
            $accountsSearchItem->setLink($accountLinkEnabled);
            $accountsSearchItem->setFavorite(isset($favorites[$accountSearchData->getId()]));

            $accountsData[] = $accountsSearchItem;
        }

        $accountsData['count'] = $accountSearchResponse->getCount();

        return $accountsData;
    }

    /**
     * Analizar la cadena de consulta por eqituetas especiales y devolver un objeto
     * QueryCondition con los filtros
     *
     * @param $string
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

        if (preg_match_all(self::FILTERS_REGEX, $string, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $filter) {
                // Removes the current filter from the string to increase regex performance
                $this->cleanString = trim(str_replace($filter[0], '', $this->cleanString));

                if (($text = $filter['filter']) !== '') {
                    try {

                        switch ($filter['type']) {
                            case 'user':
                                if (is_object(($userData = $this->dic->get(UserService::class)->getByLogin($text)))) {
                                    $queryCondition->addFilter(
                                        'Account.userId = ? OR Account.id IN (SELECT AU.accountId FROM AccountToUser AU WHERE AU.accountId = Account.id AND AU.userId = ? 
                                        UNION ALL SELECT AUG.accountId FROM AccountToUserGroup AUG WHERE AUG.accountId = Account.id AND AUG.userGroupId = ?)',
                                        [$userData->getId(), $userData->getId(), $userData->getUserGroupId()]);
                                }
                                break;
                            case 'owner':
                                $queryCondition->addFilter('Account.userLogin LIKE ?', ['%' . $text . '%']);
                                break;
                            case 'group':
                                if (is_object(($userGroupData = $this->dic->get(UserGroupService::class)->getByName($text)))) {
                                    $queryCondition->addFilter(
                                        'Account.userGroupId = ? OR Account.id IN (SELECT AUG.accountId FROM AccountToUserGroup AUG WHERE AUG.accountId = id AND AUG.userGroupId = ?)',
                                        [$userGroupData->getId(), $userGroupData->getId()]);
                                }
                                break;
                            case 'maingroup':
                                $queryCondition->addFilter('Account.userGroupName = ?', ['%' . $text . '%']);
                                break;
                            case 'file':
                                $queryCondition->addFilter('Account.id IN (SELECT AF.accountId FROM AccountFile AF WHERE AF.name LIKE ?)', ['%' . $text . '%']);
                                break;
                            case 'id':
                                $queryCondition->addFilter('Account.id = ?', [(int)$text]);
                                break;
                        }

                    } catch (\Exception $e) {
                    }
                }
            }
        }

        return $queryCondition;
    }

    /**
     * Devolver los accesos desde la caché
     *
     * @param AccountSearchVData $accountSearchData
     * @return AccountCache
     */
    protected function getCacheForAccount(AccountSearchVData $accountSearchData)
    {
        $accountId = $accountSearchData->getId();

        /** @var AccountCache[] $cache */
        $cache = $this->context->getAccountsCache();

        if (!isset($cache[$accountId])
            || $cache[$accountId]->getTime() < (int)strtotime($accountSearchData->getDateEdit())
        ) {
            $cache[$accountId] = new AccountCache(
                $accountId,
                $this->accountToUserRepository->getUsersByAccountId($accountId),
                $this->accountToUserGroupRepository->getUserGroupsByAccountId($accountId));

            $this->context->setAccountsCache($cache);
        }

        return $cache[$accountId];
    }

    /**
     * Seleccionar un color para la cuenta
     *
     * @param int $id El id del elemento a asignar
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

            debugLog('Saved accounts color cache');

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

            debugLog('Loaded accounts color cache');
        } catch (FileException $e) {
            processException($e);
        }
    }
}