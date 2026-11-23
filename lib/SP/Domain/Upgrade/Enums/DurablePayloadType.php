<?php

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

namespace SP\Domain\Upgrade\Enums;

use function SP\__u;

/**
 * Class DurablePayloadType
 */
enum DurablePayloadType: string
{
    case AuthTokenVault  = 'authTokenVault';
    case PublicLinkData  = 'publicLinkData';
    case UserProfile     = 'userProfile';
    case UserPreferences = 'userPreferences';
    case ItemPreset      = 'itemPreset';
    case PluginData      = 'pluginData';

    public function label(): string
    {
        return match ($this) {
            self::AuthTokenVault => __u('Auth token vault'),
            self::PublicLinkData => __u('Public link data'),
            self::UserProfile => __u('User profile'),
            self::UserPreferences => __u('User preferences'),
            self::ItemPreset => __u('Item presets'),
            self::PluginData => __u('Plugin data')
        };
    }

    public function isRewritable(): bool
    {
        return $this !== self::PluginData;
    }
}
