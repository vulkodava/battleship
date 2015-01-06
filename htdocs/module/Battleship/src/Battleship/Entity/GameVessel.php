<?php
namespace Battleship\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * GameVessel
 *
 * @ORM\Entity
 * @ORM\Table(name="game_vessels")
 */
class GameVessel {
    const STATUS_INTACT = 0;
    const STATUS_HIT = 1;
    const STATUS_SUNK = 2;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Game", inversedBy="id")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="id")
     */
    protected $game;

    /**
     * @ORM\ManyToOne(targetEntity="VesselType", inversedBy="id")
     * @ORM\JoinColumn(name="vessel_type_id", referencedColumnName="id")
     */
    protected $vessel_type;

    /**
     * @ORM\OneToMany(targetEntity="FieldPlate", mappedBy="game_vessel")
     */
    protected $vessel_coordinates;

    /** @ORM\Column(name="coordinate_x", type="smallint") */
    protected $coordinateX;

    /** @ORM\Column(name="coordinate_y", type="string") */
    protected $coordinateY;

    /** @ORM\Column(name="status", type="smallint") */
    protected $status;

    /** @ORM\Column(name="created_at", type="datetime") */
    protected $createdAt;

    /** @ORM\Column(name="updated_at", type="datetime") */
    protected $updatedAt;

    /** @ORM\Column(name="deleted_at", type="datetime") */
    protected $deletedAt;

    public function __construct()
    {
        $this->vessel_coordinates = new ArrayCollection();
    }

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
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param mixed $game
     */
    public function setGame($game)
    {
        $this->game = $game;
    }

    /**
     * @return mixed
     */
    public function getVesselType()
    {
        return $this->vessel_type;
    }

    /**
     * @param mixed $vesselType
     */
    public function setVesselType($vesselType)
    {
        $this->vessel_type = $vesselType;
    }

    /**
     * @return mixed
     */
    public function getVesselCoordinates()
    {
        return $this->vessel_coordinates;
    }

    /**
     * @param mixed $vesselCoordinates
     */
    public function setVesselCoordinates($vesselCoordinates)
    {
        $this->vessel_coordinates = $vesselCoordinates;
    }

    /**
     * @return mixed
     */
    public function getCoordinateX()
    {
        return $this->coordinateX;
    }

    /**
     * @param mixed $coordinateX
     */
    public function setCoordinateX($coordinateX)
    {
        $this->coordinateX = $coordinateX;
    }

    /**
     * @return mixed
     */
    public function getCoordinateY()
    {
        return $this->coordinateY;
    }

    /**
     * @param mixed $coordinateY
     */
    public function setCoordinateY($coordinateY)
    {
        $this->coordinateY = $coordinateY;
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

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param mixed $deletedAt
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /******GETTERS AND SETTERS******/
}