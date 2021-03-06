<?php

namespace FrontendUserManagement\Models;

use Doctrine\ORM\Mapping as ORM;
use Oforge\Engine\Modules\Auth\Models\User\BaseUser;
use Oforge\Engine\Modules\Core\Helper\SessionHelper;

/**
 * @ORM\Entity
 * @ORM\Table(name="frontend_user_management_user")
 * @ORM\HasLifecycleCallbacks
 */
class User extends BaseUser {
    /**
     * @var string $guid
     * @ORM\Column(name="guid", type="guid", nullable=true)
     */
    private $guid;
    /**
     * @var UserDetail $detail
     * @ORM\OneToOne(targetEntity="UserDetail", mappedBy="user", fetch="EXTRA_LAZY", cascade={"all"})
     */
    private $detail;
    /**
     * @var UserAddress $address
     * @ORM\OneToOne(targetEntity="UserAddress", mappedBy="user", fetch="EXTRA_LAZY", cascade={"all"})
     */
    private $address;

    public function __construct() {
        parent::__construct();
    }

    /** @ORM\PrePersist */
    public function updatedGuid() : void {
        $newGuid = SessionHelper::generateGuid();
        $this->setGuid($newGuid);
    }

    /**
     * @return string
     */
    public function getGuid() : string {
        return $this->guid;
    }

    /**
     * @param string $guid
     *
     * @return User
     */
    public function setGuid(string $guid) : User {
        $this->guid = $guid;

        return $this;
    }

    /**
     * @return UserDetail|null
     */
    public function getDetail() : ?UserDetail {
        return $this->detail;
    }

    /**
     * @return UserAddress|null
     */
    public function getAddress() : ?UserAddress {
        return $this->address;
    }

    /**
     * @param UserDetail $detail
     */
    public function setDetail(UserDetail $detail) : void {
        $this->detail = $detail;
    }

}
