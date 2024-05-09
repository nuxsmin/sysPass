<?php
/**
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

declare(strict_types=1);
/**
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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use SP\Domain\Account\Adapters\AccountSearchItem;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountCacheService;
use SP\Domain\Account\Ports\AccountToFavoriteService;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;

use function SP\logger;
use function SP\processException;

/**
 * Class AccountSearchData
 */
final class AccountSearchData
{
    private const COLORS_CACHE_FILE = CACHE_PATH . DIRECTORY_SEPARATOR . 'colors.cache';
    private const COLORS             = [
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
    private const TEXT_LENGTH_CARDS  = 40;
    private const TEXT_LENGTH_NORMAL = 60;
    private ?array $accountColor = null;

    public function __construct(
        private readonly Context             $context,
        private readonly AccountAclService        $accountAclService,
        private readonly AccountToTagRepository   $accountToTagRepository,
        private readonly AccountToFavoriteService $accountToFavoriteService,
        private readonly AccountCacheService      $accountCacheService,
        private readonly FileCacheService         $fileCache,
        private readonly ConfigDataInterface $configData,
        private readonly UriContextInterface $uriContext,
    ) {
        $this->loadColors();
    }

    /**
     * Load colors from cache
     */
    private function loadColors(): void
    {
        try {
            $this->accountColor = $this->fileCache->load(self::COLORS_CACHE_FILE);

            logger('Loaded accounts color cache');
        } catch (FileException $e) {
            processException($e);
        }
    }

    /**
     * @param QueryResult<AccountSearchView> $queryResult
     *
     * @return QueryResult<AccountSearchItem>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function buildFrom(QueryResult $queryResult): QueryResult
    {
        $maxTextLength = $this->configData->isResultsAsCards() ? self::TEXT_LENGTH_CARDS : self::TEXT_LENGTH_NORMAL;
        $userPreferencesData = $this->context->getUserData()->getPreferences();

        $accountLinkEnabled = (null !== $userPreferencesData && $userPreferencesData->isAccountLink())
                              || $this->configData->isAccountLink();
        $favorites = $this->accountToFavoriteService->getForUserId($this->context->getUserData()->getId());

        return $queryResult->mutateWithCallback(
            function (AccountSearchView $accountSearchView) use (
                $maxTextLength,
                $accountLinkEnabled,
                $favorites
            ): AccountSearchItem {
                $cache = $this->accountCacheService->getCacheForAccount(
                    $accountSearchView->getId(),
                    strtotime($accountSearchView->getDateEdit())
                );

                // Obtener la ACL de la cuenta
                $accountAcl = $this->accountAclService->getAcl(
                    AclActionsInterface::ACCOUNT_SEARCH,
                    AccountAclDto::makeFromAccountSearch(
                        $accountSearchView,
                        $cache->getUsers(),
                        $cache->getUserGroups()
                    )
                );

                $tags = $this->accountToTagRepository
                    ->getTagsByAccountId($accountSearchView->getId())
                    ->getDataAsArray();

                $users = !$accountSearchView->getIsPrivate() ? $cache->getUsers() : null;
                $userGroups = !$accountSearchView->getIsPrivate() ? $cache->getUserGroups() : null;

                return new AccountSearchItem(
                    $accountSearchView,
                    $accountAcl,
                    $this->configData,
                    $this->uriContext,
                    $tags,
                    $maxTextLength,
                    isset($favorites[$accountSearchView->getId()]),
                    $users,
                    $userGroups,
                    $this->pickAccountColor($accountSearchView->getClientId()),
                    $accountLinkEnabled
                );
            }
        );
    }

    private function pickAccountColor(int $id): string
    {
        if ($this->accountColor !== null && isset($this->accountColor[$id])) {
            return $this->accountColor[$id];
        }

        // Se asigna el color de forma aleatoria a cada id
        $this->accountColor[$id] = '#' . self::COLORS[array_rand(self::COLORS)];

        try {
            $this->fileCache->save($this->accountColor, self::COLORS_CACHE_FILE);

            logger('Saved accounts color cache');

            return $this->accountColor[$id];
        } catch (FileException $e) {
            processException($e);

            return '';
        }
    }
}
