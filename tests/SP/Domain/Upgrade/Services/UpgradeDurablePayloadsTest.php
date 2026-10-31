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

namespace SP\Tests\Domain\Upgrade\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use ReflectionProperty;
use RuntimeException;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Common\Adapters\SerializedPayloadReader;
use SP\Domain\Common\Adapters\SerializedPayloadRewriter;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\Upgrade\Dtos\DurablePayloadRow;
use SP\Domain\Upgrade\Enums\DurablePayloadType;
use SP\Domain\Upgrade\Services\UpgradeDurablePayloads;
use SP\Tests\Stubs\DurablePayloadFixtures;
use SP\Tests\Stubs\InMemoryDurablePayloadRepository;
use SP\Tests\UnitaryTestCase;

/**
 * Class UpgradeDurablePayloadsTest
 */
#[Group('unitary')]
final class UpgradeDurablePayloadsTest extends UnitaryTestCase
{
    #[Test]
    public function appliesLegacyPayloadsAndLeavesCurrentAndPluginRowsUntouched(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $messages = [];
        $handler = $this->buildHandler($repository, $this->buildEventDispatcher($messages));

        $legacy = [
            [DurablePayloadType::AuthTokenVault, '1', DurablePayloadFixtures::vaultWithCrypt(), null],
            [DurablePayloadType::PublicLinkData, '2', DurablePayloadFixtures::vaultWithCrypt(), null],
            [DurablePayloadType::UserProfile, '3', DurablePayloadFixtures::profile(), null],
            [DurablePayloadType::UserPreferences, '4', DurablePayloadFixtures::preferences(), null],
            [
                DurablePayloadType::ItemPreset,
                '5',
                DurablePayloadFixtures::password(),
                ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD
            ],
            [
                DurablePayloadType::ItemPreset,
                '6',
                DurablePayloadFixtures::accountPrivate(),
                ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE
            ],
            [
                DurablePayloadType::ItemPreset,
                '7',
                DurablePayloadFixtures::accountPermission(),
                ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION
            ],
            [
                DurablePayloadType::ItemPreset,
                '8',
                DurablePayloadFixtures::sessionTimeout(),
                ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT
            ],
        ];

        foreach ($legacy as [$type, $id, $payload, $subtype]) {
            $repository->add($type, new DurablePayloadRow($id, $payload, $subtype));
        }

        $current = [
            [DurablePayloadType::AuthTokenVault, '9', DurablePayloadFixtures::currentVault(), null],
            [DurablePayloadType::UserPreferences, '10', DurablePayloadFixtures::jsonPreferences(), null],
            [DurablePayloadType::UserProfile, '12', DurablePayloadFixtures::jsonProfile(), null],
            [
                DurablePayloadType::ItemPreset,
                '11',
                SerializedPayloadRewriter::rewrite(DurablePayloadFixtures::password()),
                ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD
            ],
        ];

        foreach ($current as [$type, $id, $payload, $subtype]) {
            $repository->add($type, new DurablePayloadRow($id, $payload, $subtype));
        }

        $repository->add(
            DurablePayloadType::PluginData,
            new DurablePayloadRow('Authenticator:1', 'ciphertext')
        );

        self::assertTrue($handler->apply('400.0', $this->createStub(ConfigDataInterface::class)));

        foreach ($legacy as [$type, $id, $payload]) {
            self::assertSame(
                SerializedPayloadRewriter::rewrite($payload),
                $repository->payload($type, $id),
                sprintf('Row %s:%s was not rewritten', $type->value, $id)
            );
        }

        foreach ($current as [$type, $id, $payload]) {
            self::assertSame($payload, $repository->payload($type, $id));
        }

        self::assertSame('ciphertext', $repository->payload(DurablePayloadType::PluginData, 'Authenticator:1'));
        self::assertCount(8, $repository->updates);
        self::assertSame(8, $repository->transactionCount);

        $report = implode("\n", $messages);

        self::assertStringContainsString('Durable serialized payloads processed', $report);
        self::assertStringContainsString(DurablePayloadType::PluginData->label(), $report);
    }

    #[Test]
    public function isIdempotent(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $handler = $this->buildHandler($repository);

        $repository->add(DurablePayloadType::AuthTokenVault, new DurablePayloadRow('1', DurablePayloadFixtures::vaultWithoutCrypt()));
        $repository->add(DurablePayloadType::UserPreferences, new DurablePayloadRow('2', DurablePayloadFixtures::preferences()));
        $repository->add(
            DurablePayloadType::ItemPreset,
            new DurablePayloadRow(
                '3',
                DurablePayloadFixtures::password(),
                ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD
            )
        );

        $configData = $this->createStub(ConfigDataInterface::class);

        self::assertTrue($handler->apply('400.0', $configData));
        self::assertCount(3, $repository->updates);
        self::assertSame(3, $repository->transactionCount);

        self::assertTrue($handler->apply('400.0', $configData));
        self::assertCount(3, $repository->updates);
        self::assertSame(3, $repository->transactionCount);
    }

    #[Test]
    public function failsOnUnreadableRowAndKeepsItByteIdentical(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $messages = [];
        $handler = $this->buildHandler($repository, $this->buildEventDispatcher($messages));

        $repository->add(DurablePayloadType::AuthTokenVault, new DurablePayloadRow('1', DurablePayloadFixtures::vaultWithCrypt()));
        $repository->add(
            DurablePayloadType::AuthTokenVault,
            new DurablePayloadRow('2', DurablePayloadFixtures::malformed())
        );

        self::assertFalse($handler->apply('400.0', $this->createStub(ConfigDataInterface::class)));
        self::assertSame(DurablePayloadFixtures::currentVault(), $repository->payload(DurablePayloadType::AuthTokenVault, '1'));
        self::assertSame(DurablePayloadFixtures::malformed(), $repository->payload(DurablePayloadType::AuthTokenVault, '2'));
        self::assertCount(1, $repository->updates);
        self::assertSame(1, $repository->transactionCount);

        $report = implode("\n", $messages);

        self::assertStringContainsString('Unreadable serialized payload', $report);
        self::assertStringContainsString(DurablePayloadType::AuthTokenVault->label(), $report);
        self::assertStringContainsString('2', $report);
    }

    #[Test]
    public function failsOnUnsupportedItemPresetType(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $messages = [];
        $handler = $this->buildHandler($repository, $this->buildEventDispatcher($messages));

        $repository->add(
            DurablePayloadType::ItemPreset,
            new DurablePayloadRow('1', DurablePayloadFixtures::password(), 'account.tag')
        );

        self::assertFalse($handler->apply('400.0', $this->createStub(ConfigDataInterface::class)));
        self::assertSame(
            DurablePayloadFixtures::password(),
            $repository->payload(DurablePayloadType::ItemPreset, '1')
        );
        self::assertSame(0, $repository->transactionCount);

        $report = implode("\n", $messages);

        self::assertStringContainsString('Unreadable serialized payload', $report);
        self::assertStringContainsString(DurablePayloadType::ItemPreset->label(), $report);
        self::assertStringContainsString('1', $report);
    }

    #[Test]
    public function failsWhenUpdateAffectsNoRows(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $repository->returnZeroAffected = true;
        $messages = [];
        $handler = $this->buildHandler($repository, $this->buildEventDispatcher($messages));

        $repository->add(DurablePayloadType::AuthTokenVault, new DurablePayloadRow('1', DurablePayloadFixtures::vaultWithCrypt()));

        self::assertFalse($handler->apply('400.0', $this->createStub(ConfigDataInterface::class)));
        self::assertSame(DurablePayloadFixtures::vaultWithCrypt(), $repository->payload(DurablePayloadType::AuthTokenVault, '1'));
        self::assertSame(1, $repository->transactionCount);

        $report = implode("\n", $messages);

        self::assertStringContainsString('Error while migrating a serialized payload', $report);
        self::assertStringContainsString('1', $report);
    }

    #[Test]
    public function failsAndContinuesWhenUpdateThrows(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $repository->updateException = new RuntimeException('write failed');
        $messages = [];
        $handler = $this->buildHandler($repository, $this->buildEventDispatcher($messages));

        $repository->add(DurablePayloadType::AuthTokenVault, new DurablePayloadRow('1', DurablePayloadFixtures::vaultWithCrypt()));
        $repository->add(DurablePayloadType::AuthTokenVault, new DurablePayloadRow('2', DurablePayloadFixtures::vaultWithCrypt()));

        self::assertFalse($handler->apply('400.0', $this->createStub(ConfigDataInterface::class)));
        self::assertSame(DurablePayloadFixtures::vaultWithCrypt(), $repository->payload(DurablePayloadType::AuthTokenVault, '1'));
        self::assertSame(DurablePayloadFixtures::vaultWithCrypt(), $repository->payload(DurablePayloadType::AuthTokenVault, '2'));
        self::assertSame(2, $repository->transactionCount);

        $report = implode("\n", $messages);

        self::assertStringContainsString('Error while migrating a serialized payload', $report);
        self::assertStringContainsString('write failed', $report);
        self::assertStringContainsString('1', $report);
        self::assertStringContainsString('2', $report);
    }

    #[Test]
    public function skipsEmptyPayloads(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $handler = $this->buildHandler($repository);

        $repository->add(DurablePayloadType::AuthTokenVault, new DurablePayloadRow('1', null));
        $repository->add(DurablePayloadType::AuthTokenVault, new DurablePayloadRow('2', ''));
        $repository->add(DurablePayloadType::UserPreferences, new DurablePayloadRow('3', null));

        self::assertTrue($handler->apply('400.0', $this->createStub(ConfigDataInterface::class)));
        self::assertCount(0, $repository->updates);
        self::assertSame(0, $repository->transactionCount);
    }

    #[Test]
    public function repairsVaultWithoutCrypt(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $handler = $this->buildHandler($repository);

        $repository->add(
            DurablePayloadType::AuthTokenVault,
            new DurablePayloadRow('1', DurablePayloadFixtures::vaultWithoutCrypt())
        );

        $configData = $this->createStub(ConfigDataInterface::class);

        self::assertTrue($handler->apply('400.0', $configData));

        $payload = $repository->payload(DurablePayloadType::AuthTokenVault, '1');

        self::assertNotNull($payload);
        self::assertNotSame(DurablePayloadFixtures::vaultWithoutCrypt(), $payload);

        $vault = SerializedPayloadReader::read($payload, Vault::class);
        $crypt = new ReflectionProperty(Vault::class, 'crypt');

        self::assertTrue($crypt->isInitialized($vault));
        self::assertCount(1, $repository->updates);
        self::assertSame(1, $repository->transactionCount);

        self::assertTrue($handler->apply('400.0', $configData));
        self::assertSame(1, $repository->transactionCount);
    }

    #[Test]
    public function keepsJsonProfileAndPreferencesAsCurrent(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $handler = $this->buildHandler($repository);

        $repository->add(
            DurablePayloadType::UserProfile,
            new DurablePayloadRow('1', DurablePayloadFixtures::jsonProfile())
        );
        $repository->add(
            DurablePayloadType::UserPreferences,
            new DurablePayloadRow('2', DurablePayloadFixtures::jsonPreferences())
        );

        self::assertTrue($handler->apply('400.0', $this->createStub(ConfigDataInterface::class)));
        self::assertSame(
            DurablePayloadFixtures::jsonProfile(),
            $repository->payload(DurablePayloadType::UserProfile, '1')
        );
        self::assertSame(
            DurablePayloadFixtures::jsonPreferences(),
            $repository->payload(DurablePayloadType::UserPreferences, '2')
        );
        self::assertCount(0, $repository->updates);
        self::assertSame(0, $repository->transactionCount);
    }

    #[Test]
    public function reportsTypeFailureAndFails(): void
    {
        $repository = new InMemoryDurablePayloadRepository();
        $repository->findException = new RuntimeException('query failed');
        $messages = [];
        $handler = $this->buildHandler($repository, $this->buildEventDispatcher($messages));

        self::assertFalse($handler->apply('400.0', $this->createStub(ConfigDataInterface::class)));
        self::assertStringContainsString('Error while migrating serialized payloads', implode("\n", $messages));
    }

    private function buildHandler(
        InMemoryDurablePayloadRepository $repository,
        ?EventDispatcherInterface         $eventDispatcher = null
    ): UpgradeDurablePayloads {
        return new UpgradeDurablePayloads(
            $this->config,
            $eventDispatcher ?? $this->eventDispatcherStub,
            $this->context,
            $repository,
            new Crypt(),
            new NullLogger()
        );
    }

    /**
     * @param list<string> $messages
     */
    private function buildEventDispatcher(array &$messages): EventDispatcherInterface
    {
        $formatter = new EventMessageFormatter();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $eventDispatcher
            ->method('notify')
            ->willReturnCallback(
                static function (string $eventName, Event $event) use (&$messages, $formatter): void {
                    $message = $event->getEventMessage();

                    if ($message !== null) {
                        $messages[] = $formatter->composeText($message);
                    }
                }
            );

        return $eventDispatcher;
    }
}
