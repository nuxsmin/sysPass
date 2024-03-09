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

use SP\DataModel\ProfileData;
use SP\DataModel\UserProfile;

/**
 * Class UserProfileDataGenerator
 */
final class UserProfileDataGenerator extends DataGenerator
{
    public function buildUserProfileData(): UserProfile
    {
        return new UserProfile($this->getUserProfileProperties());
    }

    private function getUserProfileProperties(): array
    {
        return [
            'id' => $this->faker->randomNumber(3),
            'name'    => $this->faker->name,
            'profile' => $this->buildProfileData(),
        ];
    }

    public function buildProfileData(): ProfileData
    {
        return new ProfileData($this->getProfileProperties());
    }

    private function getProfileProperties(): array
    {
        return [
            'accView'          => $this->faker->boolean,
            'accViewPass'      => $this->faker->boolean,
            'accViewHistory'   => $this->faker->boolean,
            'accEdit'          => $this->faker->boolean,
            'accEditPass'      => $this->faker->boolean,
            'accAdd'           => $this->faker->boolean,
            'accDelete'        => $this->faker->boolean,
            'accFiles'         => $this->faker->boolean,
            'accPrivate'       => $this->faker->boolean,
            'accPrivateGroup'  => $this->faker->boolean,
            'accPermission'    => $this->faker->boolean,
            'accPublicLinks'   => $this->faker->boolean,
            'accGlobalSearch'  => $this->faker->boolean,
            'configGeneral'    => $this->faker->boolean,
            'configEncryption' => $this->faker->boolean,
            'configBackup'     => $this->faker->boolean,
            'configImport'     => $this->faker->boolean,
            'mgmUsers'         => $this->faker->boolean,
            'mgmGroups'        => $this->faker->boolean,
            'mgmProfiles'      => $this->faker->boolean,
            'mgmCategories'    => $this->faker->boolean,
            'mgmCustomers'     => $this->faker->boolean,
            'mgmApiTokens'     => $this->faker->boolean,
            'mgmPublicLinks'   => $this->faker->boolean,
            'mgmAccounts'      => $this->faker->boolean,
            'mgmTags'          => $this->faker->boolean,
            'mgmFiles'         => $this->faker->boolean,
            'mgmItemsPreset'   => $this->faker->boolean,
            'evl'              => $this->faker->boolean,
            'mgmCustomFields'  => $this->faker->boolean,
        ];
    }
}
