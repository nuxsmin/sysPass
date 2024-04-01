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

namespace SP\Infrastructure\File;

use CallbackFilterIterator;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMNodeList;
use ReflectionObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Storage\Ports\XmlFileStorageService;

use function SP\__u;
use function SP\logger;

/**
 * Class XmlFileStorage
 */
final readonly class XmlFileStorage implements XmlFileStorageService
{
    private DOMDocument $document;

    /**
     * XmlHandler constructor.
     */
    public function __construct(private FileHandlerInterface $fileHandler)
    {
        $this->document = new DOMDocument('1.0', 'utf-8');
        $this->document->formatOutput = true;
        $this->document->preserveWhiteSpace = false;
    }

    /**
     * Save the data into an XML file
     *
     * @throws FileException
     * @throws DOMException
     */
    public function save(array|object $data, string $node = 'root'): XmlFileStorageService
    {
        $this->fileHandler->checkIsWritable();

        $root = $this->document->createElement($node);
        $this->document->appendChild($root);
        $this->serializeItems($data, $root);

        $this->fileHandler->save($this->document->saveXML());

        return $this;
    }

    /**
     * @throws DOMException
     */
    private function serializeItems(array|object $items, DOMNode $currentNode, ?string $type = null): void
    {
        foreach ($this->analyzeItems($items) as $key => $value) {
            if (is_int($key)) {
                $newNode = $this->document->createElement('item');
                $newNode->setAttribute('type', $type);
            } else {
                $newNode = $this->document->createElement($key);
            }

            if (is_array($value)) {
                $this->serializeItems($value, $newNode, $key);
            } elseif (is_object($value)) {
                $newNode->setAttribute('class', get_class($value));
                $this->serializeItems($value, $newNode, $key);
            } else {
                $newNode->appendChild($this->document->createTextNode(trim($value)));
            }

            $currentNode->appendChild($newNode);
        }
    }

    private function analyzeItems(array|object $items): array
    {
        if (is_object($items)) {
            return $this->analyzeObject($items);
        }

        ksort($items);

        return $items;
    }

    private function analyzeObject(object $object): array
    {
        $items = [];
        $reflection = new ReflectionObject($object);

        foreach ($reflection->getProperties() as $property) {
            $value = $property->getValue($object);

            $items[$property->getName()] = match (true) {
                is_bool($value) || (is_numeric($value) && !str_contains($value, '.')) => (int)$value,
                default => $value
            };
        }

        ksort($items);

        return $items;
    }

    /**
     * Loads an XML file into an array
     *
     * @throws FileException
     * @throws ServiceException
     */
    public function load(string $node = 'root'): array
    {
        $this->fileHandler->checkIsReadable();
        $this->fileHandler->getFileSize(true);

        if ($this->document->load($this->fileHandler->getFile()) === false) {
            foreach (libxml_get_errors() as $error) {
                logger(__METHOD__ . ' - ' . $error->message);
            }

            throw ServiceException::error(__u('Internal error'), __u('Unable to process the XML file'));
        }

        $nodes = $this->document->getElementsByTagName($node);

        if ($nodes->length === 0) {
            throw ServiceException::error(__u('XML node does not exist'));
        }

        return $this->deserializeItems($nodes->item(0)->childNodes);
    }

    private function deserializeItems(DOMNodeList $nodeList): array
    {
        $nodes = [];

        $elementNodes = new CallbackFilterIterator(
            $nodeList->getIterator(),
            static fn(DOMNode $node) => $node->nodeType === XML_ELEMENT_NODE
        );

        /** @var DOMElement $node */
        foreach ($elementNodes as $node) {
            if ($node->childNodes->length > 1) {
                if ($node->hasAttribute('class')) {
                    $nodes[$node->nodeName] = $this->deserializeItems($node->childNodes);
                    $nodes[$node->nodeName]['__class__'] = $node->getAttribute('class');
                } else {
                    $nodes[$node->nodeName] = $this->deserializeItems($node->childNodes);
                }
            } else {
                $value = match (true) {
                    is_numeric($node->nodeValue) && !str_contains($node->nodeValue, '.') => (int)$node->nodeValue,
                    default => (string)$node->nodeValue
                };

                if ($node->nodeName === 'item') {
                    $nodes[] = $value;
                } else {
                    $nodes[$node->nodeName] = $value;
                }
            }
        }

        return $nodes;
    }

    /**
     * @throws FileException
     */
    public function getFileTime(): int
    {
        return $this->fileHandler->getFileTime();
    }
}
