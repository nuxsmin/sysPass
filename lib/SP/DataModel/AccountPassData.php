<?php

namespace SP\DataModel;

/**
 * Class AccountPassData
 * @package DataModel
 */
class AccountPassData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int Id de la cuenta.
     */
    public $account_id = 0;
    /**
     * @var string El nombre de la cuenta.
     */
    public $account_name = '';
    /**
     * @var string El nombre de usuario de la cuenta.
     */
    public $account_login = '';
    /**
     * @var string La clave de la cuenta.
     */
    public $account_pass = '';
    /**
     * @var string La clave de encriptación de la cuenta
     */
    public $account_key = '';
    /**
     * @var int
     */
    public $account_parentId = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->account_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->account_name;
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * @param int $account_id
     */
    public function setAccountId($account_id)
    {
        $this->account_id = $account_id;
    }

    /**
     * @return string
     */
    public function getAccountName()
    {
        return $this->account_name;
    }

    /**
     * @param string $account_name
     */
    public function setAccountName($account_name)
    {
        $this->account_name = $account_name;
    }

    /**
     * @return string
     */
    public function getAccountLogin()
    {
        return $this->account_login;
    }

    /**
     * @param string $account_login
     */
    public function setAccountLogin($account_login)
    {
        $this->account_login = $account_login;
    }

    /**
     * @return string
     */
    public function getAccountPass()
    {
        return $this->account_pass;
    }

    /**
     * @param string $account_pass
     */
    public function setAccountPass($account_pass)
    {
        $this->account_pass = $account_pass;
    }

    /**
     * @return string
     */
    public function getAccountKey()
    {
        return $this->account_key;
    }

    /**
     * @param string $account_key
     */
    public function setAccountKey($account_key)
    {
        $this->account_key = $account_key;
    }

    /**
     * @return int
     */
    public function getAccountParentId()
    {
        return $this->account_parentId;
    }

    /**
     * @param int $account_parentId
     */
    public function setAccountParentId($account_parentId)
    {
        $this->account_parentId = $account_parentId;
    }
}