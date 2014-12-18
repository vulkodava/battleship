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
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /** @ORM\ManyToOne(name="field_id", targetEntity="Field") */
    protected $fieldId;

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
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * @param mixed $fieldId
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;
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