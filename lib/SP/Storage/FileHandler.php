<?php

namespace SP\Storage;

/**
 * Class FileHandler
 * @package SP\Storage
 */
class FileHandler
{
    /**
     * @var string
     */
    protected $file;
    /**
     * @var
     */
    protected $handle;

    /**
     * FileHandler constructor.
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * @param $data
     * @return FileHandler
     * @throws FileException
     */
    public function write($data)
    {
        if ($this->handle === null) {
            $this->open('w');
        }

        if (fwrite($this->handle, $data) === false) {
            throw new FileException(sprintf(__u('No es posible escribir en el archivo (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * @param $mode
     * @return resource
     * @throws FileException
     */
    public function open($mode)
    {
        if (($this->handle = fopen($this->file, $mode)) === false) {
            throw new FileException(sprintf(__u('No es posible abrir el archivo (%s)'), $this->file));
        }

        return $this->handle;
    }

    /**
     * @throws FileException
     */
    public function close()
    {
        if (fclose($this->handle) === false) {
            throw new FileException(sprintf(__u('No es posible cerrar el archivo (%s)'), $this->file));
        }

        return $this;
    }
}