<?php
namespace Battleship\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Doctrine\ORM\Query\Expr;
use Zend\EventManager\EventManagerAwareInterface;

class IndexController extends AbstractActionController implements EventManagerAwareInterface
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
            ->setParamAdapter($this->getRequest()->getPost());

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

        /** @var \Battleship\Repository\Game $game */
        $game = $objectManager->getRepository('Battleship\Entity\Game');
        $game->startGame();

        $view = new ViewModel();
        $cheat = $this->params('cheat', false);
        if ($cheat != 0 && $cheat != 1)  {
            $cheat = false;
        } else if ($cheat == 1) {
            $cheat = true;
        }

        // Prepare to fire a Shot.
        if ($this->getRequest()->isPost()) {
            $coordinates = $this->params()->fromPost('field_coordinates');
            $params = \Battleship\Repository\Game::convertCoordinates($coordinates);

            try {
                // Try to actually fire the shot.
                $game->fireShot($params);
                $shotInfo = $game->getShotInfo();
                $displayCoordinates =  \Battleship\Repository\Game::$letters[$params['coordinateX']];
                $displayCoordinates .= ($params['coordinateY'] + 1);
                if ($shotInfo['hit'] === true) {
                    if ($shotInfo['sunk_vessel'] === true) {
                        $sunkVessel = $shotInfo['hit_vessel']->getVesselType()->getName();
                        $sunkVessel .= ' #' . $shotInfo['hit_vessel']->getId();
                        $this->flashMessenger()->addSuccessMessage(sprintf('Vessel %s is sunk.', $sunkVessel));
                    }
                    $this->flashMessenger()->addSuccessMessage(sprintf('Successful shot on field %s.', $displayCoordinates));
                } else {
                    $this->flashMessenger()->addErrorMessage(sprintf('Miss on field %s.', $displayCoordinates));
                }
            } catch (\Exception $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }
            return $this->redirect()->toRoute('battleship/default', array(
                'controller' => 'index',
                'action' => 'play',
            ));
        }

        // Setup the Game Battle Field.
        $gameGrid = $game->setupBoard();

        $view->setVariable('gameGrid', $gameGrid);
        $view->setVariable('game', $game);
        $view->setVariable('gameVesselTypes', $game->getGameVesselTypes());
        $view->setVariable('gameShots', $game->getGameEntity()->getMovesCnt());
        $view->setVariable('gameVesselsInfo', $game->getGameVesselsInfo());
        $view->setVariable('hits', $game->getHits());
        $view->setVariable('missed', $game->getMissedShots());
        $view->setVariable('cheat', $cheat);

        return $view;
    }
}