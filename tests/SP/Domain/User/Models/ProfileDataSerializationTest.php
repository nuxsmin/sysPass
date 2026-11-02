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

namespace SP\Tests\Domain\User\Models;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Domain\Common\Adapters\SerializedPayloadRewriter;
use SP\Domain\User\Models\ProfileData;
use SP\Tests\Stubs\DurablePayloadFixtures;

/**
 * Class ProfileDataSerializationTest
 */
#[Group('unitary')]
class ProfileDataSerializationTest extends TestCase
{
    /**
     * Base64 of serialize(new ProfileData([...])) captured at HEAD 8f4ddb672, before
     * __serialize() was added. It guarantees new payloads stay byte-compatible with PHP 8.2.
     */
    private const PRE_CHANGE_PAYLOAD = 'TzozMzoiU1BcRG9tYWluXFVzZXJcTW9kZWxzXFByb2ZpbGVEYXRhIjozMTp7czo0MToiAF'
                                       . 'NQXERvbWFpblxDb21tb25cTW9kZWxzXE1vZGVsAHByb3BlcnRpZXMiO2E6MTp7czoxMToi'
                                       . 'bGVnYWN5RXh0cmEiO3M6Nzoia2VlcC1tZSI7fXM6MTA6IgAqAGFjY1ZpZXciO2I6MTtzOj'
                                       . 'E0OiIAKgBhY2NWaWV3UGFzcyI7YjowO3M6MTc6IgAqAGFjY1ZpZXdIaXN0b3J5IjtiOjA7'
                                       . 'czoxMDoiACoAYWNjRWRpdCI7YjowO3M6MTQ6IgAqAGFjY0VkaXRQYXNzIjtiOjA7czo5Oi'
                                       . 'IAKgBhY2NBZGQiO2I6MDtzOjEyOiIAKgBhY2NEZWxldGUiO2I6MDtzOjExOiIAKgBhY2NG'
                                       . 'aWxlcyI7YjowO3M6MTM6IgAqAGFjY1ByaXZhdGUiO2I6MDtzOjE4OiIAKgBhY2NQcml2YX'
                                       . 'RlR3JvdXAiO2I6MDtzOjE2OiIAKgBhY2NQZXJtaXNzaW9uIjtiOjA7czoxNzoiACoAYWNj'
                                       . 'UHVibGljTGlua3MiO2I6MDtzOjE4OiIAKgBhY2NHbG9iYWxTZWFyY2giO2I6MDtzOjE2Oi'
                                       . 'IAKgBjb25maWdHZW5lcmFsIjtiOjA7czoxOToiACoAY29uZmlnRW5jcnlwdGlvbiI7Yjow'
                                       . 'O3M6MTU6IgAqAGNvbmZpZ0JhY2t1cCI7YjowO3M6MTU6IgAqAGNvbmZpZ0ltcG9ydCI7Yj'
                                       . 'owO3M6MTE6IgAqAG1nbVVzZXJzIjtiOjA7czoxMjoiACoAbWdtR3JvdXBzIjtiOjA7czox'
                                       . 'NDoiACoAbWdtUHJvZmlsZXMiO2I6MDtzOjE2OiIAKgBtZ21DYXRlZ29yaWVzIjtiOjA7cz'
                                       . 'oxNToiACoAbWdtQ3VzdG9tZXJzIjtiOjA7czoxNToiACoAbWdtQXBpVG9rZW5zIjtiOjA7'
                                       . 'czoxNzoiACoAbWdtUHVibGljTGlua3MiO2I6MDtzOjE0OiIAKgBtZ21BY2NvdW50cyI7Yj'
                                       . 'owO3M6MTA6IgAqAG1nbVRhZ3MiO2I6MDtzOjExOiIAKgBtZ21GaWxlcyI7YjowO3M6MTc6'
                                       . 'IgAqAG1nbUl0ZW1zUHJlc2V0IjtiOjA7czo2OiIAKgBldmwiO2I6MDtzOjE4OiIAKgBtZ2'
                                       . '1DdXN0b21GaWVsZHMiO2I6MTt9';

    #[Test]
    public function serializationMatchesPreChangeRepresentation(): void
    {
        $profile = new ProfileData(
            [
                'accView' => true,
                'accEdit' => false,
                'mgmCustomFields' => true,
                'legacyExtra' => 'keep-me',
            ]
        );

        $this->assertSame(
            base64_decode(self::PRE_CHANGE_PAYLOAD, true),
            serialize($profile)
        );

        $restored = unserialize(
            base64_decode(self::PRE_CHANGE_PAYLOAD, true),
            ['allowed_classes' => [ProfileData::class]]
        );

        $this->assertInstanceOf(ProfileData::class, $restored);
        $this->assertTrue($restored->isAccView());
        $this->assertFalse($restored->isAccEdit());
        $this->assertTrue($restored->isMgmCustomFields());
        $this->assertSame('keep-me', $restored->toArray(null, null, true)['legacyExtra']);
        $this->assertSame(
            $profile->toArray(null, null, true),
            $restored->toArray(null, null, true)
        );
    }

    #[Test]
    public function newPayloadRoundTripPreservesAllProperties(): void
    {
        $profile = new ProfileData(
            [
                'accView' => true,
                'accViewPass' => false,
                'accViewHistory' => true,
                'accEdit' => false,
                'accEditPass' => true,
                'accAdd' => false,
                'accDelete' => true,
                'accFiles' => false,
                'accPrivate' => true,
                'accPrivateGroup' => false,
                'accPermission' => true,
                'accPublicLinks' => false,
                'accGlobalSearch' => true,
                'configGeneral' => false,
                'configEncryption' => true,
                'configBackup' => false,
                'configImport' => true,
                'mgmUsers' => false,
                'mgmGroups' => true,
                'mgmProfiles' => false,
                'mgmCategories' => true,
                'mgmCustomers' => false,
                'mgmApiTokens' => true,
                'mgmPublicLinks' => false,
                'mgmAccounts' => true,
                'mgmTags' => false,
                'mgmFiles' => true,
                'mgmItemsPreset' => false,
                'evl' => true,
                'mgmCustomFields' => false,
                'legacyExtra' => 'keep-me',
            ]
        );

        $restored = unserialize(serialize($profile), ['allowed_classes' => [ProfileData::class]]);

        $this->assertInstanceOf(ProfileData::class, $restored);
        $this->assertSame($profile->toArray(), $restored->toArray());
        $this->assertSame(
            $profile->toArray(null, null, true),
            $restored->toArray(null, null, true)
        );
        $this->assertSame('keep-me', $restored->toArray(null, null, true)['legacyExtra']);
    }

    #[Test]
    public function legacyPayloadIsRestored(): void
    {
        $payload = SerializedPayloadRewriter::rewrite(DurablePayloadFixtures::profile());

        $profile = unserialize($payload, ['allowed_classes' => [ProfileData::class]]);

        $this->assertInstanceOf(ProfileData::class, $profile);
        $this->assertSame(
            [
                'accView' => true,
                'accViewPass' => true,
                'accViewHistory' => true,
                'accEdit' => true,
                'accEditPass' => true,
                'accAdd' => true,
                'accDelete' => true,
                'accFiles' => true,
                'accPrivate' => false,
                'accPrivateGroup' => false,
                'accPermission' => true,
                'accPublicLinks' => true,
                'accGlobalSearch' => true,
                'configGeneral' => true,
                'configEncryption' => true,
                'configBackup' => true,
                'configImport' => true,
                'mgmUsers' => true,
                'mgmGroups' => true,
                'mgmProfiles' => true,
                'mgmCategories' => true,
                'mgmCustomers' => true,
                'mgmApiTokens' => true,
                'mgmPublicLinks' => true,
                'mgmAccounts' => true,
                'mgmTags' => true,
                'mgmFiles' => true,
                'mgmItemsPreset' => true,
                'evl' => true,
                'mgmCustomFields' => true,
            ],
            $profile->toArray()
        );
    }

    #[Test]
    public function plainUnderscorePropertyIsNormalised(): void
    {
        $payload = self::objectPayload(ProfileData::class, ['_accView' => true]);

        $profile = unserialize($payload, ['allowed_classes' => [ProfileData::class]]);

        $this->assertInstanceOf(ProfileData::class, $profile);
        $this->assertTrue($profile->isAccView());
        $this->assertArrayNotHasKey('_accView', $profile->toArray(null, null, true));
    }

    #[Test]
    public function mangledUnderscorePropertyIsNormalised(): void
    {
        $payload = self::objectPayload(ProfileData::class, ["\0*\0_accView" => true]);

        $profile = unserialize($payload, ['allowed_classes' => [ProfileData::class]]);

        $this->assertInstanceOf(ProfileData::class, $profile);
        $this->assertTrue($profile->isAccView());
        $this->assertArrayNotHasKey('_accView', $profile->toArray(null, null, true));
        $this->assertArrayNotHasKey("\0*\0_accView", (array)$profile);
    }

    /**
     * @param array<string, mixed> $properties
     */
    private static function objectPayload(string $class, array $properties): string
    {
        $body = '';

        foreach ($properties as $name => $value) {
            $body .= sprintf('s:%d:"%s";%s', strlen($name), $name, serialize($value));
        }

        return sprintf('O:%d:"%s":%d:{%s}', strlen($class), $class, count($properties), $body);
    }
}
