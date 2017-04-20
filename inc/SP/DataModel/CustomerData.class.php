<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\DataModel;

defined('APP_ROOT') || die();

/**
 * Class CustomerData
 *
 * @package SP\DataModel
 */
class CustomerData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int
     */
    public $customer_id = 0;
    /**
     * @var string
     */
    public $customer_name = '';
    /**
     * @var string
     */
    public $customer_description = '';
    /**
     * @var string
     */
    public $customer_hash = '';

    /**
     * CustomerData constructor.
     *
     * @param int    $customer_id
     * @param string $customer_name
     * @param string $customer_description
     */
    public function __construct($customer_id = null, $customer_name = null, $customer_description = null)
    {
        $this->customer_id = $customer_id;
        $this->customer_name = $customer_name;
        $this->customer_description = $customer_description;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @param int $customer_id
     */
    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customer_name;
    }

    /**
     * @param string $customer_name
     */
    public function setCustomerName($customer_name)
    {
        $this->customer_name = $customer_name;
    }

    /**
     * @return string
     */
    public function getCustomerDescription()
    {
        return $this->customer_description;
    }

    /**
     * @param string $customer_description
     */
    public function setCustomerDescription($customer_description)
    {
        $this->customer_description = $customer_description;
    }

    /**
     * @return string
     */
    public function getCustomerHash()
    {
        return $this->customer_hash;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->customer_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->customer_name;
    }
}