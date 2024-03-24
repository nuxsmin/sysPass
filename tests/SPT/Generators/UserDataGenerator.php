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

namespace SPT\Generators;

use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserPreferences;

/**
 * Class UserDataGenerator
 */
final class UserDataGenerator extends DataGenerator
{
    public function buildUserData(): User
    {
        return new User($this->getUserProperties());
    }

    /**
     * @return array
     */
    private function getUserProperties(): array
    {
        return [
            'id' => $this->faker->randomNumber(3),
            'name' => $this->faker->name(),
            'email' => $this->faker->randomNumber(3),
            'login' => $this->faker->name(),
            'ssoLogin' => $this->faker->userName(),
            'notes' => $this->faker->text(),
            'userGroupId' => $this->faker->randomNumber(3),
            'userProfileId' => $this->faker->randomNumber(3),
            'isAdminApp' => $this->faker->boolean(),
            'isAdminAcc' => $this->faker->boolean(),
            'isDisabled' => $this->faker->boolean(),
            'isChangePass' => $this->faker->boolean(),
            'isChangedPass' => $this->faker->boolean(),
            'isLdap' => $this->faker->boolean(),
            'isMigrate' => $this->faker->boolean(),
            'loginCount' => $this->faker->randomNumber(3),
            'lastLogin' => $this->faker->unixTime(),
            'lastUpdate' => $this->faker->unixTime(),
            'preferences' => serialize($this->buildUserPreferencesData()),
            'pass' => $this->faker->password(),
            'hashSalt' => $this->faker->sha1(),
            'mPass' => $this->faker->sha1(),
            'mKey' => $this->faker->sha1(),
            'lastUpdateMPass' => $this->faker->dateTime()->getTimestamp(),
        ];
    }

    public function buildUserPreferencesData(): UserPreferences
    {
        return new UserPreferences($this->getUserPreferencesProperties());
    }

    private function getUserPreferencesProperties(): array
    {
        return [
            'lang' => $this->faker->languageCode(),
            'theme' => $this->faker->colorName(),
            'resultsPerPage' => $this->faker->randomNumber(3),
            'accountLink' => $this->faker->boolean(),
            'sortViews' => $this->faker->boolean(),
            'topNavbar' => $this->faker->boolean(),
            'optionalActions' => $this->faker->boolean(),
            'resultsAsCards' => $this->faker->boolean(),
            'checkNotifications' => $this->faker->boolean(),
            'showAccountSearchFilters' => $this->faker->boolean(),
            'user_id' => $this->faker->randomNumber(3),
        ];
    }
}
