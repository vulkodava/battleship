<?php
namespace Battleship\Controller;

use Battleship\Entity\Field;
use Battleship\Entity\GameVessel;
use Battleship\Entity\Player;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

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
        $view = new ViewModel();
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $field = new Field();
        $field->setSizeX(10);
        $field->setSizeY(10);
        $field->setCreatedAt(new \DateTime());
        $objectManager->persist($field);
        $objectManager->flush();

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

        $gameConfigX = $objectManager->getRepository('Battleship\Entity\GameConfig')
            ->findOneBy(array('name' => 'x'));
        $gameConfigY = $objectManager->getRepository('Battleship\Entity\GameConfig')
            ->findOneBy(array('name' => 'y'));
        $gameGrid = $this->setupBoard($gameConfigX->getValue(), $gameConfigY->getValue());

        $gameGridWithShips = $this->deployShips($gameGrid, $gameVessels, $gameConfigX->getValue(), $gameConfigY->getValue());
        $view->setVariable('gameGrid', $gameGridWithShips);

        return $view;
    }

    private function setupBoard($x, $y)
    {
        $gameGrid = array();
        for ($row = 0; $row < $x; $row++) {
            $gameGrid[$row] = array();
            for ($col = 0; $col < $y; $col++) {
                $gameGrid[$row][$col] = 0;
            }
        }

        return $gameGrid;
    }

    private function deployShips(array $gameGrid, array $gameVessels, $maxX, $maxY)
    {
        foreach ($gameVessels as $gameVessel) {
            $vesselSize = $gameVessel->getVesselType()->getSize();
            $vesselId = $gameVessel->getId();
            $vesselDirection = rand(0, 1);
            $startX = $this->generateFirstPosition($maxX, $vesselSize);
            $startY = $this->generateFirstPosition($maxY, $vesselSize);

            if ($vesselDirection == 1) {
                // Deploy horizontally.
                foreach ($gameGrid as $rowNumber => $row) {
                    foreach ($row as $colNumber => $col) {
                        if (
                            $colNumber >= $startX && $colNumber < ($startX + $vesselSize)
                            && $rowNumber == $startY
                        ) {
                            $gameGrid[$rowNumber][$colNumber] = $vesselId;
                        }
                    }
                }
            } else {
                // Deploy vertically.
                foreach ($gameGrid as $rowNumber => $row) {
                    foreach ($row as $colNumber => $col) {
                        if (
                            $rowNumber >= $startY && $rowNumber < ($startY + $vesselSize)
                            && $colNumber == $startX
                        ) {
                            $gameGrid[$rowNumber][$colNumber] = $vesselId;
                        }
                    }
                }
            }
        }

        return $gameGrid;
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