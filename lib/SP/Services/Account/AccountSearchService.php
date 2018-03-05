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
use SP\Repositories\Account\AccountRepository;
use SP\Repositories\Account\AccountToTagRepository;
use SP\Repositories\Account\AccountToUserGroupRepository;
use SP\Repositories\Account\AccountToUserRepository;
use SP\Services\Service;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;

defined('APP_ROOT') || die();

/**
 * Class AccountSearchService para la gestión de búsquedas de cuentas
 */
class AccountSearchService extends Service
{
    /**
     * Regex filter for special searching
     */
    const FILTERS_REGEX = '^(?:(?P<filter>user|group|file|owner|maingroup|expired|private):(?:"(?P<text>[\w\.]+)")?)$';

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
    protected $configData;
    /**
     * @var AccountToTagRepository
     */
    protected $accountToTagRepository;
    /**
     * @var AccountToUserRepository
     */
    protected $accountToUserRepository;
    /**
     * @var AccountToUserGroupRepository
     */
    protected $accountToUserGroupRepository;
    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * Procesar los resultados de la búsqueda y crear la variable que contiene los datos de cada cuenta
     * a mostrar.
     *
     * @param AccountSearchFilter $accountSearchFilter
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\SPException
     */
    public function processSearchResults(AccountSearchFilter $accountSearchFilter)
    {
        $accountSearchFilter->setStringFilters($this->analyzeQueryString($accountSearchFilter->getTxtSearch()));

        $accountSearchResponse = $this->accountRepository->getByFilter($accountSearchFilter);

        // Variables de configuración
        $maxTextLength = $this->configData->isResultsAsCards() ? 40 : 60;

        $accountLinkEnabled = $this->session->getUserData()->getPreferences()->isAccountLink() || $this->configData->isAccountLink();
        $favorites = $this->dic->get(AccountFavoriteService::class)->getForUserId($this->session->getUserData()->getId());

        $accountAclService = $this->dic->get(AccountAclService::class);

        foreach ($accountSearchResponse->getData() as $accountSearchData) {
            $cache = $this->getCacheForAccount($accountSearchData);

            $accountAclDto = new AccountAclDto();
            $accountAclDto->setAccountId($accountSearchData->getId());
            $accountAclDto->setDateEdit($accountSearchData->getDateEdit());
            $accountAclDto->setUserId($accountSearchData->getUserId());
            $accountAclDto->setUserGroupId($accountSearchData->getUserGroupId());
            $accountAclDto->setUsersId($cache->getUsers());
            $accountAclDto->setUserGroupsId($cache->getUserGroups());
            $accountAclDto->setOtherUserEdit($accountSearchData->getOtherUserEdit());
            $accountAclDto->setOtherUserGroupEdit($accountSearchData->getOtherUserGroupEdit());

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
     * Analizar la cadena de consulta por eqituetas especiales y devolver un array
     * con las columnas y los valores a buscar.
     *
     * @param $txt
     * @return array|bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\SPException
     */
    private function analyzeQueryString($txt)
    {
        if (!preg_match('/' . self::FILTERS_REGEX . '/i', $txt, $filters)) {
            return [];
        }

        $text = isset($filters['text']) && empty($filters['text']) === false ? $filters['text'] : false;

        switch ($filters['filter']) {
            case 'user':
                if ($text === false
                    || is_object(($userData = $this->dic->get(UserService::class)->getByLogin($text))) === false) {
                    return [];
                }

                return [
                    'type' => 'user',
                    'query' => 'A.userId = ? OR A.id IN (SELECT AU.accountId FROM AccountToUser AU WHERE AU.accountId = A.id AND AU.userId = ? UNION ALL SELECT AUG.accountId FROM AccountToUserGroup AUG WHERE AUG.accountId = A.id AND AUG.userGroupId = ?)',
                    'values' => [$userData->getId(), $userData->getId(), $userData->getUserGroupId()]
                ];
                break;
            case 'owner':
                if ($text === false) {
                    return [];
                }

                return [
                    'type' => 'user',
                    'query' => 'A.userLogin LIKE ?',
                    'values' => ['%' . $text . '%']
                ];
                break;
            case 'group':
                if ($text === false
                    || is_object(($userGroupData = $this->dic->get(UserGroupService::class)->getByName($text))) === false) {
                    return [];
                }

                return [
                    'type' => 'group',
                    'query' => 'A.userGroupId = ? OR A.id IN (SELECT AUG.accountId FROM AccountToUserGroup AUG WHERE AUG.accountId = id AND AUG.userGroupId = ?)',
                    'values' => [$userGroupData->getId(), $userGroupData->getId()]
                ];
                break;
            case 'maingroup':
                if ($text === false) {
                    return [];
                }

                return [
                    'type' => 'group',
                    'query' => 'A.userGroupName = ?',
                    'values' => ['%' . $text . '%']
                ];
                break;
            case 'file':
                return [
                    'type' => 'file',
                    'query' => 'A.id IN (SELECT AF.accountId FROM AccountFile AF WHERE AF.name LIKE ?)',
                    'values' => ['%' . $text . '%']
                ];
                break;
            case 'expired':
                return [
                    'type' => 'expired',
                    'query' => 'A.passDateChange > 0 AND UNIX_TIMESTAMP() > A.passDateChange',
                    'values' => []
                ];
                break;
            case 'private':
                return [
                    'type' => 'private',
                    'query' => '(A.isPrivate = 1 AND A.userId = ?) OR (A.isPrivateGroup = 1 AND A.userGroupId = ?)',
                    'values' => [$this->session->getUserData()->getId(), $this->session->getUserData()->getUserGroupId()]
                ];
                break;
            default:
                return [];
        }
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
        $cache =& $_SESSION['accountsCache'];

        if (!isset($cache[$accountId])
            || $cache[$accountId]->getTime() < (int)strtotime($accountSearchData->getDateEdit())
        ) {
            $cache[$accountId] = new AccountCache(
                $accountId,
                $this->accountToUserRepository->getUsersByAccountId($accountId),
                $this->accountToUserGroupRepository->getUserGroupsByAccountId($accountId));
        }

        return $cache[$accountId];
    }

    /**
     * Seleccionar un color para la cuenta
     *
     * @param int $id El id del elemento a asignar
     * @return mixed
     */
    private function pickAccountColor($id)
    {
        $accountColor = $this->session->getAccountColor();

        if (!is_array($accountColor)
            || !isset($accountColor[$id])
        ) {
            // Se asigna el color de forma aleatoria a cada id
            $color = array_rand(self::COLORS);

            $accountColor[$id] = '#' . self::COLORS[$color];
            $this->session->setAccountColor($accountColor);
        }

        return $accountColor[$id];
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
        $this->configData = $this->config->getConfigData();
    }
}