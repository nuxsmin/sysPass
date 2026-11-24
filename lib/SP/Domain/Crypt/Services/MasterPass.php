<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Crypt\Services;

use Exception;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Log\LoggerInterface;
use SP\Domain\Core\Log\NullLogger;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Exceptions\MasterPasswordChangeCommittedException;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\Crypt\Ports\MasterPasswordStorageGuard;
use SP\Domain\Crypt\Ports\TemporaryMasterPassService;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Domain\Database\Exceptions\TransactionCommittedException;
use Throwable;
use function SP\__u;

/**
 * Class MasterPass
 */
final class MasterPass extends Service implements MasterPassService
{
    public const PARAM_MASTER_PASS_TIME = 'lastupdatempass';
    public const PARAM_MASTER_PASS_HASH = 'masterPwd';

    public function __construct(
        ConfigFileService $config,
        EventDispatcherInterface $eventDispatcher,
        Context $context,
        private readonly ConfigService                $configService,
        private readonly AccountMasterPasswordService $accountMasterPasswordService,
        private readonly CustomFieldCryptService      $customFieldCryptService,
        private readonly Repository                   $repository,
        private readonly MasterPasswordStorageGuard $storageGuard,
        private readonly LoggerInterface              $logger = new NullLogger()
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    /**
     * @inheritDoc
     */
    public function checkUserUpdateMPass(int $userMPassTime): bool
    {
        try {
            return $userMPassTime >= (int)$this->configService->getByParam(self::PARAM_MASTER_PASS_TIME, '0');
        } catch (ServiceException|NoSuchItemException $e) {
            $this->logger->error($e->getMessage());
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function checkMasterPassword(string $masterPassword): bool
    {
        try {
            return Hash::checkMasterKey(
                $masterPassword,
                $this->configService->getByParam(self::PARAM_MASTER_PASS_HASH)
            );
        } catch (ServiceException|NoSuchItemException $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function changeMasterPassword(UpdateMasterPassRequest $request): void
    {
        $this->eventDispatcher->notify('update.masterPassword.start', new Event($this));
        try {
            $this->repository->transactionAware(
                function () use ($request) {
                    $this->storageGuard->lockAndVerifyCurrentPassword($request->getCurrentMasterPass());
                    $this->storageGuard->assertTransactional();
                    $this->accountMasterPasswordService->updateMasterPassword($request);
                    $this->accountMasterPasswordService->updateHistoryMasterPassword($request);
                    $this->customFieldCryptService->updateMasterPassword($request);
                    $this->updateConfig($request->getHash());
                    foreach (['tempmaster_pass' => '', 'tempmaster_passkey' => '', 'tempmaster_passhash' => '',
                                 'tempmaster_passtime' => '0', TemporaryMasterPassService::PARAM_MAX_TIME => '0',
                                 'tempmaster_attempts' => '0'] as $key => $value) {
                        try {
                            $current = $this->configService->getByParam($key);
                        } catch (NoSuchItemException) {
                            continue;
                        }
                        if ($current !== null && $current !== $value && !$this->configService->save($key, $value)) {
                            throw ServiceException::error(__u('Unable to invalidate temporary master password'));
                        }
                    }
                },
                $this
            );
        } catch (TransactionCommittedException $e) {
            throw new MasterPasswordChangeCommittedException(
                'Master password changed, but a completion notification failed',
                0,
                $e
            );
        }
        try {
            $this->eventDispatcher->notify('update.masterPassword.end', new Event($this));
        } catch (Throwable $e) {
            throw new MasterPasswordChangeCommittedException(
                'Master password changed, but the completion audit event failed',
                0,
                $e
            );
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateConfig($hash): void
    {
        if (!$this->configService->save(self::PARAM_MASTER_PASS_HASH, $hash)
            || !$this->configService->save(self::PARAM_MASTER_PASS_TIME, (string)time())) {
            throw ServiceException::error(__u('Unable to update master password configuration'));
        }
    }
}
