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
use SP\Domain\Client\Ports\ClientServiceInterface;
use SP\Domain\Tag\Ports\TagServiceInterface;

/**
 * A helper class to provide the needed services.
 */
final class ImportHelper
{
    private AccountService         $accountService;
    private CategoryService        $categoryService;
    private ClientServiceInterface $clientService;
    private TagServiceInterface      $tagService;

    public function __construct(
        AccountService         $accountService,
        CategoryService $categoryService,
        ClientServiceInterface $clientService,
        TagServiceInterface    $tagService
    ) {
        $this->accountService = $accountService;
        $this->categoryService = $categoryService;
        $this->clientService = $clientService;
        $this->tagService = $tagService;
    }

    public function getAccountService(): AccountService
    {
        return $this->accountService;
    }

    public function getCategoryService(): CategoryService
    {
        return $this->categoryService;
    }

    public function getClientService(): ClientServiceInterface
    {
        return $this->clientService;
    }

    public function getTagService(): TagServiceInterface
    {
        return $this->tagService;
    }
}
