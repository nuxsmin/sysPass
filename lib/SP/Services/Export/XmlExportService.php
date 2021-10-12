<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Export;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Config\ConfigDataInterface;
use SP\Core\AppInfoInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\CheckException;
use SP\Core\Exceptions\SPException;
use SP\Core\PhpExtensionChecker;
use SP\DataModel\CategoryData;
use SP\Services\Account\AccountService;
use SP\Services\Account\AccountToTagService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\Tag\TagService;
use SP\Storage\File\ArchiveHandler;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;
use SP\Util\VersionUtil;

defined('APP_ROOT') || die();

/**
 * Clase XmlExport para realizar la exportación de las cuentas de sysPass a formato XML
 *
 * @package SP
 */
final class XmlExportService extends Service
{
    private ?ConfigDataInterface $configData = null;
    private ?PhpExtensionChecker $extensionChecker = null;
    private ?DOMDocument $xml = null;
    private ?DOMElement $root = null;
    private ?string $exportPass = null;
    private bool $encrypted = false;
    private ?string $exportPath = null;
    private ?string $exportFile = null;

    /**
     * Realiza la exportación de las cuentas a XML
     *
     * @param string      $exportPath
     * @param string|null $pass La clave de exportación
     *
     * @throws \SP\Services\ServiceException
     * @throws \SP\Storage\File\FileException
     */
    public function doExport(string $exportPath, ?string $pass = null): void
    {
        set_time_limit(0);

        if (!empty($pass)) {
            $this->exportPass = $pass;
            $this->encrypted = true;
        }

        $this->setExportPath($exportPath);
        $this->exportFile = $this->generateExportFilename();
        $this->deleteOldExports();
        $this->makeXML();
    }

    /**
     * @throws ServiceException
     */
    private function setExportPath(string $exportPath): void
    {
        if (!is_dir($exportPath)
            && !mkdir($exportPath, 0700, true)
            && !is_dir($exportPath)
        ) {
            throw new ServiceException(sprintf(
                __('Unable to create the directory (%s)'),
                $exportPath
            ));
        }

        $this->exportPath = $exportPath;
    }

    /**
     * Genera el nombre del archivo usado para la exportación.
     *
     * @throws FileException
     */
    private function generateExportFilename(): string
    {
        // Generar hash unico para evitar descargas no permitidas
        $hash = sha1(uniqid('sysPassExport', true));
        $this->configData->setExportHash($hash);
        $this->config->saveConfig($this->configData);

        return self::getExportFilename($this->exportPath, $hash);
    }

    public static function getExportFilename(
        string $path,
        string $hash,
        bool   $compressed = false
    ): string
    {
        $file = sprintf(
            '%s%s%s_export-%s',
            $path,
            DIRECTORY_SEPARATOR,
            AppInfoInterface::APP_NAME,
            $hash
        );

        if ($compressed) {
            return $file . ArchiveHandler::COMPRESS_EXTENSION;
        }

        return sprintf('%s.xml', $file);
    }

    /**
     * Eliminar los archivos de exportación anteriores
     */
    private function deleteOldExports(): void
    {
        $path = $this->exportPath . DIRECTORY_SEPARATOR . AppInfoInterface::APP_NAME;

        array_map(
            static function ($file) {
                return @unlink($file);
            },
            array_merge(
                glob($path . '_export-*'),
                glob($path . '*.xml')
            )
        );
    }

    /**
     * Crear el documento XML y guardarlo
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ServiceException
     */
    private function makeXML(): void
    {
        try {
            $this->createRoot();
            $this->createMeta();
            $this->createCategories();
            $this->createClients();
            $this->createTags();
            $this->createAccounts();
            $this->createHash();
            $this->writeXML();
        } catch (ServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ServiceException(
                __u('Error while exporting'),
                SPException::ERROR,
                __u('Please check out the event log for more details'),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Crear el nodo raíz
     *
     * @throws ServiceException
     */
    private function createRoot(): void
    {
        try {
            $this->xml = new DOMDocument('1.0', 'UTF-8');
            $this->root = $this->xml->appendChild($this->xml->createElement('Root'));
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                __FUNCTION__
            );
        }
    }

    /**
     * Crear el nodo con metainformación del archivo XML
     *
     * @throws ServiceException
     */
    private function createMeta(): void
    {
        try {
            $userData = $this->context->getUserData();

            $nodeMeta = $this->xml->createElement('Meta');
            $metaGenerator = $this->xml->createElement('Generator', 'sysPass');
            $metaVersion = $this->xml->createElement('Version', VersionUtil::getVersionStringNormalized());
            $metaTime = $this->xml->createElement('Time', time());
            $metaUser = $this->xml->createElement('User', $userData->getLogin());
            $metaUser->setAttribute('id', $userData->getId());
            $metaGroup = $this->xml->createElement('Group', $userData->getUserGroupName());
            $metaGroup->setAttribute('id', $userData->getUserGroupId());

            $nodeMeta->appendChild($metaGenerator);
            $nodeMeta->appendChild($metaVersion);
            $nodeMeta->appendChild($metaTime);
            $nodeMeta->appendChild($metaUser);
            $nodeMeta->appendChild($metaGroup);

            $this->root->appendChild($nodeMeta);
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                __FUNCTION__
            );
        }
    }

    /**
     * Crear el nodo con los datos de las categorías
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ServiceException
     */
    private function createCategories(): void
    {
        try {
            $this->eventDispatcher->notifyEvent(
                'run.export.process.category',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Exporting categories')))
            );

            $categoryService = $this->dic->get(CategoryService::class);
            $categories = $categoryService->getAllBasic();

            // Crear el nodo de categorías
            $nodeCategories = $this->xml->createElement('Categories');

            if (count($categories) === 0) {
                $this->appendNode($nodeCategories);

                return;
            }

            foreach ($categories as $category) {
                /** @var $category CategoryData */
                $categoryName = $this->xml->createElement('name', $this->escapeChars($category->getName()));
                $categoryDescription = $this->xml->createElement('description', $this->escapeChars($category->getDescription()));

                // Crear el nodo de categoría
                $nodeCategory = $this->xml->createElement('Category');
                $nodeCategory->setAttribute('id', $category->getId());
                $nodeCategory->appendChild($categoryName);
                $nodeCategory->appendChild($categoryDescription);

                // Añadir categoría al nodo de categorías
                $nodeCategories->appendChild($nodeCategory);
            }

            $this->appendNode($nodeCategories);
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                __FUNCTION__
            );
        }
    }

    /**
     * Añadir un nuevo nodo al árbol raíz
     *
     * @param DOMElement $node El nodo a añadir
     *
     * @throws ServiceException
     */
    private function appendNode(DOMElement $node): void
    {
        try {
            // Si se utiliza clave de encriptación los datos se encriptan en un nuevo nodo:
            // Encrypted -> Data
            if ($this->encrypted === true) {
                // Obtener el nodo en formato XML
                $nodeXML = $this->xml->saveXML($node);

                // Crear los datos encriptados con la información del nodo
                $securedKey = Crypt::makeSecuredKey($this->exportPass, false);
                $encrypted = Crypt::encrypt($nodeXML, $securedKey->unlockKey($this->exportPass));

                // Crear el nodo hijo con los datos encriptados
                $encryptedData = $this->xml->createElement('Data', $encrypted);

                $encryptedDataKey = $this->xml->createAttribute('key');
                $encryptedDataKey->value = $securedKey->saveToAsciiSafeString();

                // Añadir nodos de datos
                $encryptedData->appendChild($encryptedDataKey);

                // Buscar si existe ya un nodo para el conjunto de datos encriptados
                $encryptedNode = $this->root->getElementsByTagName('Encrypted');

                if ($encryptedNode->length === 0) {
                    $newNode = $this->xml->createElement('Encrypted');
                    $newNode->setAttribute('hash', Hash::hashKey($this->exportPass));
                } else {
                    $newNode = $encryptedNode->item(0);
                }

                $newNode->appendChild($encryptedData);

                // Añadir el nodo encriptado
                $this->root->appendChild($newNode);
            } else {
                $this->root->appendChild($node);
            }
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                __FUNCTION__
            );
        }
    }

    /**
     * Escapar carácteres no válidos en XML
     *
     * @param $data string Los datos a escapar
     *
     * @return string
     */
    private function escapeChars(string $data): string
    {
        $arrStrFrom = ['&', '<', '>', '"', '\''];
        $arrStrTo = ['&#38;', '&#60;', '&#62;', '&#34;', '&#39;'];

        return str_replace($arrStrFrom, $arrStrTo, $data);
    }

    /**
     * Crear el nodo con los datos de los clientes
     *
     * @throws ServiceException
     * @throws ServiceException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createClients(): void
    {
        try {
            $this->eventDispatcher->notifyEvent(
                'run.export.process.client',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Exporting clients')))
            );

            $clientService = $this->dic->get(ClientService::class);
            $clients = $clientService->getAllBasic();

            // Crear el nodo de clientes
            $nodeClients = $this->xml->createElement('Clients');

            if (count($clients) === 0) {
                $this->appendNode($nodeClients);
                return;
            }

            foreach ($clients as $client) {
                $clientName = $this->xml->createElement('name', $this->escapeChars($client->getName()));
                $clientDescription = $this->xml->createElement('description', $this->escapeChars($client->getDescription()));

                // Crear el nodo de clientes
                $nodeClient = $this->xml->createElement('Client');
                $nodeClient->setAttribute('id', $client->getId());
                $nodeClient->appendChild($clientName);
                $nodeClient->appendChild($clientDescription);

                // Añadir cliente al nodo de clientes
                $nodeClients->appendChild($nodeClient);
            }

            $this->appendNode($nodeClients);
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                __FUNCTION__
            );
        }
    }

    /**
     * Crear el nodo con los datos de las etiquetas
     *
     * @throws ServiceException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createTags(): void
    {
        try {
            $this->eventDispatcher->notifyEvent(
                'run.export.process.tag',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Exporting tags')))
            );

            $tagService = $this->dic->get(TagService::class);
            $tags = $tagService->getAllBasic();

            // Crear el nodo de etiquetas
            $nodeTags = $this->xml->createElement('Tags');

            if (count($tags) === 0) {
                $this->appendNode($nodeTags);
                return;
            }

            foreach ($tags as $tag) {
                $tagName = $this->xml->createElement('name', $this->escapeChars($tag->getName()));

                // Crear el nodo de etiquetas
                $nodeTag = $this->xml->createElement('Tag');
                $nodeTag->setAttribute('id', $tag->getId());
                $nodeTag->appendChild($tagName);

                // Añadir etiqueta al nodo de etiquetas
                $nodeTags->appendChild($nodeTag);
            }

            $this->appendNode($nodeTags);
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                __FUNCTION__
            );
        }
    }

    /**
     * Crear el nodo con los datos de las cuentas
     *
     * @throws ServiceException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createAccounts(): void
    {
        try {
            $this->eventDispatcher->notifyEvent(
                'run.export.process.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Exporting accounts')))
            );

            $accountService = $this->dic->get(AccountService::class);
            $accountToTagService = $this->dic->get(AccountToTagService::class);
            $accounts = $accountService->getAllBasic();

            // Crear el nodo de cuentas
            $nodeAccounts = $this->xml->createElement('Accounts');

            if (count($accounts) === 0) {
                $this->appendNode($nodeAccounts);
                return;
            }

            foreach ($accounts as $account) {
                $accountName = $this->xml->createElement('name', $this->escapeChars($account->getName()));
                $accountCustomerId = $this->xml->createElement('clientId', $account->getClientId());
                $accountCategoryId = $this->xml->createElement('categoryId', $account->getCategoryId());
                $accountLogin = $this->xml->createElement('login', $this->escapeChars($account->getLogin()));
                $accountUrl = $this->xml->createElement('url', $this->escapeChars($account->getUrl()));
                $accountNotes = $this->xml->createElement('notes', $this->escapeChars($account->getNotes()));
                $accountPass = $this->xml->createElement('pass', $this->escapeChars($account->getPass()));
                $accountIV = $this->xml->createElement('key', $this->escapeChars($account->getKey()));
                $tags = $this->xml->createElement('tags');

                foreach ($accountToTagService->getTagsByAccountId($account->getId()) as $itemData) {
                    $tag = $this->xml->createElement('tag');
                    $tag->setAttribute('id', $itemData->getId());

                    $tags->appendChild($tag);
                }

                // Crear el nodo de cuenta
                $nodeAccount = $this->xml->createElement('Account');
                $nodeAccount->setAttribute('id', $account->getId());
                $nodeAccount->appendChild($accountName);
                $nodeAccount->appendChild($accountCustomerId);
                $nodeAccount->appendChild($accountCategoryId);
                $nodeAccount->appendChild($accountLogin);
                $nodeAccount->appendChild($accountUrl);
                $nodeAccount->appendChild($accountNotes);
                $nodeAccount->appendChild($accountPass);
                $nodeAccount->appendChild($accountIV);
                $nodeAccount->appendChild($tags);

                // Añadir cuenta al nodo de cuentas
                $nodeAccounts->appendChild($nodeAccount);
            }

            $this->appendNode($nodeAccounts);
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                __FUNCTION__
            );
        }
    }

    /**
     * Crear el hash del archivo XML e insertarlo en el árbol DOM
     *
     * @throws ServiceException
     */
    private function createHash(): void
    {
        try {
            $hash = self::generateHashFromNodes($this->xml);

            $hashNode = $this->xml->createElement('Hash', $hash);
            $hashNode->appendChild($this->xml->createAttribute('sign'));

            $key = $this->exportPass ?: sha1($this->configData->getPasswordSalt());

            $hashNode->setAttribute('sign', Hash::signMessage($hash, $key));

            $this->root
                ->getElementsByTagName('Meta')
                ->item(0)
                ->appendChild($hashNode);
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                __FUNCTION__
            );
        }
    }

    public static function generateHashFromNodes(DOMDocument $document): string
    {
        $data = '';

        foreach ((new DOMXPath($document))->query('/Root/*[not(self::Meta)]') as $node) {
            $data .= $document->saveXML($node);
        }

        return sha1($data);
    }

    /**
     * Generar el archivo XML
     *
     * @throws ServiceException
     */
    private function writeXML(): void
    {
        try {
            $this->xml->formatOutput = true;
            $this->xml->preserveWhiteSpace = false;

            if (!$this->xml->save($this->exportFile)) {
                throw new ServiceException(__u('Error while creating the XML file'));
            }
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                __FUNCTION__
            );
        }
    }

    /**
     * @throws CheckException
     * @throws FileException
     */
    public function createArchive(): void
    {
        $archive = new ArchiveHandler($this->exportFile, $this->extensionChecker);
        $archive->compressFile($this->exportFile);

        $file = new FileHandler($this->exportFile);
        $file->delete();
    }

    public function getExportFile(): string
    {
        return $this->exportFile;
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize(): void
    {
        $this->extensionChecker = $this->dic->get(PhpExtensionChecker::class);
        $this->configData = $this->config->getConfigData();
    }
}