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

namespace SP\Domain\Import\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Category\Models\Category;
use SP\Domain\Client\Models\Client;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ImportHelperInterface;
use SP\Domain\Import\Ports\ItemsImportService;
use SP\Infrastructure\File\FileException;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class CsvImport
 */
final class CsvImport extends ImportBase implements ItemsImportService
{
    private const NUM_FIELDS = 7;

    public function __construct(
        Application                           $application,
        ImportHelperInterface $importHelper,
        CryptInterface                        $crypt,
        private readonly FileHandlerInterface $fileHandler
    ) {
        parent::__construct($application, $importHelper, $crypt);
    }

    /**
     * Import the data from a CSV file
     *
     * @param ImportParamsDto $importParams
     * @return ItemsImportService
     * @throws FileException
     * @throws ImportException
     */
    public function doImport(ImportParamsDto $importParams): ItemsImportService
    {
        $this->eventDispatcher->notify(
            'run.import.csv',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(sprintf(__('Detected format: %s'), 'CSV'))
            )
        );

        $this->processAccounts($importParams);

        return $this;
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    private function processAccounts(ImportParamsDto $importParamsDto): void
    {
        $line = 0;

        foreach ($this->fileHandler->readFromCsv($importParamsDto->getDelimiter()) as $fields) {
            $line++;
            $numfields = count($fields);

            if ($numfields !== self::NUM_FIELDS) {
                throw ImportException::error(
                    sprintf(__('Wrong number of fields (%d)'), $numfields),
                    sprintf(__('Please, check the CSV file format in line %s'), $line)
                );
            }

            [
                $accountName,
                $clientName,
                $categoryName,
                $url,
                $login,
                $password,
                $notes,
            ] = $fields;

            try {
                if (empty($clientName) || empty($categoryName)) {
                    throw ImportException::error('Either client or category name not set');
                }

                $clientId = $this->addClient(new Client(['name' => $clientName]));
                $categoryId = $this->addCategory(new Category(['name' => $categoryName]));

                $accountCreateDto = new AccountCreateDto(
                    name:       $accountName,
                    login:      $login,
                    clientId:   $clientId,
                    categoryId: $categoryId,
                    pass:       $password,
                    url:        $url,
                    notes:      $notes
                );

                $this->addAccount($accountCreateDto, $importParamsDto);

                $this->eventDispatcher->notify(
                    'run.import.csv.process.account',
                    new Event(
                        $this,
                        EventMessage::factory()
                                    ->addDetail(__u('Account imported'), $accountName)
                                    ->addDetail(__u('Client'), $clientName)
                    )
                );
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notify(
                    'exception',
                    new Event(
                        $e,
                        EventMessage::factory()
                                    ->addDetail(__u('Error while importing the account'), $accountName)
                                    ->addDetail(__u('Error while processing line'), $line)
                    )
                );
            }
        }

        if ($line === 0) {
            throw ImportException::warning(__('No lines read from the file'));
        }
    }
}
