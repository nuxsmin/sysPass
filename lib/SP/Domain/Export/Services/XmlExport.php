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

namespace SP\Domain\Export\Services;

use DOMDocument;
use DOMElement;
use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
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
use SP\Util\FileUtil;
use SP\Util\VersionUtil;

use function SP\__u;

/**
 * Class XmlExportService
 */
final class XmlExport extends Service implements XmlExportService
{
    use XmlTrait;

    private ConfigDataInterface $configData;
    private DOMDocument         $xml;
    private DOMElement          $root;

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

        $this->createRoot();
    }

    /**
     * @throws ServiceException
     */
    private function createRoot(): void
    {
        try {
            $this->xml = new DOMDocument('1.0', 'UTF-8');
            $this->xml->formatOutput = true;
            $this->xml->preserveWhiteSpace = false;
            $this->root = $this->xml->appendChild($this->xml->createElement('Root'));
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
        $path = FileUtil::buildPath($path, AppInfoInterface::APP_NAME);

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
            $this->appendNode($this->xmlCategoryExportService->export($this->xml), $password);
            $this->appendNode($this->xmlClientExportService->export($this->xml), $password);
            $this->appendNode($this->xmlTagExportService->export($this->xml), $password);
            $this->appendNode($this->xmlAccountExportService->export($this->xml), $password);
            $this->appendHash($password);

            if (!$this->xml->save($file)) {
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

            $nodeMeta = $this->xml->createElement('Meta');

            $nodeMeta->appendChild($this->xml->createElement('Generator', 'sysPass'));
            $nodeMeta->appendChild($this->xml->createElement('Version', VersionUtil::getVersionStringNormalized()));
            $nodeMeta->appendChild($this->xml->createElement('Time', time()));
            $nodeMeta->appendChild($this->xml->createElement('User', $userData->getLogin()));
            $nodeMeta->appendChild($this->xml->createElement('Group', $userData->getUserGroupName()));

            $this->root->appendChild($nodeMeta);
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
            if (!empty($password)) {
                $securedKey = $this->crypt->makeSecuredKey($password, false);
                $encrypted = $this->crypt->encrypt($this->xml->saveXML($node), $securedKey->unlockKey($password));

                $encryptedData = $this->xml->createElement('Data', $encrypted);

                $encryptedDataKey = $this->xml->createAttribute('key');
                $encryptedDataKey->value = $securedKey->saveToAsciiSafeString();

                $encryptedData->appendChild($encryptedDataKey);

                $encryptedNode = $this->root->getElementsByTagName('Encrypted');

                if ($encryptedNode->length === 0) {
                    $newNode = $this->xml->createElement('Encrypted');
                    $newNode->setAttribute('hash', Hash::hashKey($password));
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
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * @throws ServiceException
     */
    private function appendHash(?string $password = null): void
    {
        try {
            $hash = self::generateHashFromNodes($this->xml);

            $hashNode = $this->xml->createElement('Hash', $hash);
            $hashNode->appendChild($this->xml->createAttribute('sign'));

            $key = $password ?: sha1($this->configData->getPasswordSalt());

            $hashNode->setAttribute('sign', Hash::signMessage($hash, $key));

            $this->root
                ->getElementsByTagName('Meta')
                ->item(0)
                ->appendChild($hashNode);
        } catch (Exception $e) {
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * @throws FileException
     */
    public function createArchiveFor(string $file): string
    {
        $archive = new ArchiveHandler($file, $this->extensionChecker);
        return $archive->compressFile($file);
    }
}
