<?php
declare(strict_types=1);
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

namespace SP\Domain\User\Models;

use SP\Domain\Common\Models\Model;

/**
 * Class UserPreferences
 */
class UserPreferences extends Model
{
    protected ?string $lang                     = null;
    protected ?string $theme                    = null;
    protected int     $resultsPerPage           = 0;
    protected bool    $accountLink              = false;
    protected bool    $sortViews                = false;
    protected bool    $topNavbar                = false;
    protected bool    $optionalActions          = false;
    protected bool    $resultsAsCards           = false;
    protected bool    $checkNotifications       = true;
    protected bool    $showAccountSearchFilters = false;
    protected ?int    $user_id                  = null;

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function getResultsPerPage(): int
    {
        return $this->resultsPerPage;
    }

    public function isAccountLink(): bool
    {
        return $this->accountLink;
    }

    public function isSortViews(): bool
    {
        return $this->sortViews;
    }

    public function isTopNavbar(): bool
    {
        return $this->topNavbar;
    }

    public function isOptionalActions(): bool
    {
        return $this->optionalActions;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * unserialize() checks for the presence of a function with the magic name __wakeup.
     * If present, this function can reconstruct any resources that the object may have.
     * The intended use of __wakeup is to reestablish any database connections that may have been lost during
     * serialization and perform other reinitialization tasks.
     *
     * @return void
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
     */
    public function __wakeup()
    {
        // Para realizar la conversión de nombre de propiedades que empiezan por _
        foreach (get_object_vars($this) as $name => $value) {
            if (str_starts_with($name, '_')) {
                $newName = substr($name, 1);
                $this->$newName = $value;

                // Borrar la variable anterior
                unset($this->$name);
            }
        }
    }

    public function isResultsAsCards(): bool
    {
        return $this->resultsAsCards;
    }

    public function isCheckNotifications(): bool
    {
        return $this->checkNotifications;
    }

    public function isShowAccountSearchFilters(): bool
    {
        return $this->showAccountSearchFilters;
    }
}
