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

namespace SP\Domain\Upgrade\Services;

use JsonException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionProperty;
use SP\Domain\Common\Attributes\UpgradeVersion;
use SP\Domain\Common\Adapters\SerializedPayloadReader;
use SP\Domain\Common\Adapters\SerializedPayloadRewriter;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Domain\ItemPreset\Models\AccountPermission;
use SP\Domain\ItemPreset\Models\AccountPrivate;
use SP\Domain\ItemPreset\Models\Password;
use SP\Domain\ItemPreset\Models\SessionTimeout;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\Upgrade\Dtos\DurablePayloadRow;
use SP\Domain\Upgrade\Enums\DurablePayloadType;
use SP\Domain\Upgrade\Ports\DurablePayloadRepository;
use SP\Domain\Upgrade\Ports\UpgradeHandlerService;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use Throwable;

use function SP\__u;

/**
 * Class UpgradeDurablePayloads
 */
#[UpgradeVersion('4.0.0')]
final class UpgradeDurablePayloads extends Service implements UpgradeHandlerService
{
    public function __construct(
        ConfigFileService        $config,
        EventDispatcherInterface $eventDispatcher,
        Context                  $context,
        private readonly DurablePayloadRepository $payloadRepository,
        private readonly AppLockHandler           $appLock,
        private readonly CryptInterface           $crypt,
        private readonly LoggerInterface          $logger = new NullLogger()
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    public function apply(string $version, ConfigDataInterface $configData): bool
    {
        try {
            if ($this->appLock->getLock() !== false) {
                $this->eventDispatcher->notify(
                    'upgrade.payloads.process',
                    new Event(
                        $this,
                        EventMessage::build()
                                    ->addDescription(__u('Another operation is in progress'))
                    )
                );

                return false;
            }

            $this->appLock->lock(0, 'upgrade-durable-payloads');
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Cannot lock the application for the payload upgrade: %s', $e->getMessage()));

            $this->eventDispatcher->notify(
                'upgrade.payloads.process',
                new Event(
                    $this,
                    EventMessage::build()
                                ->addDescription(__u('Cannot lock the application for the payload upgrade'))
                                ->addDetail(__u('Reason'), $e->getMessage())
                )
            );

            return false;
        }

        try {
            $failed = false;

            foreach (DurablePayloadType::cases() as $type) {
                $failed = $this->processType($type) || $failed;
            }

            return !$failed;
        } finally {
            $this->appLock->unlock();
        }
    }

    private function processType(DurablePayloadType $type): bool
    {
        $rewritten = 0;
        $current = 0;
        $empty = 0;
        $unreadable = 0;
        $failedRows = 0;
        $skipped = 0;
        $failed = false;

        try {
            foreach ($this->payloadRepository->findByType($type) as $row) {
                if (!$type->isRewritable()) {
                    if ($row->payload !== null && $row->payload !== '') {
                        $skipped++;
                    } else {
                        $empty++;
                    }

                    continue;
                }

                if ($row->payload === null || $row->payload === '') {
                    $empty++;
                    continue;
                }

                if ($type === DurablePayloadType::UserPreferences && $this->isJsonPayload($row->payload)) {
                    $current++;
                    continue;
                }

                try {
                    $expectedClass = $this->expectedClass($type, $row->subtype);

                    if ($expectedClass === null) {
                        throw InvalidClassException::error(__u('Unsupported serialized payload type'));
                    }

                    $object = SerializedPayloadReader::read($row->payload, $expectedClass);

                    $payload = $this->rewritePayload($type, $row->payload, $object);

                    if ($payload === $row->payload) {
                        $current++;
                        continue;
                    }

                    $affected = $this->payloadRepository->transactional(
                        fn(): int => $this->payloadRepository->update($type, $row->id, $payload)
                    );

                    if ($affected !== 1) {
                        throw UpgradeException::error(__u('The serialized payload row could not be updated'));
                    }

                    $rewritten++;
                } catch (InvalidClassException $e) {
                    $unreadable++;
                    $failed = true;
                    $this->reportRowFailure($type, $row, $e);
                } catch (Throwable $e) {
                    $failedRows++;
                    $failed = true;
                    $this->reportRowFailure($type, $row, $e);
                }
            }
        } catch (Throwable $e) {
            $failed = true;
            $this->reportTypeFailure($type, $e);
        }

        $this->reportSummary($type, $rewritten, $current, $empty, $unreadable, $failedRows, $skipped);

        return $failed;
    }

    private function rewritePayload(DurablePayloadType $type, string $payload, object $object): string
    {
        if ($type === DurablePayloadType::AuthTokenVault || $type === DurablePayloadType::PublicLinkData) {
            $crypt = new ReflectionProperty(Vault::class, 'crypt');

            if (!$crypt->isInitialized($object)) {
                $crypt->setValue($object, $this->crypt);

                return serialize($object);
            }
        }

        return SerializedPayloadRewriter::rewrite($payload);
    }

    /**
     * @return class-string|null
     */
    private function expectedClass(DurablePayloadType $type, ?string $subtype): ?string
    {
        return match ($type) {
            DurablePayloadType::AuthTokenVault, DurablePayloadType::PublicLinkData => Vault::class,
            DurablePayloadType::UserProfile => ProfileData::class,
            DurablePayloadType::UserPreferences => UserPreferences::class,
            DurablePayloadType::ItemPreset => match ($subtype) {
                ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD => Password::class,
                ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE => AccountPrivate::class,
                ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION => AccountPermission::class,
                ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT => SessionTimeout::class,
                default => null
            },
            DurablePayloadType::PluginData => null
        };
    }

    private function isJsonPayload(string $payload): bool
    {
        if (!str_starts_with(ltrim($payload), '{')) {
            return false;
        }

        try {
            json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (JsonException) {
            return false;
        }
    }

    private function reportRowFailure(DurablePayloadType $type, DurablePayloadRow $row, Throwable $e): void
    {
        $description = $e instanceof InvalidClassException
            ? __u('Unreadable serialized payload')
            : __u('Error while migrating a serialized payload');

        $this->logger->error(
            sprintf(
                '%s (%s:%s): %s',
                $description,
                $type->value,
                $row->id,
                $e->getMessage()
            )
        );

        $this->eventDispatcher->notify(
            'upgrade.payloads.process',
            new Event(
                $this,
                EventMessage::build()
                            ->addDescription($description)
                            ->addDetail(__u('Type'), $type->label())
                            ->addDetail(__u('ID'), $row->id)
                            ->addDetail(__u('Reason'), $e->getMessage())
            )
        );
    }

    private function reportTypeFailure(DurablePayloadType $type, Throwable $e): void
    {
        $this->logger->error(
            sprintf('Error while migrating serialized payloads (%s): %s', $type->value, $e->getMessage())
        );

        $this->eventDispatcher->notify(
            'upgrade.payloads.process',
            new Event(
                $this,
                EventMessage::build()
                            ->addDescription(__u('Error while migrating serialized payloads'))
                            ->addDetail(__u('Type'), $type->label())
                            ->addDetail(__u('Reason'), $e->getMessage())
            )
        );
    }

    private function reportSummary(
        DurablePayloadType $type,
        int                $rewritten,
        int                $current,
        int                $empty,
        int                $unreadable,
        int                $failed,
        int                $skipped
    ): void {
        $this->eventDispatcher->notify(
            'upgrade.payloads.process',
            new Event(
                $this,
                EventMessage::build()
                            ->addDescription(__u('Durable serialized payloads processed'))
                            ->addDetail(__u('Type'), $type->label())
                            ->addDetail(__u('Rewritten'), $rewritten)
                            ->addDetail(__u('Current'), $current)
                            ->addDetail(__u('Empty'), $empty)
                            ->addDetail(__u('Unreadable'), $unreadable)
                            ->addDetail(__u('Failed'), $failed)
                            ->addDetail(__u('Skipped'), $skipped)
            )
        );
    }
}
