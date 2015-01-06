<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.01.15
 * Time: 10:53
 */

namespace Battleship\Repository;

use Battleship\Entity\Field;
use Battleship\Entity\GameVessel;
use Battleship\Entity\Player;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Zend\Session\Container;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;

class Game extends EntityRepository {
    private $gameEntity;
    private $field;
    private $gameConfig;
    private $battleshipGameSession;
    private $gameVesselTypes;

    public static $letters = array(
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'R',
        'S',
        'T',
    );

    public function startGame() {
        $this->setBattleshipGameSession(new Container('battleshipGameSession'));

        $this->gameVesselTypes = $this->getEntityManager()->getRepository('Battleship\Entity\VesselType')
            ->findBy(array('status' => \Battleship\Entity\VesselType::STATUS_ACTIVE));
        if (isset($this->getBattleshipGameSession()->gameId)) {
            $game = $this->getEntityManager()->getRepository('Battleship\Entity\Game')->find($this->getBattleshipGameSession()->gameId);
            if (!empty($game)) {
                $this->setGameEntity($game);
                $this->setField($game->getField());
            } else {
                throw new InvalidArgumentException('No Game Id.', 102);
            }
        } else {
            $this->createGame();
        }
    }

    private function createGame() {
        $gameConfig = $this->getEntityManager()->getRepository('Battleship\Entity\GameConfig')->findAll();

        foreach ($gameConfig as $config) {
            $this->gameConfig[$config->getName()] = $config->getValue();
        }

        $this->createGameField();

        $player = new Player();
        $player->setUsername('guest');
        $player->setFirstName('Guest');
        $player->setLastName('Guest');
        $player->setCreatedAt(new \DateTime());
        $player->setStatus(Player::STATUS_ACTIVE);
        $this->getEntityManager()->persist($player);
        $this->getEntityManager()->flush();

        $game = new \Battleship\Entity\Game();
        $game->setField($this->getField());
        $game->setPlayer($player);

        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();

        // Set gameId in session.
        $this->getBattleshipGameSession()->gameId = $game->getId();

        $gameVessels = array();
        foreach ($this->getGameVesselTypes() as $vesselType) {
            for ($i = 0; $i < (int) $vesselType->getVesselsCount(); $i++) {
                $gameVessel = new GameVessel();
                $gameVessel->setGame($game);
                $gameVessel->setVesselType($vesselType);
                $gameVessel->setCoordinateX(1);
                $gameVessel->setCoordinateY('A');
                $gameVessel->setUpdatedAt(new \DateTime());
                $gameVessel->setStatus(\Battleship\Entity\GameVessel::STATUS_INTACT);
                $this->getEntityManager()->persist($gameVessel);
                $this->getEntityManager()->flush();
                $gameVessels[] = $gameVessel;
            }
        }

        $this->deployShips($gameVessels, $this->gameConfig['x'], $this->gameConfig['y'], $this->getField());

        $this->setGameEntity($game);
    }

    private function createGameField()
    {
        $field = new Field();
        $field->setSizeX($this->gameConfig['x']);
        $field->setSizeY($this->gameConfig['y']);
        $field->setCreatedAt(new \DateTime());
        $this->getEntityManager()->persist($field);
        $this->getEntityManager()->flush();

        $this->setField($field);

        // Create Field Plates.
        for ($row = 0; $row < $this->gameConfig['x']; $row++) {
            $gameGrid[$row] = array();
            for ($col = 0; $col < $this->gameConfig['y']; $col++) {
                $fieldPlate = new \Battleship\Entity\FieldPlate();
                $fieldPlate->setField($field);
                $fieldPlate->setStatus(\Battleship\Entity\FieldPlate::STATUS_NEW);
                $fieldPlate->setCoordinateX($row);
                $fieldPlate->setCoordinateY($col);
                $this->getEntityManager()->persist($fieldPlate);
                $this->getEntityManager()->flush();
            }
        }
    }

    public function setupBoard()
    {
        $field = $this->getField();
        if (empty($field)) {
            throw new InvalidArgumentException('No Field supplied for the Game.', 103);
        }

        $gameGrid = array();
        $fieldPlates = $this->getEntityManager()->getRepository('Battleship\Entity\FieldPlate')->findBy(array(
            'field' => $field,
        ));
        foreach ($fieldPlates as $fieldPlate) {
            $content = 'empty';
            $vesselId = null;
            if (!is_null($fieldPlate->getGameVessel())) {
                $content = $fieldPlate->getGameVessel()->getVesselType()->getName();
                $vesselId = $fieldPlate->getGameVessel()->getId();
            }
            $gameGrid[$fieldPlate->getCoordinateX()][$fieldPlate->getCoordinateY()] = array(
                'field_plate_status' => $fieldPlate->getStatus(),
                'content' => $content,
                'vessel_id' => $vesselId,
            );
        }
        return $gameGrid;
    }

    private function deployShips(array $gameVessels, $maxX, $maxY)
    {
        foreach ($gameVessels as $gameVessel) {
            $vesselSize = $gameVessel->getVesselType()->getSize();
            $vesselDirection = rand(0, 1);
            $startX = $this->generateFirstPosition($maxX, $vesselSize);
            $startY = $this->generateFirstPosition($maxY, $vesselSize);

            if ($vesselDirection == 1) {
                // Deploy horizontally.
                for($rowNumber = 0; $rowNumber < $maxX; $rowNumber++) {
                    for($colNumber = 0; $colNumber < $maxY; $colNumber++) {
                        if (
                            $colNumber >= $startX && $colNumber < ($startX + $vesselSize)
                            && $rowNumber == $startY
                        ) {
                            $this->addVessel($gameVessel, $rowNumber, $colNumber);
                        }
                    }
                }
            } else {
                // Deploy vertically.
                for($rowNumber = 0; $rowNumber < $maxX; $rowNumber++) {
                    for($colNumber = 0; $colNumber < $maxY; $colNumber++) {
                        if (
                            $rowNumber >= $startY && $rowNumber < ($startY + $vesselSize)
                            && $colNumber == $startX
                        ) {
                            $this->addVessel($gameVessel, $rowNumber, $colNumber);
                        }
                    }
                }
            }
        }
    }

    private function generateFirstPosition($max, $vesselSize)
    {
        $start = rand(0, ($max - 1));
        if ($start + $vesselSize > $max) {
            $start = $this->generateFirstPosition($max, $vesselSize);
        }
        return $start;
    }

    private function addVessel($gameVessel, $rowNumber, $colNumber)
    {
        $field = $this->getField();
        if (empty($field)) {
            throw new InvalidArgumentException('Invalid game field.', 101);
        }

        $fieldPlate = $this->getEntityManager()->getRepository('Battleship\Entity\FieldPlate')->findOneBy(array(
            'coordinateX' => $rowNumber,
            'coordinateY' => $colNumber,
            'field' => $field,
        ));

        $fieldPlate->setGameVessel($gameVessel);
        $this->getEntityManager()->persist($fieldPlate);
        $this->getEntityManager()->flush();
    }

    public function fireShot($params)
    {
        $params['field'] = $this->getField();
        $fieldPlate = $this->getEntityManager()->getRepository('Battleship\Entity\FieldPlate')->findOneBy($params);

        $status = \Battleship\Entity\FieldPlate::STATUS_MISS;
        if (!is_null($fieldPlate->getGameVessel())) {
            $status = \Battleship\Entity\FieldPlate::STATUS_HIT;
        }
        $fieldPlate->setStatus($status);

        $this->getEntityManager()->persist($fieldPlate);

        $this->getGameEntity()->setMovesCnt($this->getGameEntity()->getMovesCnt() + 1);
        $this->getEntityManager()->persist($this->getGameEntity());

        $this->getEntityManager()->flush();

        return true;
    }

    public static function convertCoordinates($coordinatesIn)
    {
        self::$letters;
        $x = substr($coordinatesIn, 0, 1);
        $y = substr($coordinatesIn, 1);

        $x = array_search($x, self::$letters);

        return array('coordinateX' => $x, 'coordinateY' => $y);
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
    public function getGameConfig()
    {
        return $this->gameConfig;
    }

    /**
     * @param mixed $gameConfig
     */
    public function setGameConfig($gameConfig)
    {
        $this->gameConfig = $gameConfig;
    }

    /**
     * @return mixed
     */
    public function getBattleshipGameSession()
    {
        return $this->battleshipGameSession;
    }

    /**
     * @param mixed $battleshipGameSession
     */
    public function setBattleshipGameSession($battleshipGameSession)
    {
        $this->battleshipGameSession = $battleshipGameSession;
    }

    /**
     * @return mixed
     */
    public function getGameVesselTypes()
    {
        return $this->gameVesselTypes;
    }

    /**
     * @param mixed $gameVesselTypes
     */
    public function setGameVesselTypes($gameVesselTypes)
    {
        $this->gameVesselTypes = $gameVesselTypes;
    }

    /**
     * @return mixed
     */
    public function getGameEntity()
    {
        return $this->gameEntity;
    }

    /**
     * @param mixed $gameEntity
     */
    public function setGameEntity($gameEntity)
    {
        $this->gameEntity = $gameEntity;
    }
}