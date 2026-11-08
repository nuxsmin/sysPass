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
use SP\Domain\User\Models\UserPreferences;
use SP\Tests\Stubs\DurablePayloadFixtures;

/**
 * Class UserPreferencesSerializationTest
 */
#[Group('unitary')]
class UserPreferencesSerializationTest extends TestCase
{
    /**
     * Base64 of serialize(new UserPreferences([...])) captured at HEAD 8f4ddb672, before
     * __serialize() was added. It guarantees new payloads stay byte-compatible with PHP 8.2.
     */
    private const PRE_CHANGE_PAYLOAD = 'TzozNzoiU1BcRG9tYWluXFVzZXJcTW9kZWxzXFVzZXJQcmVmZXJlbmNlcyI6MTI6e3M6ND'
                                       . 'E6IgBTUFxEb21haW5cQ29tbW9uXE1vZGVsc1xNb2RlbABwcm9wZXJ0aWVzIjthOjE6e3M6'
                                       . 'MTE6ImxlZ2FjeUV4dHJhIjtzOjc6ImtlZXAtbWUiO31zOjc6IgAqAGxhbmciO3M6NToiZW'
                                       . '5fVVMiO3M6ODoiACoAdGhlbWUiO3M6MTM6Im1hdGVyaWFsLWJsdWUiO3M6MTc6IgAqAHJl'
                                       . 'c3VsdHNQZXJQYWdlIjtpOjEyO3M6MTQ6IgAqAGFjY291bnRMaW5rIjtiOjA7czoxMjoiAC'
                                       . 'oAc29ydFZpZXdzIjtiOjA7czoxMjoiACoAdG9wTmF2YmFyIjtiOjA7czoxODoiACoAb3B0'
                                       . 'aW9uYWxBY3Rpb25zIjtiOjA7czoxNzoiACoAcmVzdWx0c0FzQ2FyZHMiO2I6MDtzOjIxOi'
                                       . 'IAKgBjaGVja05vdGlmaWNhdGlvbnMiO2I6MTtzOjI3OiIAKgBzaG93QWNjb3VudFNlYXJj'
                                       . 'aEZpbHRlcnMiO2I6MDtzOjEwOiIAKgB1c2VyX2lkIjtpOjc7fQ==';

    /**
     * PHP 8.2-era payload for the legacy SP\DataModel\UserPreferencesData layout: every property
     * is underscore-prefixed and the class name predates the current namespace. It exercises both
     * SerializedPayloadRewriter and the legacy name normalisation in __unserialize().
     */
    private const LEGACY_UNDERSCORE_PAYLOAD = 'TzozMjoiU1BcRGF0YU1vZGVsXFVzZXJQcmVmZXJlbmNlc0RhdGEiOjExOntzOjg6Il91c2VyX2lkIjtpOjE7czo1'
                                                . 'OiJfbGFuZyI7czo1OiJlc19FUyI7czo2OiJfdGhlbWUiO3M6MTM6Im1hdGVyaWFsLWJsdWUiO3M6MTU6Il9yZXN1'
                                                . 'bHRzUGVyUGFnZSI7aTo2O3M6MTI6Il9hY2NvdW50TGluayI7YjoxO3M6MTA6Il9zb3J0Vmlld3MiO2I6MTtzOjEw'
                                                . 'OiJfdG9wTmF2YmFyIjtiOjA7czoxNjoiX29wdGlvbmFsQWN0aW9ucyI7YjoxO3M6MTU6Il9yZXN1bHRzQXNDYXJk'
                                                . 'cyI7YjoxO3M6MTk6Il9jaGVja05vdGlmaWNhdGlvbnMiO2I6MTtzOjI1OiJfc2hvd0FjY291bnRTZWFyY2hGaWx0'
                                                . 'ZXJzIjtiOjA7fQ==';

    #[Test]
    public function serializationMatchesPreChangeRepresentation(): void
    {
        $preferences = new UserPreferences(
            [
                'lang' => 'en_US',
                'theme' => 'material-blue',
                'resultsPerPage' => 12,
                'user_id' => 7,
                'legacyExtra' => 'keep-me',
            ]
        );

        $this->assertSame(
            base64_decode(self::PRE_CHANGE_PAYLOAD, true),
            serialize($preferences)
        );

        $restored = unserialize(
            base64_decode(self::PRE_CHANGE_PAYLOAD, true),
            ['allowed_classes' => [UserPreferences::class]]
        );

        $this->assertInstanceOf(UserPreferences::class, $restored);
        $this->assertSame('en_US', $restored->getLang());
        $this->assertSame('material-blue', $restored->getTheme());
        $this->assertSame(12, $restored->getResultsPerPage());
        $this->assertSame(7, $restored->getUserId());
        $this->assertSame('keep-me', $restored->toArray(null, null, true)['legacyExtra']);
        $this->assertSame(
            $preferences->toArray(null, null, true),
            $restored->toArray(null, null, true)
        );
    }

    #[Test]
    public function newPayloadRoundTripPreservesAllProperties(): void
    {
        $preferences = new UserPreferences(
            [
                'lang' => 'en_US',
                'theme' => 'material-blue',
                'resultsPerPage' => 12,
                'accountLink' => true,
                'sortViews' => false,
                'topNavbar' => true,
                'optionalActions' => false,
                'resultsAsCards' => true,
                'checkNotifications' => false,
                'showAccountSearchFilters' => true,
                'user_id' => 7,
                'legacyExtra' => 'keep-me',
            ]
        );

        $restored = unserialize(serialize($preferences), ['allowed_classes' => [UserPreferences::class]]);

        $this->assertInstanceOf(UserPreferences::class, $restored);
        $this->assertSame($preferences->toArray(), $restored->toArray());
        $this->assertSame(
            $preferences->toArray(null, null, true),
            $restored->toArray(null, null, true)
        );
        $this->assertSame('keep-me', $restored->toArray(null, null, true)['legacyExtra']);
    }

    #[Test]
    public function legacyPayloadIsRestored(): void
    {
        $payload = SerializedPayloadRewriter::rewrite(DurablePayloadFixtures::preferences());

        $preferences = unserialize($payload, ['allowed_classes' => [UserPreferences::class]]);

        $this->assertInstanceOf(UserPreferences::class, $preferences);
        $this->assertSame(
            [
                'lang' => 'es_ES',
                'theme' => 'material-blue',
                'resultsPerPage' => 6,
                'accountLink' => true,
                'sortViews' => true,
                'topNavbar' => false,
                'optionalActions' => true,
                'resultsAsCards' => true,
                'checkNotifications' => true,
                'showAccountSearchFilters' => false,
                'user_id' => 1,
            ],
            $preferences->toArray()
        );
    }

    #[Test]
    public function legacyUnderscorePayloadRestoresEveryProperty(): void
    {
        $expected = [
            'lang' => 'es_ES',
            'theme' => 'material-blue',
            'resultsPerPage' => 6,
            'accountLink' => true,
            'sortViews' => true,
            'topNavbar' => false,
            'optionalActions' => true,
            'resultsAsCards' => true,
            'checkNotifications' => true,
            'showAccountSearchFilters' => false,
            'user_id' => 1,
        ];

        $payload = SerializedPayloadRewriter::rewrite(
            base64_decode(self::LEGACY_UNDERSCORE_PAYLOAD, true)
        );

        $preferences = unserialize($payload, ['allowed_classes' => [UserPreferences::class]]);

        $this->assertInstanceOf(UserPreferences::class, $preferences);
        $this->assertSame($expected, $preferences->toArray());
        $this->assertSame($expected, $preferences->toArray(null, null, true));
        $this->assertSame('es_ES', $preferences->getLang());
        $this->assertSame('material-blue', $preferences->getTheme());
        $this->assertSame(6, $preferences->getResultsPerPage());
        $this->assertSame(1, $preferences->getUserId());

        $roundTripped = unserialize(
            serialize($preferences),
            ['allowed_classes' => [UserPreferences::class]]
        );

        $this->assertInstanceOf(UserPreferences::class, $roundTripped);
        $this->assertSame($expected, $roundTripped->toArray());
    }

    #[Test]
    public function plainUnderscorePropertyIsNormalised(): void
    {
        $payload = self::objectPayload(UserPreferences::class, ['_lang' => 'es_ES']);

        $preferences = unserialize($payload, ['allowed_classes' => [UserPreferences::class]]);

        $this->assertInstanceOf(UserPreferences::class, $preferences);
        $this->assertSame('es_ES', $preferences->getLang());
        $this->assertArrayNotHasKey('_lang', $preferences->toArray(null, null, true));
    }

    #[Test]
    public function mangledUnderscorePropertyIsNormalised(): void
    {
        $payload = self::objectPayload(UserPreferences::class, ["\0*\0_lang" => 'es_ES']);

        $preferences = unserialize($payload, ['allowed_classes' => [UserPreferences::class]]);

        $this->assertInstanceOf(UserPreferences::class, $preferences);
        $this->assertSame('es_ES', $preferences->getLang());
        $this->assertArrayNotHasKey('_lang', $preferences->toArray(null, null, true));
        $this->assertArrayNotHasKey("\0*\0_lang", (array)$preferences);
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
