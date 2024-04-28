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

namespace SP\Domain\Import\Services;

use CallbackFilterIterator;
use DOMElement;
use DOMXPath;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Category\Models\Category;
use SP\Domain\Client\Models\Client;
use SP\Domain\Common\Providers\Filter;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ItemsImportService;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SplObjectStorage;

use function SP\__u;
use function SP\processException;

/**
 * Class KeepassImport
 */
final class KeepassImport extends XmlImportBase implements ItemsImportService
{
    /**
     * @var SplObjectStorage<AccountCreateDto>[] $items
     */
    private array $entries = [];

    /**
     * Iniciar la importación desde KeePass
     *
     * @param ImportParamsDto $importParams
     * @return ItemsImportService
     * @throws SPException
     */
    public function doImport(ImportParamsDto $importParams): ItemsImportService
    {
        $this->eventDispatcher->notify(
            'run.import.keepass',
            new Event($this, EventMessage::factory()->addDescription(__u('KeePass XML Import')))
        );

        $this->process($importParams);

        return $this;
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @throws SPException
     */
    private function process(ImportParamsDto $importParamsDto): void
    {
        $clientId = $this->addClient(new Client(['name' => 'KeePass']));

        $this->eventDispatcher->notify(
            'run.import.keepass.process.client',
            new Event($this, EventMessage::factory()->addDetail(__u('Client added'), 'KeePass'))
        );

        $this->getGroups();
        $this->getEntries();

        foreach ($this->entries as $groupName => $accounts) {
            $this->processAccounts($accounts, $clientId, $importParamsDto, $groupName);
        }
    }


    /**
     * @throws DuplicatedItemException
     * @throws SPException
     */
    private function getGroups(): void
    {
        $tags = (new DOMXPath($this->document))->query('/KeePassFile/Root//Group/Name');

        $nodesList = new CallbackFilterIterator(
            $tags->getIterator(),
            static fn(DOMElement $node) => $node->nodeType === XML_ELEMENT_NODE
        );

        /** @var DOMElement $tag */
        foreach ($nodesList as $tag) {
            $this->setItem($tag->childNodes->item(0)->nodeValue);
        }
    }

    /**
     * @throws DuplicatedItemException
     * @throws SPException
     */
    private function setItem(string $groupName): void
    {
        $groupName = Filter::getString($groupName);

        if (!isset($this->entries[$groupName])) {
            $this->addCategory(new Category(['name' => $groupName, 'description' => 'KeePass']));

            $this->eventDispatcher->notify(
                'run.import.keepass.process.category',
                new Event($this, EventMessage::factory()->addDetail(__u('Category imported'), $groupName))
            );

            $this->entries[$groupName] = new SplObjectStorage();
        }
    }

    /**
     * Gets the entries found
     */
    private function getEntries(): void
    {
        $DOMXPath = new DOMXPath($this->document);
        $entries = $DOMXPath->query('/KeePassFile/Root/Group//Entry[not(parent::History)]');

        $nodesList = new CallbackFilterIterator(
            $entries->getIterator(),
            static fn(DOMElement $node) => $node->nodeType === XML_ELEMENT_NODE
        );

        /** @var DOMElement $entry */
        foreach ($nodesList as $entry) {
            $path = $entry->getNodePath();
            $entryData = [];

            /** @var DOMElement $string */
            foreach ($DOMXPath->query($path . '/String') as $string) {
                $key = $string->childNodes->item(0)->nodeValue;
                $value = $string->childNodes->item(1)->nodeValue;

                $entryData[$key] = $value;
            }

            $groupName = $DOMXPath->query($path . '/../Name')->item(0)->nodeValue;

            $this->getEntryFor($groupName)?->attach($this->mapEntryToAccount($entryData, $groupName));
        }
    }

    private function getEntryFor(string $groupName): ?SplObjectStorage
    {
        if (array_key_exists($groupName, $this->entries)) {
            return $this->entries[$groupName];
        }

        return null;
    }

    private function mapEntryToAccount(array $entry, string $groupName): AccountCreateDto
    {
        return new AccountCreateDto(
            name:       Filter::getString($entry['Title'] ?? ''),
            login:      Filter::getString($entry['UserName'] ?? ''),
            categoryId: $this->getOrSetCache(self::ITEM_CATEGORY, $groupName),
            pass:       $entry['Password'] ?? '',
            url:        Filter::getString($entry['URL'] ?? ''),
            notes:      Filter::getString($entry['Notes'] ?? '')
        );
    }

    private function processAccounts(
        SplObjectStorage $accounts,
        int              $clientId,
        ImportParamsDto  $importParamsDto,
        string           $groupName
    ): void {
        foreach ($accounts as $account) {
            try {
                $this->addAccount($account->set('clientId', $clientId), $importParamsDto);

                $this->eventDispatcher->notify(
                    'run.import.keepass.process.account',
                    new Event(
                        $this,
                        EventMessage::factory()
                                    ->addDetail(__u('Account imported'), $account->getName())
                                    ->addDetail(__u('Category'), $groupName)
                    )
                );
            } catch (SPException $e) {
                processException($e);

                $this->eventDispatcher->notify('exception', new Event($e));
            }
        }
    }

}
