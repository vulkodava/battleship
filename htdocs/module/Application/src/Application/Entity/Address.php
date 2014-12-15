<?php
/**
 * Created by PhpStorm.
 * User: momchil.milev
 * Date: 15.12.2014 г.
 * Time: 16:05 ч.
 */

namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** @ORM\Entity */
class Address {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $city;

    /** @ORM\Column(type="string") */
    protected $country;

    // getters/setters etc.

    public function getId() {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return true;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }
}