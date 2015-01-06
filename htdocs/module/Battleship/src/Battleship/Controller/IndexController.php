<?php
namespace Battleship\Controller;

use Battleship\Entity\Field;
use Battleship\Entity\GameVessel;
use Battleship\Entity\Player;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use ZendService\ReCaptcha\Exception; // We need this when using sessions
use Doctrine\ORM\Query\Expr;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;

class IndexController extends AbstractActionController implements EventManagerAwareInterface
{
    private $field = null;
    private $gameConfig = array();
    private $gameVesselTypes = array();

    public function indexAction()
    {
        return new ViewModel();
    }

    public function ajaxDoctrineAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $gameRepo = $objectManager->getRepository('Battleship\Entity\Game');
        $gameRepo->setPlayerId();

        $source = $objectManager->getRepository('Battleship\Entity\Game')->findAll();

        $table = new \ZfTable\Example\TableExample\Doctrine();
        $table->setAdapter($objectManager)
            ->setSource($source)
            ->setParamAdapter($this->getRequest()->getPost())
        ;

        return $this->getResponse()->setContent($table->render());
    }

    public function ajaxBaseAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $source = $objectManager->getRepository('Battleship\Entity\Game')->findAll();

        $table = new \ZfTable\Example\TableExample\Base();
        $table->setAdapter($objectManager)
            ->setSource($source)
            ->setParamAdapter($this->getRequest()->getPost())
        ;
        return $this->htmlResponse($table->render());
    }

    public function newGameAction()
    {
        $battleshipGameSession = new Container('battleshipGameSession');
        unset($battleshipGameSession->gameId);
        return $this->redirect()->toRoute('battleship/default', array(
            'controller' => 'index',
            'action' => 'play',
        ));
    }

    public function playAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $battleshipGameSession = new Container('battleshipGameSession');

        $this->gameVesselTypes = $objectManager->getRepository('Battleship\Entity\VesselType')
            ->findBy(array('status' => \Battleship\Entity\VesselType::STATUS_ACTIVE));

        $view = new ViewModel();

        if (isset($battleshipGameSession->gameId)) {
            $game = $objectManager->getRepository('Battleship\Entity\Game')->find($battleshipGameSession->gameId);
        } else {
            $game = $this->createGame();
        }

        if ($this->getRequest()->isPost()) {
            $coords = $this->params()->fromPost('field_coordinates');
            $convertedCoords = $this->convertCoords($coords);
            $params = $convertedCoords;
            $params['field'] = $game->getField();
            $fieldPlate = $objectManager->getRepository('Battleship\Entity\FieldPlate')->findOneBy($params);

            $status = \Battleship\Entity\FieldPlate::STATUS_MISS;
            if (!is_null($fieldPlate->getGameVessel())) {
                $status = \Battleship\Entity\FieldPlate::STATUS_HIT;
            }
            $fieldPlate->setStatus($status);

            $objectManager->persist($fieldPlate);

            $game->setMovesCnt($game->getMovesCnt() + 1);
            $objectManager->persist($game);

            $objectManager->flush();

            return $this->redirect()->toRoute('battleship/default', array(
                'controller' => 'index',
                'action' => 'play',
            ));
        }

        $gameGrid = $this->setupBoard($game->getField());

        $qb = $objectManager->createQueryBuilder();
        $qb->add('select', new Expr\Select(array('COUNT(field_plates.id)')));
        $qb->add('from', new Expr\From('Battleship\Entity\FieldPlate', 'field_plates'));
        $qb->add('where', $qb->expr()->andX(
            $qb->expr()->eq('field_plates.field', '?0'),
            $qb->expr()->eq('field_plates.status', '?1')
        ));
        $qb->setParameters(array(
            $game->getField()->getId(),
            \Battleship\Entity\FieldPlate::STATUS_HIT,
        ));
        $hitsCount = $qb->getQuery()->getSingleScalarResult();

        $qb->setParameters(array(
            $game->getField()->getId(),
            \Battleship\Entity\FieldPlate::STATUS_MISS,
        ));
        $missedCount = $qb->getQuery()->getSingleScalarResult();

        $vessels = array();
        $qb = $objectManager->createQueryBuilder();
        $qb->add('select', new Expr\Select(array('COUNT(game_vessels.id)')));
        $qb->add('from', new Expr\From('Battleship\Entity\GameVessel', 'game_vessels'));
        $qb->add('where', $qb->expr()->andX(
            $qb->expr()->eq('game_vessels.game', '?0'),
            $qb->expr()->eq('game_vessels.vessel_type', '?1'),
            $qb->expr()->eq('game_vessels.status', '?2')
        ));

        foreach ($this->gameVesselTypes as $vesselType) {
            $qb->setParameters(array(
                $game->getId(),
                $vesselType->getId(),
                \Battleship\Entity\GameVessel::STATUS_INTACT,
            ));
            $intactCount = $qb->getQuery()->getSingleScalarResult();

            $qb->setParameters(array(
                $game->getId(),
                $vesselType->getId(),
                \Battleship\Entity\GameVessel::STATUS_HIT,
            ));
            $hitCount = $qb->getQuery()->getSingleScalarResult();

            $qb->setParameters(array(
                $game->getId(),
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


        $view->setVariable('gameGrid', $gameGrid);
        $view->setVariable('gameId', $game->getId());
        $view->setVariable('gameVesselTypes', $this->gameVesselTypes);
        $view->setVariable('gameShots', $game->getMovesCnt());
        $view->setVariable('hits', $hitsCount);
        $view->setVariable('missed', $missedCount);
        $view->setVariable('vessels', $vessels);

        return $view;
    }

    private function convertCoords($coordsIn)
    {
        $letters = array(
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
        $x = substr($coordsIn, 0, 1);
        $y = substr($coordsIn, 1);

        $x = array_search($x, $letters);

        return array('coordinateX' => $x, 'coordinateY' => $y);
    }

    private function createGame() {
        $battleshipGameSession = new Container('battleshipGameSession');
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $gameConfig = $objectManager->getRepository('Battleship\Entity\GameConfig')->findAll();

        foreach ($gameConfig as $config) {
            $this->gameConfig[$config->getName()] = $config->getValue();
        }

        $this->getEventManager()->trigger('battleship.createNewGameStart', null, $this->gameConfig);
//        $field = new Field();
//        $field->setSizeX($this->gameConfig['x']);
//        $field->setSizeY($this->gameConfig['y']);
//        $field->setCreatedAt(new \DateTime());
//        $objectManager->persist($field);
//        $objectManager->flush();
//        $this->getEventManager()->trigger('battleship.createField', null, $field);

        $this->field = $field;

        // Create Field Plates.
        for ($row = 0; $row < $this->gameConfig['x']; $row++) {
            $gameGrid[$row] = array();
            for ($col = 0; $col < $this->gameConfig['y']; $col++) {
                $fieldPlate = new \Battleship\Entity\FieldPlate();
                $fieldPlate->setField($field);
                $fieldPlate->setStatus(\Battleship\Entity\FieldPlate::STATUS_NEW);
                $fieldPlate->setCoordinateX($row);
                $fieldPlate->setCoordinateY($col);
                $objectManager->persist($fieldPlate);
                $objectManager->flush();
                $this->getEventManager()->trigger('battleship.createFieldPlate', null, $fieldPlate);
            }
        }

        $player = new Player();
        $player->setUsername('guest');
        $player->setFirstName('Guest');
        $player->setLastName('Guest');
        $player->setCreatedAt(new \DateTime());
        $player->setStatus(Player::STATUS_ACTIVE);
        $objectManager->persist($player);
        $objectManager->flush();
        $this->getEventManager()->trigger('battleship.createPlayer', null, $player);

        $game = new \Battleship\Entity\Game();
        $game->setField($field);
        $game->setPlayer($player);

        $objectManager->persist($game);
        $objectManager->flush();
        $this->getEventManager()->trigger('battleship.createGame', null, $game);

        // Set gameId in session.
        $battleshipGameSession->gameId = $game->getId();

        $gameVessels = array();
        foreach ($this->gameVesselTypes as $vesselType) {
            for ($i = 0; $i < (int) $vesselType->getVesselsCount(); $i++) {
                $gameVessel = new GameVessel();
                $gameVessel->setGame($game);
                $gameVessel->setVesselType($vesselType);
                $gameVessel->setCoordinateX(1);
                $gameVessel->setCoordinateY('A');
                $gameVessel->setUpdatedAt(new \DateTime());
                $gameVessel->setStatus(\Battleship\Entity\GameVessel::STATUS_INTACT);
                $objectManager->persist($gameVessel);
                $objectManager->flush();
                $this->getEventManager()->trigger('battleship.createGameVessel', null, $gameVessel);
                $gameVessels[] = $gameVessel;
            }
        }
        $this->getEventManager()->trigger('battleship.readyToDeployVessels', null, $gameVessel);

        $this->deployShips($gameVessels, $this->gameConfig['x'], $this->gameConfig['y'], $field);

        return $game;
    }

    private function setupBoard($field)
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $gameGrid = array();
        $fieldPlates = $objectManager->getRepository('Battleship\Entity\FieldPlate')->findBy(array(
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

    private function deployShips(array $gameVessels, $maxX, $maxY, $field)
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
        if (empty($this->field)) {
            throw new InvalidArgumentException('Invalid game field.', 101);
        }
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $fieldPlate = $objectManager->getRepository('Battleship\Entity\FieldPlate')->findOneBy(array(
            'coordinateX' => $rowNumber,
            'coordinateY' => $colNumber,
            'field' => $this->field,
        ));

        $fieldPlate->setGameVessel($gameVessel);
        $objectManager->persist($fieldPlate);
        $objectManager->flush();
    }
}