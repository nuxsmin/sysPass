<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\DataModel;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use JsonSerializable;
use SP\Util\Json;

/**
 * Class AccountData
 *
 * @package SP\Account
 */
class AccountData extends DataModelBase implements JsonSerializable, DataModelInterface
{
    /**
     * @var int Id de la cuenta.
     */
    public $account_id = 0;
    /**
     * @var int Id del usuario principal de la cuenta.
     */
    public $account_userId = 0;
    /**
     * @var int Id del grupo principal de la cuenta.
     */
    public $account_userGroupId = 0;
    /**
     * @var int Id del usuario que editó la cuenta.
     */
    public $account_userEditId = 0;
    /**
     * @var string El nombre de la cuenta.
     */
    public $account_name = '';
    /**
     * @var int Id del cliente de la cuenta.
     */
    public $account_customerId = 0;
    /**
     * @var int Id de la categoría de la cuenta.
     */
    public $account_categoryId = 0;
    /**
     * @var string El nombre de usuario de la cuenta.
     */
    public $account_login = '';
    /**
     * @var string La URL de la cuenta.
     */
    public $account_url = '';
    /**
     * @var string La clave de la cuenta.
     */
    public $account_pass = '';
    /**
     * @var string El vector de inicialización de la cuenta.
     */
    public $account_IV = '';
    /**
     * @var string Las nosta de la cuenta.
     */
    public $account_notes = '';
    /**
     * @var bool Si se permite la edición por los usuarios secundarios.
     */
    public $account_otherUserEdit = false;
    /**
     * @var bool Si se permita la edición por los grupos secundarios.
     */
    public $account_otherGroupEdit = false;
    /**
     * @var int
     */
    public $account_dateAdd = 0;
    /**
     * @var int
     */
    public $account_dateEdit = 0;
    /**
     * @var int
     */
    public $account_countView = 0;
    /**
     * @var int
     */
    public $account_countDecrypt = 0;

    /**
     * AccountData constructor.
     *
     * @param int $accountId
     */
    public function __construct($accountId = 0)
    {
        $this->account_id = (int)$accountId;
    }

    /**
     * @return int
     */
    public function getAccountDateAdd()
    {
        return $this->account_dateAdd;
    }

    /**
     * @param int $account_dateAdd
     */
    public function setAccountDateAdd($account_dateAdd)
    {
        $this->account_dateAdd = (int)$account_dateAdd;
    }

    /**
     * @return int
     */
    public function getAccountDateEdit()
    {
        return $this->account_dateEdit;
    }

    /**
     * @param int $account_dateEdit
     */
    public function setAccountDateEdit($account_dateEdit)
    {
        $this->account_dateEdit = (int)$account_dateEdit;
    }

    /**
     * @return int
     */
    public function getAccountUserEditId()
    {
        return $this->account_userEditId;
    }

    /**
     * @param int $account_userEditId
     */
    public function setAccountUserEditId($account_userEditId)
    {
        $this->account_userEditId = (int)$account_userEditId;
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
    public function getAccountIV()
    {
        return $this->account_IV;
    }

    /**
     * @param string $account_IV
     */
    public function setAccountIV($account_IV)
    {
        $this->account_IV = $account_IV;
    }

    /**
     * @return int|null
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
        $this->account_id = (int)$account_id;
    }

    /**
     * @return int
     */
    public function getAccountUserId()
    {
        return $this->account_userId;
    }

    /**
     * @param int $account_userId
     */
    public function setAccountUserId($account_userId)
    {
        $this->account_userId = (int)$account_userId;
    }

    /**
     * @return int
     */
    public function getAccountUserGroupId()
    {
        return $this->account_userGroupId;
    }

    /**
     * @param int $account_userGroupId
     */
    public function setAccountUserGroupId($account_userGroupId)
    {
        $this->account_userGroupId = (int)$account_userGroupId;
    }

    /**
     * @return bool
     */
    public function getAccountOtherUserEdit()
    {
        return $this->account_otherUserEdit;
    }

    /**
     * @param bool $account_otherUserEdit
     */
    public function setAccountOtherUserEdit($account_otherUserEdit)
    {
        $this->account_otherUserEdit = (int)$account_otherUserEdit;
    }

    /**
     * @return bool
     */
    public function getAccountOtherGroupEdit()
    {
        return $this->account_otherGroupEdit;
    }

    /**
     * @param bool $account_otherGroupEdit
     */
    public function setAccountOtherGroupEdit($account_otherGroupEdit)
    {
        $this->account_otherGroupEdit = (int)$account_otherGroupEdit;
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
     * @return int
     */
    public function getAccountCategoryId()
    {
        return $this->account_categoryId;
    }

    /**
     * @param int $account_categoryId
     */
    public function setAccountCategoryId($account_categoryId)
    {
        $this->account_categoryId = (int)$account_categoryId;
    }

    /**
     * @return int
     */
    public function getAccountCustomerId()
    {
        return $this->account_customerId;
    }

    /**
     * @param int $account_customerId
     */
    public function setAccountCustomerId($account_customerId)
    {
        $this->account_customerId = (int)$account_customerId;
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
    public function getAccountUrl()
    {
        return $this->account_url;
    }

    /**
     * @param string $account_url
     */
    public function setAccountUrl($account_url)
    {
        $this->account_url = $account_url;
    }

    /**
     * @return string
     */
    public function getAccountNotes()
    {
        return $this->account_notes;
    }

    /**
     * @param string $account_notes
     */
    public function setAccountNotes($account_notes)
    {
        $this->account_notes = $account_notes;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $data = get_object_vars($this);

        unset($data['accountPass'], $data['accountIV']);

        return Json::safeJson($data);
    }

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
    public function getAccountCountView()
    {
        return $this->account_countView;
    }

    /**
     * @param int $account_countView
     */
    public function setAccountCountView($account_countView)
    {
        $this->account_countView = (int)$account_countView;
    }

    /**
     * @return int
     */
    public function getAccountCountDecrypt()
    {
        return $this->account_countDecrypt;
    }

    /**
     * @param int $account_countDecrypt
     */
    public function setAccountCountDecrypt($account_countDecrypt)
    {
        $this->account_countDecrypt = (int)$account_countDecrypt;
    }
}