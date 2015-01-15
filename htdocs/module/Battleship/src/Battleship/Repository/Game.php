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
use Zend\Form\Exception\InvalidElementException;
use Zend\Session\Container;
use Doctrine\ORM\Query\Expr;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;
use ZendService\ReCaptcha\Exception;

class Game extends EntityRepository {
    const VESSEL_POSITION_VERTICALLY = 0;
    const VESSEL_POSITION_HORIZONTALLY = 1;
    const DEPLOYMENT_TRIES_THRESHOLD = 10000;

    private $vesselsDeployCounter = 0;
    private $gameEntity;
    private $field;
    private $gameConfig;
    private $battleshipGameSession;
    private $gameVesselTypes;
    private $missedShots;
    private $hits;
    private $gameVesselsInfo;
    private $shotInfo;
    private $gameGrid;

    public static $letters = array(
        'skip-zero-index',
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

    /**
     * Starts a game.
     *
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    public function startGame() {
        $this->setBattleshipGameSession(new Container('battleshipGameSession'));
        $config = $this->getEntityManager()->getRepository('Battleship\Entity\GameConfig')->findAll();
        foreach ($config as $configElement) {
            $gameConfig[$configElement->getName()] = $configElement->getValue();
        }
        $this->setGameConfig($gameConfig);

        $vesselTypes = $this->getEntityManager()->getRepository('Battleship\Entity\VesselType')
            ->findBy(array('status' => \Battleship\Entity\VesselType::STATUS_ACTIVE));

        $gameVesselTypes = array();
        foreach ($vesselTypes as $vesselType) {
            $gameVesselTypes[$vesselType->getId()] = $vesselType;
        }

        $this->setGameVesselTypes($gameVesselTypes);

        if (isset($this->getBattleshipGameSession()->gameId)) {
            $game = $this->getEntityManager()->getRepository('Battleship\Entity\Game')->find($this->getBattleshipGameSession()->gameId);
            if (!empty($game)) {
                $this->setGameEntity($game);
                $this->setField($game->getField());
            } else {
                throw new InvalidArgumentException('No or Invalid Game Id.', 102);
            }
        } else {
            $this->createGame();
        }

        $this->loadVesselsInfo();
    }

    /**
     * Creates a new game.
     *
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    private function createGame() {
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

    /**
     * Creates a new Battlefield.
     *
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
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
        $this->gameGrid = array();
        for ($row = 1; $row < ($this->gameConfig['x'] + 1); $row++) {
            $this->gameGrid[$row] = array();
            for ($col = 1; $col < ($this->gameConfig['y'] + 1); $col++) {
                $this->gameGrid[$row][$col] = array();
                $fieldPlate = new \Battleship\Entity\FieldPlate();
                $fieldPlate->setField($field);
                $fieldPlate->setStatus(\Battleship\Entity\FieldPlate::STATUS_NEW);
                $fieldPlate->setCoordinateX($row);
                $fieldPlate->setCoordinateY($col);
                $this->getEntityManager()->persist($fieldPlate);
                $this->getEntityManager()->flush();

                $this->gameGrid[$row][$col] = $fieldPlate;
            }
        }
    }

    /**
     * Setups a game board.
     *
     * @return array
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    public function setupBoard()
    {
        $battleshipGameSession = new Container('battleshipGameSession');
        $cheat = $battleshipGameSession->cheat;
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

            $fieldClass = array();
            if (!is_null($fieldPlate->getGameVessel())) {
                if ($cheat) {
                    $fieldClass[] = 'cheat';
                }
                $vesselStatus = $fieldPlate->getGameVessel()->getStatus();
                if ($vesselStatus == \Battleship\Entity\GameVessel::STATUS_SUNK) {
                    $fieldClass[] = 'sunk';
                }
            }
            $gameGrid[$fieldPlate->getCoordinateX()][$fieldPlate->getCoordinateY()] = array(
                'field_plate_status' => $fieldPlate->getStatus(),
                'content' => $content,
                'vessel_id' => $vesselId,
                'field_class' => $fieldClass,
            );
        }
        return $gameGrid;
    }

    /**
     * Deploys the battleships on the game battle field.
     *
     * @param array $gameVessels
     * @param $maxX
     * @param $maxY
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    private function deployShips(array $gameVessels, $maxX, $maxY)
    {
        foreach ($gameVessels as $gameVessel) {
            $vesselSize = $gameVessel->getVesselType()->getSize();
            $vesselDirection = rand(0, 1);
            $this->deploy($maxX, $maxY, $vesselSize, $vesselDirection, $gameVessel);
        }
    }

    private function deploy($maxX, $maxY, $vesselSize, $vesselDirection, $gameVessel)
    {
        // Prevent infinite loops.
        if ($this->vesselsDeployCounter > self::DEPLOYMENT_TRIES_THRESHOLD) {
            throw new Exception(sprintf('Too many tries to deploy ships. More than %d. Please, try again.', $this->vesselsDeployCounter), 108);
        }

        $this->vesselsDeployCounter++;
        $startX = $this->generateFirstPosition($maxX, $vesselSize, $vesselDirection);
        $startY = $this->generateFirstPosition($maxY, $vesselSize, $vesselDirection);

        if ($vesselDirection == self::VESSEL_POSITION_HORIZONTALLY) {
            // Deploy horizontally.
            $vesselCoordinates = array();
            for ($rowNumber = 1; $rowNumber < ($maxX + 1); $rowNumber++) {
                for ($colNumber = 1; $colNumber < ($maxY + 1); $colNumber++) {
                    if (
                        $colNumber >= $startX && $colNumber < ($startX + $vesselSize)
                        && $rowNumber == $startY
                    ) {
                        if (!is_null($this->gameGrid[$colNumber][$rowNumber]->getGameVessel())) {
                            $this->deploy($maxX, $maxY, $vesselSize, $vesselDirection, $gameVessel);
                        }
                        $vesselCoordinates[] = array('x' => $colNumber, 'y' => $rowNumber);
                    }
                }
            }
            $this->addVessel($gameVessel, $vesselCoordinates);
        } else if ($vesselDirection == self::VESSEL_POSITION_VERTICALLY) {
            // Deploy vertically.
            $vesselCoordinates = array();
            for($rowNumber = 1; $rowNumber < ($maxX + 1); $rowNumber++) {
                for($colNumber = 1; $colNumber < ($maxY + 1); $colNumber++) {
                    if (
                        $rowNumber >= $startY && $rowNumber < ($startY + $vesselSize)
                        && $colNumber == $startX
                    ) {
                        if (!is_null($this->gameGrid[$colNumber][$rowNumber]->getGameVessel())) {
                            $this->deploy($maxX, $maxY, $vesselSize, $vesselDirection, $gameVessel);
                        }
                        $vesselCoordinates[] = array('x' => $colNumber, 'y' => $rowNumber);
                    }
                }
            }
            $this->addVessel($gameVessel, $vesselCoordinates);
        } else {
            throw new InvalidArgumentException('Invalid Vessel direction detected.', 107);
        }
    }

    /**
     * Recursively determines the first position of every battleship.
     *
     * @param $max
     * @param $vesselSize
     * @param $vesselDirection
     * @return int
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    private function generateFirstPosition($max, $vesselSize, $vesselDirection)
    {
        $start = rand(1, $max);

        if ($start + $vesselSize > $max) {
            $start = $this->generateFirstPosition($max, $vesselSize, $vesselDirection);
        }
        return $start;
    }

    /**
     * Adds a new Game Vessel on the Field Plate.
     *
     * @param $gameVessel
     * @param $vesselCoordinates
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    private function addVessel($gameVessel, $vesselCoordinates)
    {
        $field = $this->getField();
        if (empty($field)) {
            throw new InvalidArgumentException('Invalid game field.', 101);
        }

        foreach ($vesselCoordinates as $vesselFieldCoordinates) {

            $fieldPlate = $this->getEntityManager()->getRepository('Battleship\Entity\FieldPlate')->findOneBy(array(
                'coordinateX' => $vesselFieldCoordinates['x'],
                'coordinateY' => $vesselFieldCoordinates['y'],
                'field' => $field,
            ));
            if (empty($fieldPlate)) {
                throw new InvalidElementException('Invalid game field plate.', 105);
            }

            $fieldPlate->setGameVessel($gameVessel);
            $this->getEntityManager()->persist($fieldPlate);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Base Fire Shot method used by all apps.
     *
     * @param $params
     * @return bool
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    public function fireShot($params)
    {
        $params['field'] = $this->getField();
        $fieldPlate = $this->getEntityManager()->getRepository('Battleship\Entity\FieldPlate')->findOneBy($params);

        if (empty($fieldPlate)) {
            throw new InvalidElementException('Invalid Filed Plate selected.', 104);
        }

        if ($fieldPlate->getStatus() != \Battleship\Entity\FieldPlate::STATUS_NEW) {
            throw new InvalidElementException('This field has been already fired upon.', 106);
        }

        $status = \Battleship\Entity\FieldPlate::STATUS_MISS;
        if (!is_null($fieldPlate->getGameVessel())) {
            $status = \Battleship\Entity\FieldPlate::STATUS_HIT;
        }
        $fieldPlate->setStatus($status);

        $this->getEntityManager()->persist($fieldPlate);

        $this->getGameEntity()->setMovesCnt($this->getGameEntity()->getMovesCnt() + 1);
        $this->getEntityManager()->persist($this->getGameEntity());

        $this->getEntityManager()->flush();

        $this->checkVessel($fieldPlate);

        return true;
    }

    /**
     * Converts given coordinates.
     *
     * @param $coordinatesIn
     * @return array
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    public static function convertCoordinates($coordinatesIn)
    {
        $x = substr($coordinatesIn, 0, 1);
        $y = substr($coordinatesIn, 1);

        // Use letters as X Coordinate.
        $x = array_search(strtoupper($x), self::$letters);

        return array('coordinateX' => $x, 'coordinateY' => $y);
    }

    /**
     * Loads Game Vessels Info.
     *
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    private function loadVesselsInfo()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->add('select', new Expr\Select(array('COUNT(field_plates.id)')));
        $qb->add('from', new Expr\From('Battleship\Entity\FieldPlate', 'field_plates'));
        $qb->add('where', $qb->expr()->andX(
            $qb->expr()->eq('field_plates.field', '?0'),
            $qb->expr()->eq('field_plates.status', '?1')
        ));
        $qb->setParameters(array(
            $this->getField()->getId(),
            \Battleship\Entity\FieldPlate::STATUS_HIT,
        ));
        $hitsCount = $qb->getQuery()->getSingleScalarResult();
        $this->setHits($hitsCount);

        $qb->setParameters(array(
            $this->getField()->getId(),
            \Battleship\Entity\FieldPlate::STATUS_MISS,
        ));
        $missedCount = $qb->getQuery()->getSingleScalarResult();
        $this->setMissedShots($missedCount);

        $vessels = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->add('select', new Expr\Select(array('COUNT(game_vessels.id)')));
        $qb->add('from', new Expr\From('Battleship\Entity\GameVessel', 'game_vessels'));
        $qb->add('where', $qb->expr()->andX(
            $qb->expr()->eq('game_vessels.game', '?0'),
            $qb->expr()->eq('game_vessels.vessel_type', '?1'),
            $qb->expr()->eq('game_vessels.status', '?2')
        ));

        foreach ($this->getGameVesselTypes() as $vesselType) {
            $qb->setParameters(array(
                $this->getGameEntity()->getId(),
                $vesselType->getId(),
                \Battleship\Entity\GameVessel::STATUS_INTACT,
            ));
            $intactCount = $qb->getQuery()->getSingleScalarResult();

            $qb->setParameters(array(
                $this->getGameEntity()->getId(),
                $vesselType->getId(),
                \Battleship\Entity\GameVessel::STATUS_HIT,
            ));
            $hitCount = $qb->getQuery()->getSingleScalarResult();

            $qb->setParameters(array(
                $this->getGameEntity()->getId(),
                $vesselType->getId(),
                \Battleship\Entity\GameVessel::STATUS_SUNK,
            ));
            $sunkCount = $qb->getQuery()->getSingleScalarResult();

            $vessels[$vesselType->getId()] = array(
                'intactCnt' => $intactCount,
                'hitCnt' => $hitCount,
                'sunkCnt' => $sunkCount,
            );
        }

        $this->setGameVesselsInfo($vessels);
    }

    /**
     * Checks and marks vessels as hit or sunk on fire if they stand on the given field plate.
     *
     * @param $fieldPlate
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    public function checkVessel($fieldPlate)
    {
        if (empty($fieldPlate)) {
            throw new InvalidArgumentException('No field plate is set.', 104);
        }
        $shotInfo = $this->getShotInfo();
        $shotInfo['hit'] = false;
        $shotInfo['sunk_vessel'] = false;
        $shotInfo['hit_vessel'] = null;
        if (!is_null($fieldPlate->getGameVessel())) {
            $vessel = $fieldPlate->getGameVessel();

            // Count Hit Parts.
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb->add('select', new Expr\Select(array('COUNT(field_palate.id)')));
            $qb->add('from', new Expr\From('Battleship\Entity\FieldPlate', 'field_palate'));
            $qb->add('where', $qb->expr()->andX(
                $qb->expr()->eq('field_palate.gameVessel', '?0'),
                $qb->expr()->eq('field_palate.status', '?1')
            ));
            $qb->setParameters(array(
                $fieldPlate->getGameVessel()->getId(),
                \Battleship\Entity\FieldPlate::STATUS_HIT,
            ));

            if (!empty($vessel)) {
                $hitVesselParts = $qb->getQuery()->getSingleScalarResult();
                $vesselTypes = $this->getGameVesselTypes();

                $vesselType = $vesselTypes[$vessel->getVesselType()->getId()];
                $status = \Battleship\Entity\GameVessel::STATUS_HIT;
                if ($vesselType->getSize() <= $hitVesselParts) {
                    $status = \Battleship\Entity\GameVessel::STATUS_SUNK;
                    $shotInfo['sunk_vessel'] = true;
                }
                $vessel->setStatus($status);
                $this->getEntityManager()->persist($vessel);
                $this->getEntityManager()->flush();
                $shotInfo['hit_vessel'] = $vessel;
            }
            $shotInfo['hit'] = true;
        }

        $this->setShotInfo($shotInfo);
    }

    /**********************Repository's Getters and Setters***********************/

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

    /**
     * @return mixed
     */
    public function getMissedShots()
    {
        return $this->missedShots;
    }

    /**
     * @param mixed $missedShots
     */
    public function setMissedShots($missedShots)
    {
        $this->missedShots = $missedShots;
    }

    /**
     * @return mixed
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * @param mixed $hits
     */
    public function setHits($hits)
    {
        $this->hits = $hits;
    }

    /**
     * @return mixed
     */
    public function getGameVesselsInfo()
    {
        return $this->gameVesselsInfo;
    }

    /**
     * @param mixed $gameVesselsInfo
     */
    public function setGameVesselsInfo($gameVesselsInfo)
    {
        $this->gameVesselsInfo = $gameVesselsInfo;
    }

    /**
     * @return mixed
     */
    public function getShotInfo()
    {
        return $this->shotInfo;
    }

    /**
     * @param mixed $shotInfo
     */
    public function setShotInfo($shotInfo)
    {
        $this->shotInfo = $shotInfo;
    }
}