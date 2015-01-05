<?php
namespace Battleship\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VesselCoordinate
 *
 * @ORM\Entity
 * @ORM\Table(name="vessel_coordinates")
 */
class VesselCoordinate {
    const STATUS_INTACT = 0;
    const STATUS_HIT = 1;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="GameVessel", inversedBy="id")
     * @ORM\JoinColumn(name="vessel_id", referencedColumnName="id")
     */
    protected $vessel;

    /**
     * @ORM\ManyToOne(targetEntity="FieldPlate", inversedBy="id")
     * @ORM\JoinColumn(name="plate_coordinate_id", referencedColumnName="id")
     */
    protected $plate_coordinate;

    /** @ORM\Column(name="status", type="smallint") */
    protected $status;

//    /** @ORM\Column(name="created_at", type="datetime") */
//    protected $createdAt;
//
//    /** @ORM\Column(name="updated_at", type="datetime") */
//    protected $updatedAt;
//
//    /** @ORM\Column(name="deleted_at", type="datetime") */
//    protected $deletedAt;

    /******GETTERS AND SETTERS******/

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getVessel()
    {
        return $this->vessel;
    }

    /**
     * @param mixed $vessel
     */
    public function setVessel($vessel)
    {
        $this->vessel = $vessel;
    }

    /**
     * @return mixed
     */
    public function getPlateCoordinate()
    {
        return $this->plate_coordinate;
    }

    /**
     * @param mixed $plateCoordinate
     */
    public function setPlateCoordinate($plateCoordinate)
    {
        $this->plate_coordinate = $plateCoordinate;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

//    /**
//     * @return mixed
//     */
//    public function getCreatedAt()
//    {
//        return $this->createdAt;
//    }
//
//    /**
//     * @param mixed $createdAt
//     */
//    public function setCreatedAt($createdAt)
//    {
//        $this->createdAt = $createdAt;
//    }
//
//    /**
//     * @return mixed
//     */
//    public function getUpdatedAt()
//    {
//        return $this->updatedAt;
//    }
//
//    /**
//     * @param mixed $updatedAt
//     */
//    public function setUpdatedAt($updatedAt)
//    {
//        $this->updatedAt = new \DateTime();
//    }
//
//    /**
//     * @return mixed
//     */
//    public function getDeletedAt()
//    {
//        return $this->deletedAt;
//    }
//
//    /**
//     * @param mixed $deletedAt
//     */
//    public function setDeletedAt($deletedAt)
//    {
//        $this->deletedAt = $deletedAt;
//    }

    /******GETTERS AND SETTERS******/
}