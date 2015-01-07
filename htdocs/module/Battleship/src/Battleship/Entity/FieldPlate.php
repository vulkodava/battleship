<?php
namespace Battleship\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FieldPlate
 *
 * @ORM\Entity
 * @ORM\Table(name="field_plates")
 */
class FieldPlate {
    const STATUS_NEW = 0;
    const STATUS_MISS = 1;
    const STATUS_HIT = 2;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Field", inversedBy="id")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * @ORM\ManyToOne(targetEntity="GameVessel", inversedBy="id")
     * @ORM\JoinColumn(name="game_vessel_id", referencedColumnName="id")
     */
    protected $gameVessel;

    /** @ORM\Column(name="coordinate_x", type="smallint") */
    protected $coordinateX;

    /** @ORM\Column(name="coordinate_y", type="smallint") */
    protected $coordinateY;

    /** @ORM\Column(name="status", type="smallint") */
    protected $status;

    /** @ORM\Column(name="created_at", type="datetime") */
    protected $createdAt;

    /** @ORM\Column(name="updated_at", type="datetime") */
    protected $updatedAt;

    /** @ORM\Column(name="deleted_at", type="datetime") */
    protected $deletedAt;

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
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param mixed $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return mixed
     */
    public function getGameVessel()
    {
        return $this->gameVessel;
    }

    /**
     * @param mixed $gameVessel
     */
    public function setGameVessel($gameVessel)
    {
        $this->gameVessel = $gameVessel;
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