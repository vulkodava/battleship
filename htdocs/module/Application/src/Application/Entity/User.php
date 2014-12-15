<?php
/**
 * Created by PhpStorm.
 * User: momchil.milev
 * Date: 15.12.2014 г.
 * Time: 15:37 ч.
 */

namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class User {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $fullName;

    /** @ORM\ManyToOne(targetEntity="Address") */
    protected $address;

    /** @ORM\ManyToMany(targetEntity="Project") */
    protected $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }

    // getters/setters

    public function setId($id) {
        $this->id = $id;
        return true;
    }
    public function getId() {
        return $this->id;
    }

    public function setFullName($fullName) {
        $this->fullName = $fullName;
        return true;
    }
    public function getFullName() {
        return $this->fullName;
    }

    public function setAddress($address) {
        $this->address = $address;
        return true;
    }
    public function getAddress() {
        return $this->address;
    }

    public function setProjects($projects) {
        $this->projects = $projects;
        return true;
    }
    public function getProjects() {
        return $this->projects;
    }
}