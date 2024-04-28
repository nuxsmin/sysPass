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
use DOMXPath;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Export\Ports\XmlVerifyService;

use function SP\__u;

/**
 * Class XmlVerify
 *
 * Verify a sysPass exported file format
 */
final class XmlVerify extends Service implements XmlVerifyService
{
    use XmlTrait;

    private const NODES           = ['Category', 'Client', 'Tag', 'Account'];
    private const XML_MIN_VERSION = [2, 1, 0, 0];
    private readonly DOMDocument $document;

    public function __construct(
        Application                     $application,
        private readonly CryptInterface $crypt,
        private readonly string         $schema = XML_SCHEMA
    ) {
        parent::__construct($application);

        $this->document = new DOMDocument('1.0', 'UTF-8');
    }

    /**
     * @param string $xmlFile
     * @param string|null $password
     * @return VerifyResult
     * @throws ServiceException
     */
    public function verify(string $xmlFile, ?string $password = null): VerifyResult
    {
        $self = clone $this;

        $self->setup($xmlFile);
        $self->validateSchema();

        $version = $self->getXmlVersion();

        self::checkVersion($version);

        if (!self::checkXmlHash($self->document, $password ?? $self->config->getConfigData()->getPasswordSalt())) {
            throw ServiceException::error(__u('Error while checking integrity hash'));
        }

        if (!empty($password)) {
            $self->checkPassword($password);
            $self->processEncrypted($password);
        }

        return new VerifyResult($version, $self->detectEncrypted(), $self->countItemNodes());
    }

    /**
     * @throws ServiceException
     */
    private function setup(string $file): void
    {
        if (!$this->document->load($file, LIBXML_NOBLANKS)) {
            $error = libxml_get_last_error();
            throw ServiceException::error('Unable to load XML file', $error->message);
        }
    }

    /**
     * @throws ServiceException
     */
    private function validateSchema(): void
    {
        if (!$this->document->schemaValidate($this->schema)) {
            $error = libxml_get_last_error();
            throw ServiceException::error('Invalid XML schema', $error->message);
        }
    }

    /**
     * Obtener la versión del XML
     */
    private function getXmlVersion(): string
    {
        return (new DOMXPath($this->document))->query('/Root/Meta/Version')->item(0)->nodeValue;
    }

    /**
     * @throws ServiceException
     */
    private static function checkVersion(string $version): void
    {
        if (Version::checkVersion($version, [self::XML_MIN_VERSION])) {
            throw ServiceException::error(
                sprintf(
                    'Sorry, this XML version is not compatible. Please use >= %s',
                    implode('.', array_slice(self::XML_MIN_VERSION, 0, 2))
                )
            );
        }
    }

    /**
     * Obtener la versión del XML
     */
    public static function checkXmlHash(DOMDocument $document, string $key): bool
    {
        $xpath = new DOMXPath($document);
        $hash = $xpath->query('/Root/Meta/Hash')->item(0)?->nodeValue;
        $sign = $xpath->query('/Root/Meta/Hash/@sign')->item(0)?->nodeValue;

        if (!empty($hash) && !empty($sign)) {
            return Hash::checkMessage($hash, $key, $sign);
        }

        return $hash !== null && $hash === self::generateHashFromNodes($document);
    }

    /**
     * @throws ServiceException
     */
    private function checkPassword(string $password): void
    {
        $hash = $this->document
            ->getElementsByTagName('Encrypted')
            ->item(0)
            ->attributes
            ?->getNamedItem('hash')
            ->nodeValue;

        if (empty($hash) || !Hash::checkHashKey($password, $hash)) {
            throw ServiceException::error(__u('Wrong encryption password'));
        }
    }

    /**
     * Process the encrypted data and then build the unencrypted DOM
     *
     * @throws ServiceException
     */
    private function processEncrypted(string $password): DOMDocument
    {
        $dataNodes = (new DOMXPath($this->document))->query('/Root/Encrypted/Data');

        $decode = Version::checkVersion($this->getXmlVersion(), '320.0');

        /** @var $node DOMElement */
        foreach ($dataNodes as $node) {
            $data = $decode ? base64_decode($node->nodeValue) : $node->nodeValue;

            try {
                $xmlDecrypted = $this->crypt->decrypt($data, $node->getAttribute('key'), $password);
            } catch (CryptException $e) {
                throw ServiceException::error(__u('Wrong encryption password'), null, $e->getCode(), $e);
            }

            $newXmlData = new DOMDocument('1.0', 'UTF-8');

            if (!$newXmlData->loadXML($xmlDecrypted)) {
                throw ServiceException::error(__u('Error loading XML data'));
            }

            $this->document
                ->documentElement
                ->appendChild($this->document->importNode($newXmlData->documentElement, true));
        }

        // Remove the encrypted data after processing
        $this->document->documentElement->removeChild($dataNodes->item(0)->parentNode);

        // Validate XML schema again after processing the encrypted data
        $this->validateSchema();

        return $this->document;
    }

    /**
     * Verificar si existen datos encriptados
     */
    private function detectEncrypted(): bool
    {
        return $this->document->getElementsByTagName('Encrypted')->length > 0;
    }

    /**
     * @return int[]
     */
    private function countItemNodes(): array
    {
        $result = [];

        foreach (self::NODES as $node) {
            $result[$node] = $this->document->getElementsByTagName($node)->length;
        }

        return $result;
    }
}
