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

namespace SP\Domain\Export\Services;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Export\Ports\XmlAccountExportService;
use SP\Domain\Export\Ports\XmlCategoryExportService;
use SP\Domain\Export\Ports\XmlClientExportService;
use SP\Domain\Export\Ports\XmlExportService;
use SP\Domain\Export\Ports\XmlTagExportService;
use SP\Domain\File\Ports\DirectoryHandlerService;
use SP\Infrastructure\File\ArchiveHandler;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileSystem;

use function SP\__u;

/**
 * Class XmlExport
 */
final class XmlExport extends Service implements XmlExportService
{
    use XmlTrait;

    private ConfigDataInterface $configData;
    private DOMDocument $document;

    /**
     * @throws ServiceException
     */
    public function __construct(
        Application                                 $application,
        private readonly PhpExtensionCheckerService $extensionChecker,
        private readonly XmlClientExportService     $xmlClientExportService,
        private readonly XmlAccountExportService    $xmlAccountExportService,
        private readonly XmlCategoryExportService   $xmlCategoryExportService,
        private readonly XmlTagExportService        $xmlTagExportService,
        private readonly CryptInterface             $crypt
    ) {
        parent::__construct($application);

        $this->configData = $this->config->getConfigData();

        $this->createDocument();
    }

    /**
     * @throws ServiceException
     */
    private function createDocument(): void
    {
        try {
            $this->document = new DOMDocument('1.0', 'UTF-8');
            $this->document->formatOutput = true;
            $this->document->preserveWhiteSpace = false;

            $this->document->appendChild($this->document->createElement('Root'));
        } catch (Exception $e) {
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * @inheritDoc
     * @throws CheckException
     */
    public function export(DirectoryHandlerService $exportPath, ?string $password = null): string
    {
        set_time_limit(0);

        $exportPath->checkOrCreate();

        self::deleteExportFiles($exportPath->getPath());

        $file = self::buildFilename($exportPath->getPath(), $this->buildAndSaveHashForFile());
        $this->buildAndSaveXml($file, $password);

        return $file;
    }

    private static function deleteExportFiles(string $path): void
    {
        $path = FileSystem::buildPath($path, AppInfoInterface::APP_NAME);

        array_map(
            static fn($file) => @unlink($file),
            array_merge(glob($path . '_export-*'), glob($path . '*.xml'))
        );
    }

    public static function buildFilename(string $path, string $hash, bool $compressed = false): string
    {
        $file = sprintf('%s%s%s_export-%s', $path, DIRECTORY_SEPARATOR, AppInfoInterface::APP_NAME, $hash);

        if ($compressed) {
            return $file . ArchiveHandler::COMPRESS_EXTENSION;
        }

        return sprintf('%s.xml', $file);
    }

    /**
     * @throws FileException
     */
    private function buildAndSaveHashForFile(): string
    {
        $hash = sha1(uniqid('sysPassExport', true));
        $this->configData->setExportHash($hash);
        $this->config->save($this->configData);

        return $hash;
    }

    /**
     * @throws ServiceException
     */
    private function buildAndSaveXml(string $file, ?string $password = null): void
    {
        try {
            $this->appendMeta();
            $this->appendNode($this->xmlCategoryExportService->export(), $password);
            $this->appendNode($this->xmlClientExportService->export(), $password);
            $this->appendNode($this->xmlTagExportService->export(), $password);
            $this->appendNode($this->xmlAccountExportService->export(), $password);
            $this->appendHash($password);

            if (!$this->document->save($file)) {
                throw ServiceException::error(__u('Error while creating the XML file'));
            }
        } catch (ServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            throw ServiceException::error(
                __u('Error while exporting'),
                __u('Please check out the event log for more details'),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws ServiceException
     */
    private function appendMeta(): void
    {
        try {
            $userData = $this->context->getUserData();

            $nodeMeta = $this->document->createElement('Meta');
            $nodeMeta->append(
                $this->document->createElement('Generator', 'sysPass'),
                $this->document->createElement('Version', Version::getVersionStringNormalized()),
                $this->document->createElement('Time', (string)time()),
                $this->document->createElement(
                    'User',
                    $this->document->createTextNode($userData->getLogin())->nodeValue
                ),
                $this->document->createElement(
                    'Group',
                    $this->document->createTextNode($userData->getUserGroupName())->nodeValue
                )
            );

            $this->document->documentElement->appendChild($nodeMeta);
        } catch (Exception $e) {
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * @throws ServiceException
     */
    private function appendNode(DOMElement $node, ?string $password = null): void
    {
        try {
            $selfNode = $this->document->importNode($node, true);

            if (!empty($password)) {
                $securedKey = $this->crypt->makeSecuredKey($password, false);
                $encrypted = $this->crypt->encrypt(
                    $this->document->saveXML($selfNode),
                    $securedKey->unlockKey($password)
                );

                $encryptedData = $this->document->createElement('Data', $encrypted);
                $encryptedData->setAttribute('key', $securedKey->saveToAsciiSafeString());

                $newNode = $this->getEncryptedNode($password);

                $newNode->appendChild($encryptedData);

                $this->document->documentElement->appendChild($newNode);
            } else {
                $this->document->documentElement->appendChild($selfNode);
            }
        } catch (Exception $e) {
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * @param string $password
     * @return DOMElement|DOMNode|false|null
     * @throws DOMException
     */
    private function getEncryptedNode(string $password): DOMElement|null|false|DOMNode
    {
        $encryptedNode = $this->document->documentElement->getElementsByTagName('Encrypted');

        if ($encryptedNode->length === 0) {
            $node = $this->document->createElement('Encrypted');
            $node->setAttribute('hash', Hash::hashKey($password));

            return $node;
        }

        return $encryptedNode->item(0);
    }

    /**
     * @throws ServiceException
     */
    private function appendHash(?string $password = null): void
    {
        try {
            $hash = self::generateHashFromNodes($this->document);
            $key = $password ?: sha1($this->configData->getPasswordSalt());

            $hashNode = $this->document->createElement('Hash', $hash);
            $hashNode->setAttribute('sign', Hash::signMessage($hash, $key));

            $this->document
                ->documentElement
                ->getElementsByTagName('Meta')
                ->item(0)
                ->appendChild($hashNode);
        } catch (Exception $e) {
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }
}
