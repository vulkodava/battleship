<?php
namespace Battleship\Controller;

use Battleship\Entity\Field;
use Battleship\Entity\Player;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
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
        $player->setUsername('vulkodava');
        $player->setFirstName('Momchil');
        $player->setLastName('Milev');
        $player->setCreatedAt(new \DateTime());
        $player->setStatus(Player::STATUS_ACTIVE);
        $objectManager->persist($player);
        $objectManager->flush();

        $game = new \Battleship\Entity\Game();
        $game->setField($field);
        $game->setPlayer($player);

        $objectManager->persist($game);
        $objectManager->flush();
        return new ViewModel();
    }

    public function playAction()
    {
        return new ViewModel();
    }
}