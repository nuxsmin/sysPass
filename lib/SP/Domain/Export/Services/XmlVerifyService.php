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


use Defuse\Crypto\Exception\CryptoException;
use DOMDocument;
use DOMElement;
use DOMXPath;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Ports\XmlVerifyServiceInterface;
use SP\Domain\Import\Services\FileImport;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\XmlFileImport;
use SP\Infrastructure\File\FileException;
use SP\Util\VersionUtil;

/**
 * Class XmlVerifyService
 *
 * Verifies a sysPass exported file format
 *
 * @package SP\Domain\Export\Services
 */
final class XmlVerifyService extends Service implements XmlVerifyServiceInterface
{
    use XmlTrait;

    private const NODES           = ['Category', 'Client', 'Tag', 'Account'];
    private const XML_MIN_VERSION = [2, 1, 0, 0];
    private ?DOMDocument $xml      = null;
    private ?string      $xmlFile  = null;
    private ?string      $password = null;

    /**
     * @throws FileException
     * @throws ImportException
     * @throws ServiceException
     */
    public function verify(string $xmlFile): VerifyResult
    {
        $this->xmlFile = $xmlFile;

        $this->setup();

        self::validateSchema($this->xml);

        $version = $this->getXmlVersion();

        self::checkVersion($version);

        self::checkXmlHash($this->xml, $this->config->getConfigData()->getPasswordSalt());

        return new VerifyResult(
            $version,
            false,
            $this->countItemNodes($this->xml)
        );
    }

    /**
     * @throws FileException
     * @throws ImportException
     */
    private function setup(): void
    {
        $this->xml = (new XmlFileImport(FileImport::fromFilesystem($this->xmlFile)))->getXmlDOM();
    }

    /**
     * @throws ServiceException
     */
    public static function validateSchema(DOMDocument $document): void
    {
        if (!$document->schemaValidate(XML_SCHEMA)) {
            throw new ServiceException('Invalid XML schema');
        }
    }

    /**
     * Obtener la versión del XML
     */
    private function getXmlVersion(): string
    {
        return (new DOMXPath($this->xml))->query('/Root/Meta/Version')->item(0)->nodeValue;
    }

    /**
     * @throws ServiceException
     */
    public static function checkVersion(string $version): void
    {
        if (VersionUtil::checkVersion($version, self::XML_MIN_VERSION)) {
            throw new ServiceException(
                sprintf(
                    'Sorry, this XML version is not compatible. Please use >= %s',
                    VersionUtil::normalizeVersionForCompare(self::XML_MIN_VERSION)
                )
            );
        }
    }

    /**
     * Obtener la versión del XML
     */
    public static function checkXmlHash(
        DOMDocument $document,
        string $key
    ): bool {
        $DOMXPath = new DOMXPath($document);
        $hash = $DOMXPath->query('/Root/Meta/Hash');
        $sign = $DOMXPath->query('/Root/Meta/Hash/@sign');

        if ($hash->length === 1 && $sign->length === 1) {
            return Hash::checkMessage(
                $hash->item(0)->nodeValue,
                $key,
                $sign->item(0)->nodeValue
            );
        }

        return (string)$hash === self::generateHashFromNodes($document);
    }

    /**
     * @return int[]
     */
    private function countItemNodes(DOMDocument $document): array
    {
        $result = [];

        foreach (self::NODES as $node) {
            $result[$node] = $document->getElementsByTagName($node)->length;
        }

        return $result;
    }

    /**
     * @throws FileException
     * @throws ImportException
     * @throws ServiceException
     */
    public function verifyEncrypted(string $xmlFile, string $password): VerifyResult
    {
        $this->xmlFile = $xmlFile;
        $this->password = $password;

        $this->setup();

        self::validateSchema($this->xml);

        self::checkVersion($this->getXmlVersion());

        $this->checkPassword();

        $key = $password !== '' ? $password : $this->config->getConfigData()->getPasswordSalt();

        if (!self::checkXmlHash($this->xml, $key)) {
            throw new ServiceException(__u('Error while checking integrity hash'));
        }

        return new VerifyResult(
            $this->getXmlVersion(),
            $this->detectEncrypted(),
            $this->countItemNodes($this->processEncrypted())
        );
    }

    /**
     * @throws ServiceException
     */
    private function checkPassword(): void
    {
        $hash = $this->xml
            ->getElementsByTagName('Encrypted')
            ->item(0)
            ->getAttribute('hash');

        if (empty($hash) || !Hash::checkHashKey($this->password, $hash)) {
            throw new ServiceException(__u('Wrong encryption password'));
        }
    }

    /**
     * Verificar si existen datos encriptados
     */
    private function detectEncrypted(): bool
    {
        return $this->xml->getElementsByTagName('Encrypted')->length > 0;
    }

    /**
     * Process the encrypted data and then build the unencrypted DOM
     *
     * @throws ServiceException
     */
    private function processEncrypted(): DOMDocument
    {
        $xpath = new DOMXPath($this->xml);
        $dataNodes = $xpath->query('/Root/Encrypted/Data');

        $decode = VersionUtil::checkVersion(
            $this->getXmlVersion(),
            '320.0'
        );

        /** @var $node DOMElement */
        foreach ($dataNodes as $node) {
            $data = $decode ? base64_decode($node->nodeValue) : $node->nodeValue;

            try {
                $xmlDecrypted = Crypt::decrypt(
                    $data,
                    $node->getAttribute('key'),
                    $this->password
                );
            } catch (CryptoException $e) {
                throw new ServiceException(__u('Wrong encryption password'));
            }

            $newXmlData = new DOMDocument();

            if ($newXmlData->loadXML($xmlDecrypted) === false) {
                throw new ServiceException(__u('Error loading XML data'));
            }

            $this->xml->documentElement->appendChild(
                $this->xml->importNode($newXmlData->documentElement, true)
            );
        }

        // Remove the encrypted data after processing
        $this->xml->documentElement->removeChild($dataNodes->item(0)->parentNode);

        // Validate XML schema again after processing the encrypted data
        self::validateSchema($this->xml);

        return $this->xml;
    }
}
