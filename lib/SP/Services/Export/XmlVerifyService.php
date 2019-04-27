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


use Defuse\Crypto\Exception\CryptoException;
use DOMDocument;
use DOMElement;
use DOMXPath;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;

/**
 * Class XmlVerifyService
 *
 * Verifies a sysPass exported file format
 *
 * @package SP\Services\Export
 */
final class XmlVerifyService extends Service
{
    const NODES = ['Category', 'Client', 'Tag', 'Account'];
    /**
     * @var DOMDocument
     */
    private $xml;
    /**
     * @var string
     */
    private $xmlFile;
    /**
     * @var string
     */
    private $password;

    /**
     * @param string $xmlFile
     *
     * @return VerifyResult
     * @throws ServiceException
     * @throws FileException
     */
    public function verify(string $xmlFile): VerifyResult
    {
        $this->xmlFile = $xmlFile;

        $this->setup();

        self::checkXmlHash($this->xml, $this->config->getConfigData()->getPasswordSalt());

        return new VerifyResult($this->getXmlVersion(), false, $this->countItemNodes($this->xml));
    }

    /**
     * @throws ServiceException
     * @throws FileException
     */
    private function setup()
    {
        $this->readXmlFile();
    }

    /**
     * Leer el archivo a un objeto XML.
     *
     * @throws ServiceException
     * @throws FileException
     */
    protected function readXmlFile()
    {
        libxml_use_internal_errors(true);

        // Cargar el XML con DOM
        $this->xml = new DOMDocument();
        $this->xml->formatOutput = false;
        $this->xml->preserveWhiteSpace = false;

        if ($this->xml->loadXML((new FileHandler($this->xmlFile))->read()) === false) {
            foreach (libxml_get_errors() as $error) {
                logger(__METHOD__ . ' - ' . $error->message);
            }

            throw new ServiceException(
                __u('Internal error'),
                ServiceException::ERROR,
                __u('Unable to process the XML file')
            );
        }
    }

    /**
     * Obtener la versión del XML
     *
     * @param DOMDocument $document
     * @param string      $key
     *
     * @return bool
     */
    public static function checkXmlHash(DOMDocument $document, string $key)
    {
        $DOMXPath = new DOMXPath($document);
        $hash = $DOMXPath->query('/Root/Meta/Hash');
        $sign = $DOMXPath->query('/Root/Meta/Hash/@sign');

        if ($hash->length === 1 && $sign->length === 1) {
            return Hash::checkMessage($hash->item(0)->nodeValue, $key, $sign->item(0)->nodeValue);
        }

        return $hash === XmlExportService::generateHashFromNodes($document);
    }

    /**
     * Obtener la versión del XML
     */
    private function getXmlVersion(): string
    {
        return (new DOMXPath($this->xml))->query('/Root/Meta/Version')->item(0)->nodeValue;
    }

    /**
     * @param DOMDocument $document
     *
     * @return int[]
     */
    private function countItemNodes(DOMDocument $document): array
    {
        $result = [];

        foreach (self::NODES as $node) {
            $result[$node] = (int)$document->getElementsByTagName($node)->length;
        }

        return $result;
    }

    /**
     * @param string $xmlFile
     * @param string $password
     *
     * @return VerifyResult
     * @throws ServiceException
     * @throws FileException
     * @throws CryptoException
     */
    public function verifyEncrypted(string $xmlFile, string $password): VerifyResult
    {
        $this->xmlFile = $xmlFile;
        $this->password = $password;

        $this->setup();

        $this->checkPassword();

        $key = $password !== '' ? $password : $this->config->getConfigData()->getPasswordSalt();

        if (!self::checkXmlHash($this->xml, $key)) {
            throw new ServiceException(__u('Error while checking integrity hash'));
        }

        return new VerifyResult($this->getXmlVersion(), $this->detectEncrypted(), $this->countItemNodes($this->processEncrypted()));
    }

    /**
     * @throws ServiceException
     */
    private function checkPassword()
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
     *
     * @return bool
     */
    private function detectEncrypted()
    {
        return $this->xml->getElementsByTagName('Encrypted')->length > 0;
    }

    /**
     * Procesar los datos encriptados y añadirlos al árbol DOM desencriptados
     *
     * @throws CryptoException
     * @throws ServiceException
     */
    private function processEncrypted(): DOMDocument
    {
        $xmlOut = new DOMDocument('1.0', 'UTF-8');
        $xmlOut->appendChild($xmlOut->createElement('Root'));

        foreach ($this->xml->getElementsByTagName('Data') as $node) {
            /** @var $node DOMElement */
            $xml = new DOMDocument();

            if (!$xml->loadXML(Crypt::decrypt(base64_decode($node->nodeValue), $node->getAttribute('key'), $this->password))) {
                throw new ServiceException(__u('Wrong encryption password'));
            }

            $xmlOut->documentElement->appendChild($xmlOut->importNode($xml->documentElement, true));
        }

        return $xmlOut;
    }
}