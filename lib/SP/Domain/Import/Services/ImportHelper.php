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

namespace SP\Domain\Import\Services;

use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Tag\Ports\TagServiceInterface;

/**
 * A helper class to provide the needed services.
 */
final class ImportHelper
{

    public function __construct(
        private readonly AccountService      $accountService,
        private readonly CategoryService     $categoryService,
        private readonly ClientService       $clientService,
        private readonly TagServiceInterface $tagService,
        private readonly ConfigService       $configService
    ) {
    }

    public function getAccountService(): AccountService
    {
        return $this->accountService;
    }

    public function getCategoryService(): CategoryService
    {
        return $this->categoryService;
    }

    public function getClientService(): ClientService
    {
        return $this->clientService;
    }

    public function getTagService(): TagServiceInterface
    {
        return $this->tagService;
    }

    public function getConfigService(): ConfigService
    {
        return $this->configService;
    }
}
