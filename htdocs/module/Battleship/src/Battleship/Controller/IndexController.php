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
        return new ViewModel();
    }

    public function ajaxDoctrineAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $table = new \ZfTable\Example\TableExample\Doctrine();
        $table->setAdapter($this->getDbAdapter())
            ->setSource($objectManager->fetchAll())
            ->setParamAdapter($this->getRequest()->getPost())
        ;

        return $this->htmlResponse($table->render());
    }

    public function ajaxBaseAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $sm = $this->getServiceLocator();
        var_dump($sm); die;
        $dbAdapter      = $sm->get('Zend\Db\Adapter\Adapter');
        var_dump($dbAdapter); die;
        $db = $objectManager->getDbAdapter();
        var_dump($db); die;

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
        return new ViewModel();
    }

    public function createGameAction()
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
    }
}