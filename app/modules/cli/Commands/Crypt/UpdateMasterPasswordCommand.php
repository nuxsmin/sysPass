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

namespace SP\Modules\Cli\Commands\Crypt;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SP\Domain\Account\Ports\AccountServiceInterface;
use SP\Domain\Account\Services\AccountService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Config\Services\Config;
use SP\Domain\Crypt\Ports\MasterPassServiceInterface;
use SP\Domain\Crypt\Services\MasterPassService;
use SP\Domain\Crypt\Services\UpdateMasterPassRequest;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Cli\Commands\CommandBase;
use SP\Modules\Cli\Commands\Validators;
use SP\Util\Util;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CryptCommand
 *
 * @package SP\Modules\Cli\Commands\Crypt
 */
final class UpdateMasterPasswordCommand extends CommandBase
{
    use LockableTrait;

    /**
     * @var string[]
     */
    public static array $envVarsMapping = [
        'currentMasterPassword' => 'CURRENT_MASTER_PASSWORD',
        'masterPassword'        => 'MASTER_PASSWORD',
        'update'                => 'UPDATE',
    ];
    /**
     * @var string
     */
    protected static                                          $defaultName = 'sp:crypt:update-master-password';
    private MasterPassServiceInterface $masterPassService;
    private Config         $configService;
    private AccountService $accountService;

    public function __construct(
        MasterPassServiceInterface $masterPassService,
        AccountServiceInterface $accountService,
        ConfigService           $configService,
        LoggerInterface         $logger,
        ConfigFileService       $config
    ) {
        $this->masterPassService = $masterPassService;
        $this->accountService = $accountService;
        $this->configService = $configService;

        parent::__construct($logger, $config);
    }

    protected function configure(): void
    {
        $this->setDescription(__('Update sysPass master password'))
            ->setHelp(__('This command updates sysPass master password for all the encrypted data'))
            ->addOption(
                'masterPassword',
                null,
                InputOption::VALUE_REQUIRED,
                __('The new master password to encrypt the data')
            )
            ->addOption(
                'currentMasterPassword',
                null,
                InputOption::VALUE_REQUIRED,
                __('The current master password')
            )
            ->addOption(
                'update',
                null,
                InputOption::VALUE_NONE,
                __('Skip asking to confirm the update')
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $style = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $style->warning(__('The command is already running in another process'));

            return self::SUCCESS;
        }

        try {
            $this->checkInstalled();
            $this->checkMaintenance();

            $masterPassword = $this->getMasterPassword($input, $style);
            $currentMasterPassword = $this->getCurrentMasterPassword($input, $style);

            if ($masterPassword === $currentMasterPassword) {
                $this->logger->debug(__u('Passwords are the same'));
                $style->info(__('Passwords are the same'));

                return self::FAILURE;
            }

            $this->checkMasterPassword($currentMasterPassword);

            $request = new UpdateMasterPassRequest(
                $currentMasterPassword,
                $masterPassword,
                $this->configService->getByParam(MasterPassService::PARAM_MASTER_PASS_HASH)
            );

            if (!$this->getUpdate($input, $style)) {
                $this->logger->debug(__u('Master password update aborted'));
                $style->info(__('Master password update aborted'));

                return self::FAILURE;
            }

            $style->warning(__('You should save the new password on a secure place'));
            $style->warning(__('All accounts passwords will be encrypted again.'));
            $style->warning(__('Users will need to enter the new Master Password.'));
            $style->warning(
                printf(
                    __('It will be updated %s accounts. This process could take some time long.'),
                    $this->accountService->getTotalNumAccounts()
                )
            );
            $style->newLine();
            $style->caution(__('This is a critical process, please do not cancel/close this CLI'));

            $style->ask(__('Please, press any key to continue'));

            $this->masterPassService->changeMasterPassword($request);

            $this->logger->info(__u('Master password updated'));

            $style->success(__('Master password updated'));
            $style->info(__('Please, restart any browser session to update it'));

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->logger->error($e->getTraceAsString());
            $this->logger->error($e->getMessage());

            $style->error(__($e->getMessage()));
        } finally {
            $this->release();
        }

        return self::FAILURE;
    }

    private function checkInstalled(): void
    {
        if (!defined('TEST_ROOT')
            && !$this->configData->isInstalled()) {
            throw new RuntimeException(__u('sysPass is not installed'));
        }
    }

    private function checkMaintenance(): void
    {
        if (!defined('TEST_ROOT')
            && !$this->configData->isMaintenance()) {
            throw new RuntimeException(__u('Maintenance mode not enabled'));
        }
    }

    /**
     * @return array|false|mixed|string
     */
    private function getMasterPassword(
        InputInterface $input,
        StyleInterface $style
    ) {
        $password = self::getEnvVarOrOption('masterPassword', $input);

        if (empty($password)) {
            $this->logger->debug(__u('Ask for master password'));

            $password = $style->askHidden(
                __('Please provide the new master password'),
                fn($value) => Validators::valueNotEmpty(
                    $value,
                    sprintf(__u('%s cannot be blank'), 'Master password')
                )
            );
            $passwordRepeat = $style->askHidden(
                __('Please provide the new master password again'),
                fn($value) => Validators::valueNotEmpty(
                    $value,
                    sprintf(__u('%s cannot be blank'), 'Master password')
                )
            );

            if ($password !== $passwordRepeat) {
                throw new RuntimeException(__u('Passwords do not match'));
            } elseif (null === $password || null === $passwordRepeat) {
                throw new RuntimeException(sprintf(__u('%s cannot be blank'), 'Master password'));
            }
        }

        return $password;
    }

    /**
     * @return array|false|mixed|string
     */
    private function getCurrentMasterPassword(
        InputInterface $input,
        StyleInterface $style
    ) {
        $password = self::getEnvVarOrOption('currentMasterPassword', $input);

        if (empty($password)) {
            $this->logger->debug(__u('Ask for current master password'));

            $password = $style->askHidden(
                __('Please provide the current master password'),
                fn($value) => Validators::valueNotEmpty(
                    $value,
                    sprintf(__u('%s cannot be blank'), 'Master password')
                )
            );
            $passwordRepeat = $style->askHidden(
                __('Please provide the current master password again'),
                fn($value) => Validators::valueNotEmpty(
                    $value,
                    sprintf(__u('%s cannot be blank'), 'Master password')
                )
            );

            if ($password !== $passwordRepeat) {
                throw new RuntimeException(__u('Passwords do not match'));
            } elseif (null === $password || null === $passwordRepeat) {
                throw new RuntimeException(sprintf(__u('%s cannot be blank'), 'Master password'));
            }
        }

        return $password;
    }

    /**
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    private function checkMasterPassword(string $password): void
    {
        if (!$this->masterPassService->checkMasterPassword($password)) {
            throw new RuntimeException(__u('The current master password does not match'));
        }
    }

    private function getUpdate(
        InputInterface $input,
        StyleInterface $style
    ): bool {
        $option = 'update';

        $envUpdate = self::getEnvVarForOption($option);

        $value = $envUpdate !== false
            ? Util::boolval($envUpdate)
            : $input->getOption($option);

        if ($value === false) {
            return $style->confirm(__('Update master password? (This process cannot be undone)'), false);
        }

        return true;
    }
}
