<?php

namespace SP\DataModel;

/**
 * Class AccountPassData
 *
 * @package DataModel
 */
class AccountPassData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int Id de la cuenta.
     */
    public $id = 0;
    /**
     * @var string El nombre de la cuenta.
     */
    public $name = '';
    /**
     * @var string El nombre de usuario de la cuenta.
     */
    public $login = '';
    /**
     * @var string La clave de la cuenta.
     */
    public $pass = '';
    /**
     * @var string La clave de encriptación de la cuenta
     */
    public $key = '';
    /**
     * @var int
     */
    public $parentId = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param string $pass
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }
}