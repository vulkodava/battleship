<?php
namespace Battleship\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Game
 *
 * @ORM\Entity(repositoryClass="Battleship\Repository\Game")
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

    /**
     * @ORM\OneToMany(targetEntity="GameVessel", mappedBy="game")
     */
    protected $vessels;

    /**
     * @ORM\ManyToOne(targetEntity="Field", inversedBy="id")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * @ORM\ManyToOne(targetEntity="Player", inversedBy="id")
     * @ORM\JoinColumn(name="player_id", referencedColumnName="id")
     */
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
        $this->vessels = new ArrayCollection();
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
     * @param mixed $field
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
     * @param mixed $player
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
     * @return ArrayCollection
     */
    public function getVessels()
    {
        return $this->vessels;
    }

    /**
     * @param $vessels
     */
    public function setVessels($vessels)
    {
        $this->vessels = $vessels;
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