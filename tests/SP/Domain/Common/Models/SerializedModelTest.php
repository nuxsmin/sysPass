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

namespace SP\Tests\Domain\Common\Models;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserPreferences;
use SP\Domain\User\Models\UserProfile;

/**
 * Class SerializedModelTest
 */
#[Group('unitary')]
final class SerializedModelTest extends TestCase
{
    #[Test]
    public function hydratesJsonProfile(): void
    {
        $profile = new ProfileData(['accView' => true, 'accEdit' => true]);
        $model = new UserProfile(['profile' => $profile->toJson()]);

        $hydrated = $model->hydrate(ProfileData::class);

        self::assertInstanceOf(ProfileData::class, $hydrated);
        self::assertTrue($hydrated->isAccView());
        self::assertTrue($hydrated->isAccEdit());
    }

    #[Test]
    public function hydratesJsonPreferences(): void
    {
        $preferences = new UserPreferences(['lang' => 'en_US', 'resultsPerPage' => 12]);
        $model = new User(['preferences' => $preferences->toJson()]);

        $hydrated = $model->hydrate(UserPreferences::class);

        self::assertInstanceOf(UserPreferences::class, $hydrated);
        self::assertSame('en_US', $hydrated->getLang());
        self::assertSame(12, $hydrated->getResultsPerPage());
    }

    #[Test]
    public function hydratesSerializedPreferences(): void
    {
        $preferences = new UserPreferences(['lang' => 'es_ES', 'resultsPerPage' => 6]);
        $model = new User(['preferences' => serialize($preferences)]);

        $hydrated = $model->hydrate(UserPreferences::class);

        self::assertInstanceOf(UserPreferences::class, $hydrated);
        self::assertSame('es_ES', $hydrated->getLang());
        self::assertSame(6, $hydrated->getResultsPerPage());
    }

    #[Test]
    public function rejectsUnreadablePayload(): void
    {
        $model = new User(['preferences' => serialize('not-a-preferences-object')]);

        $this->expectException(SPException::class);

        $model->hydrate(UserPreferences::class);
    }
}
