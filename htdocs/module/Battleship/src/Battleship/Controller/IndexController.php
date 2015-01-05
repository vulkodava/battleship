<?php
namespace Battleship\Controller;

use Battleship\Entity\Field;
use Battleship\Entity\GameVessel;
use Battleship\Entity\Player;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container; // We need this when using sessions

class IndexController extends AbstractActionController
{
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

    public function playAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $battleshipGameSession = new Container('battleshipGameSession');

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
            if ($this->hasVessel($fieldPlate) === true) {
                $status = \Battleship\Entity\FieldPlate::STATUS_HIT;
            }
            $fieldPlate->setStatus($status);

            $objectManager->persist($fieldPlate);
            $objectManager->flush();
            return $this->redirect()->toRoute('battleship/default', array(
                'controller' => 'index',
                'action' => 'play',
            ));
        }

        $gameGrid = $this->setupBoard($game->getField(), $game->getVessels());
        $view->setVariable('gameGrid', $gameGrid);

        return $view;
    }

    private function hasVessel($fieldPlate)
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $vesselCoordinate = $objectManager->getRepository('Battleship\Entity\VesselCoordinate')->findOneBy(array('plate_coordinate' => $fieldPlate));
        if (!empty($vesselCoordinate)) {
            $vesselCoordinate->setStatus(\Battleship\Entity\VesselCoordinate::STATUS_HIT);
            $objectManager->persist($vesselCoordinate);
            $objectManager->flush();
            return true;
        }
        return false;
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

        $configData = array();
        foreach ($gameConfig as $config) {
            $configData[$config->getName()] = $config->getValue();
        }

        $field = new Field();
        $field->setSizeX($configData['x']);
        $field->setSizeY($configData['y']);
        $field->setCreatedAt(new \DateTime());
        $objectManager->persist($field);
        $objectManager->flush();

        // Create Field Plates.
        for ($row = 0; $row < $configData['x']; $row++) {
            $gameGrid[$row] = array();
            for ($col = 0; $col < $configData['y']; $col++) {
                $fieldPlate = new \Battleship\Entity\FieldPlate();
                $fieldPlate->setField($field);
                $fieldPlate->setStatus(\Battleship\Entity\FieldPlate::STATUS_NEW);
                $fieldPlate->setCoordinateX($row);
                $fieldPlate->setCoordinateY($col);
                $objectManager->persist($fieldPlate);
                $objectManager->flush();
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

        $game = new \Battleship\Entity\Game();
        $game->setField($field);
        $game->setPlayer($player);

        $objectManager->persist($game);
        $objectManager->flush();

        // Set gameId in session.
        $battleshipGameSession->gameId = $game->getId();

        $gameVessels = array();
        $gameVesselTypes = $objectManager->getRepository('Battleship\Entity\VesselType')
            ->findBy(array('status' => \Battleship\Entity\VesselType::STATUS_ACTIVE));
        foreach ($gameVesselTypes as $vesselType) {
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
                $gameVessels[] = $gameVessel;
            }
        }

        $this->deployShips($gameVessels, $configData['x'], $configData['y'], $field);

        return $game;
    }

    private function setupBoard($field, $vessels)
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $gameGrid = array();
        $fieldPlates = $objectManager->getRepository('Battleship\Entity\FieldPlate')->findBy(array(
            'field' => $field,
        ));
        foreach ($fieldPlates as $fieldPlate) {
            $vesselCoordinate = $objectManager->getRepository('Battleship\Entity\VesselCoordinate')->findOneBy(array(
                'plate_coordinate' => $fieldPlate,
            ));
            $content = 'empty';
            if (isset($vesselCoordinate) && !empty($vesselCoordinate)) {
                $content = $vesselCoordinate->getVessel()->getVesselType()->getName();
            }
            $gameGrid[$fieldPlate->getCoordinateX()][$fieldPlate->getCoordinateY()] = array(
                'field_plate_status' => $fieldPlate->getStatus(),
                'content' => $content,
            );
        }

        foreach ($vessels as $vessel) {
            $vesselCoordinates = $vessel->getVesselCoordinates();
            foreach ($vesselCoordinates as $vesselCoordinate) {
                $x = $vesselCoordinate->getPlateCoordinate()->getCoordinateX();
                $y = $vesselCoordinate->getPlateCoordinate()->getCoordinateY();
                $gameGrid[$x][$y]['content'] = $vessel->getVesselType()->getName();
            }
        }
        return $gameGrid;
    }

    private function deployShips(array $gameVessels, $maxX, $maxY, $field)
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        foreach ($gameVessels as $gameVessel) {
            $vesselCoordinates = $gameVessel->getVesselCoordinates();
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
                            $fieldPlate = $objectManager->getRepository('Battleship\Entity\FieldPlate')->findOneBy(array(
                                'coordinateX' => $rowNumber,
                                'coordinateY' => $colNumber,
                                'field' => $field,
                            ));

                            $gameVesselCoordinate = new \Battleship\Entity\VesselCoordinate();
                            $gameVesselCoordinate->setVessel($gameVessel);
                            $gameVesselCoordinate->setPlateCoordinateId($fieldPlate->getId());
                            $gameVesselCoordinate->setStatus(\Battleship\Entity\VesselCoordinate::STATUS_INTACT);
                            $objectManager->persist($gameVesselCoordinate);
                            $objectManager->flush();
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
                            $fieldPlate = $objectManager->getRepository('Battleship\Entity\FieldPlate')->findOneBy(array(
                                'coordinateX' => $rowNumber,
                                'coordinateY' => $colNumber,
                                'field' => $field,
                            ));

                            $gameVesselCoordinate = new \Battleship\Entity\VesselCoordinate();
                            $gameVesselCoordinate->setVessel($gameVessel);
                            $gameVesselCoordinate->setPlateCoordinateId($fieldPlate->getId());
                            $gameVesselCoordinate->setStatus(\Battleship\Entity\VesselCoordinate::STATUS_INTACT);
                            $objectManager->persist($gameVesselCoordinate);
                            $objectManager->flush();
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

    private function addVessels($vesselType)
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $vessel = $objectManager->getRepository('Battleship\Entity\GameVessel');
        $vessel->setGameId();
    }
}