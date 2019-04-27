<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Services\Export;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Config\ConfigData;
use SP\Core\AppInfoInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\CheckException;
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
    /**
     * @var ConfigData
     */
    private $configData;
    /**
     * @var
     */
    private $extensionChecker;
    /**
     * @var DOMDocument
     */
    private $xml;
    /**
     * @var DOMElement
     */
    private $root;
    /**
     * @var string
     */
    private $exportPass;
    /**
     * @var bool
     */
    private $encrypted = false;
    /**
     * @var string
     */
    private $exportPath;
    /**
     * @var string
     */
    private $exportFile;

    /**
     * Realiza la exportación de las cuentas a XML
     *
     * @param string $exportPath
     * @param string $pass string La clave de exportación
     *
     * @throws ServiceException
     * @throws FileException
     */
    public function doExport(string $exportPath, string $pass = null)
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
     * @param string $exportPath
     *
     * @throws ServiceException
     */
    private function setExportPath(string $exportPath)
    {
        if (!is_dir($exportPath)
            && @mkdir($exportPath, 0700, true) === false
        ) {
            throw new ServiceException(sprintf(__('Unable to create the directory (%s)'), $exportPath));
        }

        $this->exportPath = $exportPath;
    }

    /**
     * Genera el nombre del archivo usado para la exportación.
     *
     * @return string
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

    /**
     * @param string $path
     * @param string $hash
     * @param bool   $compressed
     *
     * @return string
     */
    public static function getExportFilename(string $path, string $hash, bool $compressed = false)
    {
        $file = $path . DIRECTORY_SEPARATOR . AppInfoInterface::APP_NAME . '_export-' . $hash;

        if ($compressed) {
            return $file . ArchiveHandler::COMPRESS_EXTENSION;
        }

        return $file . '.xml';
    }

    /**
     * Eliminar los archivos de exportación anteriores
     */
    private function deleteOldExports()
    {
        $path = $this->exportPath . DIRECTORY_SEPARATOR . AppInfoInterface::APP_NAME;

        array_map(function ($file) {
            return @unlink($file);
        }, array_merge(glob($path . '_export-*'), glob($path . '*.xml')));
    }

    /**
     * Crear el documento XML y guardarlo
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ServiceException
     */
    private function makeXML()
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
                ServiceException::ERROR,
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
    private function createRoot()
    {
        try {
            $this->xml = new DOMDocument('1.0', 'UTF-8');
            $this->root = $this->xml->appendChild($this->xml->createElement('Root'));
        } catch (Exception $e) {
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }

    /**
     * Crear el nodo con metainformación del archivo XML
     *
     * @throws ServiceException
     */
    private function createMeta()
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
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }

    /**
     * Crear el nodo con los datos de las categorías
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ServiceException
     */
    private function createCategories()
    {
        try {
            $this->eventDispatcher->notifyEvent('run.export.process.category',
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
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }

    /**
     * Añadir un nuevo nodo al árbol raíz
     *
     * @param DOMElement $node El nodo a añadir
     *
     * @throws ServiceException
     */
    private function appendNode(DOMElement $node)
    {
        try {
            // Si se utiliza clave de encriptación los datos se encriptan en un nuevo nodo:
            // Encrypted -> Data
            if ($this->encrypted === true) {
                // Obtener el nodo en formato XML
                $nodeXML = $this->xml->saveXML($node);

                // Crear los datos encriptados con la información del nodo
                $securedKey = Crypt::makeSecuredKey($this->exportPass);
                $encrypted = Crypt::encrypt($nodeXML, $securedKey, $this->exportPass);

                // Buscar si existe ya un nodo para el conjunto de datos encriptados
                $encryptedNode = $this->root->getElementsByTagName('Encrypted')->item(0);

                if (!$encryptedNode instanceof DOMElement) {
                    $encryptedNode = $this->xml->createElement('Encrypted');
                    $encryptedNode->setAttribute('hash', Hash::hashKey($this->exportPass));
                }

                // Crear el nodo hijo con los datos encriptados
                $encryptedData = $this->xml->createElement('Data', base64_encode($encrypted));

                $encryptedDataIV = $this->xml->createAttribute('key');
                $encryptedDataIV->value = $securedKey;

                // Añadir nodos de datos
                $encryptedData->appendChild($encryptedDataIV);
                $encryptedNode->appendChild($encryptedData);

                // Añadir el nodo encriptado
                $this->root->appendChild($encryptedNode);
            } else {
                $this->root->appendChild($node);
            }
        } catch (Exception $e) {
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }

    /**
     * Escapar carácteres no válidos en XML
     *
     * @param $data string Los datos a escapar
     *
     * @return mixed
     */
    private function escapeChars($data)
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
    private function createClients()
    {
        try {
            $this->eventDispatcher->notifyEvent('run.export.process.client',
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
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }

    /**
     * Crear el nodo con los datos de las etiquetas
     *
     * @throws ServiceException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createTags()
    {
        try {
            $this->eventDispatcher->notifyEvent('run.export.process.tag',
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
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }

    /**
     * Crear el nodo con los datos de las cuentas
     *
     * @throws ServiceException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createAccounts()
    {
        try {
            $this->eventDispatcher->notifyEvent('run.export.process.account',
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
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }

    /**
     * Crear el hash del archivo XML e insertarlo en el árbol DOM
     *
     * @throws ServiceException
     */
    private function createHash()
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
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }

    /**
     * @param DOMDocument $document
     *
     * @return string
     */
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
    private function writeXML()
    {
        try {
            $this->xml->formatOutput = true;
            $this->xml->preserveWhiteSpace = false;

            if (!$this->xml->save($this->exportFile)) {
                throw new ServiceException(__u('Error while creating the XML file'));
            }
        } catch (Exception $e) {
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }

    /**
     * @throws CheckException
     * @throws FileException
     */
    public function createArchive()
    {
        $archive = new ArchiveHandler($this->exportFile, $this->extensionChecker);
        $archive->compressFile($this->exportFile);

        $file = new FileHandler($this->exportFile);
        $file->delete();
    }

    /**
     * @return string
     */
    public function getExportFile(): string
    {
        return $this->exportFile;
    }

    /**
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->extensionChecker = $this->dic->get(PhpExtensionChecker::class);
        $this->configData = $this->config->getConfigData();
    }

    /**
     * Devuelve el código XML de un nodo
     *
     * @param $node string El nodo a devolver
     *
     * @return string
     * @throws ServiceException
     */
    private function getNodeXML($node)
    {
        try {
            return $this->xml->saveXML($this->root->getElementsByTagName($node)->item(0));
        } catch (Exception $e) {
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, __FUNCTION__);
        }
    }
}