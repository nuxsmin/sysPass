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
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Exception;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Models\Account;
use SP\Domain\Category\Models\Category;
use SP\Domain\Client\Models\Client;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Export\Services\XmlVerify;
use SP\Domain\Import\Ports\ImportParams;
use SP\Domain\Import\Ports\ImportService;
use SP\Domain\Tag\Models\Tag;
use SP\Util\VersionUtil;

use function SP\__u;
use function SP\processException;

/**
 * Esta clase es la encargada de importar cuentas desde sysPass
 */
final class SyspassImport extends XmlImportBase
{
    /**
     * Iniciar la importación desde sysPass.
     *
     * @param ImportParams $importParams
     * @return ImportService
     * @throws ImportException
     */
    public function doImport(ImportParams $importParams): ImportService
    {
        try {
            $this->eventDispatcher->notify(
                'run.import.syspass',
                new Event($this, EventMessage::factory()->addDescription(__u('sysPass XML Import')))
            );

            $this->version = $this->getXmlVersion();

            if ($this->detectEncrypted()) {
                if ($importParams->getPassword() === '') {
                    throw ImportException::error(__u('Encryption password not set'));
                }

                $this->processEncrypted($importParams);
            }

            $this->checkIntegrity($importParams);

            $this->processCategories();

            if ($this->version >= VersionUtil::versionToInteger('3.0.0')) {
                $this->processClients();
            } else {
                $this->processCustomers();
            }

            $this->processTags();
            $this->processAccounts($importParams);

            return $this;
        } catch (ImportException $e) {
            throw $e;
        } catch (Exception $e) {
            throw ImportException::from($e);
        }
    }

    /**
     * Obtener la versión del XML
     */
    protected function getXmlVersion(): float|int
    {
        return VersionUtil::versionToInteger(
            (new DOMXPath($this->document))->query('/Root/Meta/Version')->item(0)?->nodeValue ?? 0
        );
    }

    /**
     * Verificar si existen datos encriptados
     */
    protected function detectEncrypted(): bool
    {
        return $this->document->getElementsByTagName('Encrypted')->length > 0;
    }

    /**
     * Procesar los datos encriptados y añadirlos al árbol DOM desencriptados
     *
     * @throws ImportException
     */
    protected function processEncrypted(ImportParams $importParams): void
    {
        $hash = $this->document
            ->getElementsByTagName('Encrypted')
            ->item(0)
            ?->getAttribute('hash');

        if (!empty($hash) && !Hash::checkHashKey($importParams->getPassword(), $hash)) {
            throw ImportException::error(__u('Wrong encryption password'));
        }

        /** @var DOMElement $node */
        foreach ($this->document->getElementsByTagName('Data') as $node) {
            try {
                if ($this->version >= 210 && $this->version <= 310) {
                    $encryptedNodeValue = base64_decode($node->nodeValue);
                } elseif ($this->version >= 320) {
                    $encryptedNodeValue = $node->nodeValue;
                } else {
                    throw ImportException::error(__u('The file was exported with an old sysPass version (<= 2.10).'));
                }

                $xmlDecrypted = $this->crypt->decrypt(
                    $encryptedNodeValue,
                    $node->getAttribute('key'),
                    $importParams->getPassword()
                );
            } catch (CryptException $e) {
                processException($e);

                $this->eventDispatcher->notify('exception', new Event($e));

                continue;
            }

            $document = new DOMDocument();

            if ($document->loadXML($xmlDecrypted) === false) {
                throw ImportException::error(__u('Wrong encryption password'));
            }

            $this->document->documentElement
                ->appendChild(
                    $this->document->importNode($document->documentElement, true)
                );
        }

        // Eliminar los datos encriptados tras desencriptar los mismos
        if ($this->document->getElementsByTagName('Data')->length > 0) {
            $nodeData = $this->document
                ->getElementsByTagName('Encrypted')
                ->item(0);
            $nodeData->parentNode->removeChild($nodeData);
        }

        $this->eventDispatcher->notify(
            'run.import.syspass.process.decryption',
            new Event($this, EventMessage::factory()->addDescription(__u('Data unencrypted')))
        );
    }

    /**
     * Checks XML file's data integrity using the signed hash
     */
    protected function checkIntegrity(ImportParams $importParams): void
    {
        $key = $importParams->getPassword() ?? sha1($this->configData->getPasswordSalt());

        if (!XmlVerify::checkXmlHash($this->document, $key)) {
            $this->eventDispatcher->notify(
                'run.import.syspass.process.verify',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Error while checking integrity hash'))
                        ->addDescription(
                            __u(
                                'If you are importing an exported file from the same origin, the data could be compromised.'
                            )
                        )
                )
            );
        }
    }

    /**
     * Obtener las categorías y añadirlas a sysPass.
     *
     * @throws ImportException
     */
    protected function processCategories(): void
    {
        $this->getNodesData(
            'Categories',
            'Category',
            function (DOMElement $category) {
                $nodesIterator = new CallbackFilterIterator(
                    $category->childNodes->getIterator(),
                    static fn(DOMElement $element) => isset($element->tagName)
                );

                $data = [];

                /** @var DOMElement $node */
                foreach ($nodesIterator as $node) {
                    $data[$node->tagName] = $node->nodeValue;
                }

                try {
                    $this->addCategory(new Category($data));

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.category',
                        new Event(
                            $this,
                            EventMessage::factory()
                                ->addDetail(__u('Category imported'), $data['name'])
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            }
        );
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     *
     * @throws ImportException
     */
    protected function processClients(): void
    {
        $this->getNodesData(
            'Clients',
            'Client',
            function (DOMElement $client) {
                $nodesIterator = new CallbackFilterIterator(
                    $client->childNodes->getIterator(),
                    static fn(DOMElement $element) => isset($element->tagName)
                );

                $data = [];

                /** @var DOMElement $node */
                foreach ($nodesIterator as $node) {
                    $data[$node->tagName] = $node->nodeValue;
                }

                try {
                    $this->addClient(new Client($data));

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.client',
                        new Event(
                            $this,
                            EventMessage::factory()
                                ->addDetail(__u('Client imported'), $data['name'])
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            }
        );
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     *
     * @throws ImportException
     * @deprecated
     */
    protected function processCustomers(): void
    {
        $this->getNodesData(
            'Customers',
            'Customer',
            function (DOMElement $client) {
                $nodesIterator = new CallbackFilterIterator(
                    $client->childNodes->getIterator(),
                    static fn(DOMElement $element) => isset($element->tagName)
                );

                $data = [];

                /** @var DOMElement $node */
                foreach ($nodesIterator as $node) {
                    $data[$node->tagName] = $node->nodeValue;
                }

                try {
                    $this->addClient(new Client($data));

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.customer',
                        new Event(
                            $this,
                            EventMessage::factory()->addDetail(__u('Client imported'), $data['name'])
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            }
        );
    }

    /**
     * Obtener las etiquetas y añadirlas a sysPass.
     *
     * @throws ImportException
     */
    protected function processTags(): void
    {
        $this->getNodesData(
            'Tags',
            'Tag',
            function (DOMElement $tag) {
                $nodesIterator = new CallbackFilterIterator(
                    $tag->childNodes->getIterator(),
                    static fn(DOMElement $element) => isset($element->tagName)
                );

                $data = [];

                /** @var DOMElement $node */
                foreach ($nodesIterator as $node) {
                    if ($node->tagName === 'name') {
                        $data['name'] = $node->nodeValue;
                        $data['id'] = $node->getAttribute('id');
                    }
                }

                try {
                    $this->addTag(new Tag($data));

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.tag',
                        new Event(
                            $this,
                            EventMessage::factory()->addDetail(__u('Tag imported'), $data['name'])
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            },
            false
        );
    }

    /**
     * Obtener los datos de las cuentas de sysPass y crearlas.
     *
     * @throws ImportException
     */
    protected function processAccounts(ImportParams $importParams): void
    {
        $this->getNodesData(
            'Accounts',
            'Account',
            function (DOMElement $account) use ($importParams) {
                $data = [];

                $nodesIterator = new CallbackFilterIterator(
                    $account->childNodes->getIterator(),
                    static fn(DOMElement $element) => isset($element->tagName)
                );

                /** @var DOMElement $node */
                foreach ($nodesIterator as $node) {
                    switch ($node->tagName) {
                        case 'categoryId':
                            $data['categoryId'] =
                                $this->getOrSetCache(self::ITEM_CATEGORY, (int)$node->nodeValue);
                            break;
                        case 'clientId':
                        case 'customerId':
                            $data['clientId'] =
                                $this->getOrSetCache(self::ITEM_CLIENT, (int)$node->nodeValue);
                            break;
                        case 'tags':
                            $data['tags'] = $this->processAccountTags($node->childNodes);
                            break;
                        default:
                            $data[$node->tagName] = $node->nodeValue;
                    }
                }

                try {
                    $this->addAccount(AccountCreateDto::fromAccount(new Account($data)), $importParams);

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.account',
                        new Event(
                            $this,
                            EventMessage::factory()->addDetail(__u('Account imported'), $data['name'])
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            }
        );
    }

    /**
     * Procesar las etiquetas de la cuenta
     */
    protected function processAccountTags(DOMNodeList $nodes): array
    {
        $tags = [];

        $nodesIterator = new CallbackFilterIterator(
            $nodes->getIterator(),
            static fn(DOMElement $element) => isset($element->tagName)
        );

        /** @var DOMElement $node */
        foreach ($nodesIterator as $node) {
            $tags[] = $this->getOrSetCache(self::ITEM_TAG, (int)$node->getAttribute('id'));
        }

        return $tags;
    }
}
