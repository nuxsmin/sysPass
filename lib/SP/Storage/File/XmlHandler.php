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

namespace SP\Storage\File;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use ReflectionObject;
use RuntimeException;

/**
 * Class XmlHandler para manejo básico de documentos XML
 *
 * @package SP\Storage\File;
 */
final class XmlHandler implements XmlFileStorageInterface
{
    /**
     * @var mixed
     */
    protected $items;
    private ?DOMDocument $document = null;
    private FileHandler $fileHandler;

    /**
     * XmlHandler constructor.
     */
    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    /**
     * Cargar un archivo XML
     *
     * @throws FileException
     * @throws RuntimeException
     */
    public function load(string $node = 'root'): XmlFileStorageInterface
    {
        $this->fileHandler->checkIsReadable();
        $this->fileHandler->getFileSize(true);

        $this->items = [];
        $this->setDOM();
        $this->document->load($this->fileHandler->getFile());

        $nodes = $this->document->getElementsByTagName($node);

        if ($nodes->length === 0) {
            throw new RuntimeException(__u('XML node does not exist'));
        }

        $this->items = $this->readChildNodes($nodes->item(0)->childNodes);

        return $this;
    }

    /**
     * Crear un nuevo documento XML
     */
    private function setDOM(): void
    {
        $this->document = new DOMDocument('1.0', 'utf-8');
    }

    /**
     * Leer de forma recursiva los nodos hijos y devolver un array multidimensional
     */
    protected function readChildNodes(DOMNodeList $nodeList): array
    {
        $nodes = [];

        /** @var DOMElement $node */
        foreach ($nodeList as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                if (is_object($node->childNodes)
                    && $node->childNodes->length > 1
                ) {
                    if ($node->hasAttribute('multiple')
                        && (int)$node->getAttribute('multiple') === 1
                    ) {
                        $nodes[] = $this->readChildNodes($node->childNodes);

                    } elseif ($node->hasAttribute('class')) {
                        $nodes[$node->nodeName] = $this->readChildNodes($node->childNodes);
                        $nodes[$node->nodeName]['__class__'] = (string)$node->getAttribute('class');
                    } else {
                        $nodes[$node->nodeName] = $this->readChildNodes($node->childNodes);
                    }
                } else {
                    $val = null;

                    if (is_numeric($node->nodeValue)
                        && strpos($node->nodeValue, '.') === false) {
                        $val = (int)$node->nodeValue;
                    } else if (!empty($node->nodeValue)) {
                        $val = $node->nodeValue;
                    }

                    if ($node->nodeName === 'item') {
                        $nodes[] = $val;
                    } else {
                        $nodes[$node->nodeName] = $val;
                    }
                }
            }
        }

        return $nodes;
    }

    /**
     * Obtener un elemento del array
     */
    public function __get($id)
    {
        return $this->items[$id];
    }

    /**
     * Guardar el archivo XML
     *
     * @throws FileException
     * @throws RuntimeException
     */
    public function save($data, string $node = 'root'): XmlFileStorageInterface
    {
        $this->fileHandler->checkIsWritable();

        if (null === $data) {
            throw new RuntimeException(__u('There aren\'t any items to save'));
        }

        $this->setDOM();
        $this->document->formatOutput = true;

        $root = $this->document->createElement($node);
        $this->document->appendChild($root);
        $this->writeChildNodes($data, $root);

        $this->fileHandler->save($this->document->saveXML());

        return $this;
    }

    /**
     * Crear los nodos hijos recursivamente a partir de un array multidimensional
     */
    protected function writeChildNodes(
        $items,
        DOMNode $node,
        $type = null
    ): void
    {
        foreach ($this->analyzeItems($items) as $key => $value) {
            if (is_int($key)) {
                $newNode = $this->document->createElement('item');
                $newNode->setAttribute('type', $type);
            } else {
                $newNode = $this->document->createElement($key);
            }

            if (is_array($value)) {
                $this->writeChildNodes($value, $newNode, $key);
            } else if (is_object($value)) {
                $newNode->setAttribute('class', get_class($value));
                $this->writeChildNodes($value, $newNode, $key);
//                $newNode->appendChild($this->document->createTextNode(base64_encode(serialize($value))));
            } else {
                $newNode->appendChild($this->document->createTextNode(trim($value)));
            }

            $node->appendChild($newNode);
        }
    }

    /**
     * Analizar el tipo de elementos
     *
     * @return array|string
     */
    protected function analyzeItems($items, bool $serialize = false)
    {
        if (is_array($items)) {
            ksort($items);

            return $items;
        }

        if (is_object($items)) {
            return $serialize ? serialize($items) : $this->analyzeObject($items);
        }

        return [];

    }

    /**
     * Analizar un elemento del tipo objeto
     */
    protected function analyzeObject(object $object): array
    {
        $items = [];
        $reflection = new ReflectionObject($object);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object);

            if (is_bool($value)) {
                $items[$property->getName()] = (int)$value;
            } elseif (is_numeric($value)
                && strpos($value, '.') === false
            ) {
                $items[$property->getName()] = (int)$value;
            } else {
                $items[$property->getName()] = $value;
            }

            $property->setAccessible(false);
        }

        ksort($items);

        return $items;
    }

    /**
     * Devolver los elementos cargados
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Establecer los elementos
     */
    public function setItems($items): XmlHandler
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @throws \SP\Storage\File\FileException
     */
    public function getPathValue(string $path): string
    {
        $this->fileHandler->checkIsReadable();
        $this->fileHandler->getFileSize(true);

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->load($this->fileHandler->getFile());

        $query = (new DOMXPath($dom))->query($path);

        if ($query->length === 0) {
            throw new RuntimeException(__u('XML node does not exist'));
        }

        return $query->item(0)->nodeValue;
    }

    public function getFileHandler(): FileHandler
    {
        return $this->fileHandler;
    }
}