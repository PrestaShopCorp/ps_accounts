<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * ./bin/console doctrine:schema:update --dump-sql
 *
 * EmployeeAccount
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class EmployeeAccount
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_employee_account", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @phpstan-ignore-next-line
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="id_employee", type="integer")
     */
    private $employeeId;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=64)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="uid", type="string", length=64)
     */
    private $uid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_add", type="datetime")
     */
    private $dateAdd;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_upd", type="datetime")
     */
    private $dateUpd;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getEmployeeId()
    {
        return $this->employeeId;
    }

    /**
     * @param int $employeeId
     *
     * @return EmployeeAccount
     */
    public function setEmployeeId($employeeId)
    {
        $this->employeeId = $employeeId;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return EmployeeAccount
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     *
     * @return EmployeeAccount
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateAdd()
    {
        return $this->dateAdd;
    }

    /**
     * Date is stored in UTC timezone
     *
     * @param \DateTime $dateAdd
     *
     * @return EmployeeAccount
     */
    public function setDateAdd($dateAdd)
    {
        $this->dateAdd = $dateAdd;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateUpd()
    {
        return $this->dateUpd;
    }

    /**
     * Date is stored in UTC timezone
     *
     * @param \DateTime $dateUpd
     *
     * @return EmployeeAccount
     */
    public function setDateUpd(DateTime $dateUpd)
    {
        $this->dateUpd = $dateUpd;

        return $this;
    }

    /**
     * Now we tell doctrine that before we persist or update we call the updatedTimestamps() function.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     *
     * @return void
     */
    public function updatedTimestamps()
    {
        $this->setDateUpd(new DateTime());

        if ($this->getDateAdd() == null) {
            $this->setDateAdd(new DateTime());
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id_employee' => $this->getEmployeeId(),
            'id_employee_account' => $this->getId(),
            'email' => $this->getEmail(),
            'uid' => $this->getUid(),
            'date_add' => $this->getDateAdd(),
            'date_upd' => $this->getDateUpd(),
        ];
    }
}
