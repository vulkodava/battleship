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

        $gameVesselTypes = $objectManager->getRepository('Battleship\Entity\VesselType')
            ->findBy(array('status' => \Battleship\Entity\VesselType::STATUS_ACTIVE));
        foreach ($gameVesselTypes as $vesselType) {
            $gameVessel = new GameVessel();
            $gameVessel->setGame($game);
            $gameVessel->setVesselId($vesselType->getId());
            $gameVessel->setCoordinateX(1);
            $gameVessel->setCoordinateY('A');
            $gameVessel->setUpdatedAt(new \DateTime());
            $gameVessel->setStatus(\Battleship\Entity\GameVessel::STATUS_INTACT);
            $objectManager->persist($gameVessel);
            $objectManager->flush();
        }
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