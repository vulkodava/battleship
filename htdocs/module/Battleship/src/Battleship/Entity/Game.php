<?php
namespace Battleship\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Game
 *
 * @ORM\Entity
 * @ORM\Table(name="games")
 */
class Game {
    const STATUS_NEW = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_FINISHED = 2;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /** @ORM\ManyToOne(targetEntity="Field") */
    protected $field;

    /** @ORM\ManyToOne(targetEntity="Player") */
    protected $player;

    /** @ORM\Column(name="moves_cnt", type="smallint", options={"default":0}) */
    protected $movesCnt;

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
        $this->setMovesCnt(0);
        $this->setStatus(self::STATUS_NEW);
        $this->setCreatedAt(new \DateTime());
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
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param mixed $fieldId
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return mixed
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @param mixed $playerId
     */
    public function setPlayer($player)
    {
        $this->player = $player;
    }

    /**
     * @return mixed
     */
    public function getMovesCnt()
    {
        return $this->movesCnt;
    }

    /**
     * @param mixed $movesCnt
     */
    public function setMovesCnt($movesCnt)
    {
        $this->movesCnt = $movesCnt;
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