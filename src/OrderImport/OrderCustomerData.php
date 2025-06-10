<?php

/**
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

namespace ShoppingfeedAddon\OrderImport;

class OrderCustomerData
{
    /** @var string */
    protected $firstName;

    /** @var string */
    protected $lastName;

    /** @var string */
    protected $email;

    /**
     * @return string
     */
    public function getFirstName()
    {
        return (string) $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return OrderCustomerData
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return (string) $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return OrderCustomerData
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return (string) $this->email;
    }

    /**
     * @param string $email
     *
     * @return OrderCustomerData
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }
}
