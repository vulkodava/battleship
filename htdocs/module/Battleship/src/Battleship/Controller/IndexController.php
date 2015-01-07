<?php
namespace Battleship\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Doctrine\ORM\Query\Expr;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\View\Renderer\PhpRenderer;

class IndexController extends AbstractActionController implements EventManagerAwareInterface
{
    private $game;
    private $gameGrid;

    public function indexAction()
    {
        return new ViewModel();
    }

    /**
     * Create a new game Action
     *
     * @return \Zend\Http\Response
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    public function newGameAction()
    {
        $battleshipGameSession = new Container('battleshipGameSession');
        unset($battleshipGameSession->gameId);
        return $this->redirect()->toRoute('battleship/default', array(
            'controller' => 'index',
            'action' => 'play',
        ));
    }

    /**
     * Play Existing Game Action.
     *
     * @return ViewModel
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    public function playAction()
    {
        $this->gameCommonLogic();

        $cheat = $this->params('cheat', false);
        if ($cheat != 0 && $cheat != 1)  {
            $cheat = false;
        } else if ($cheat == 1) {
            $cheat = true;
        }

        // Generate the Web View.
        $view = new ViewModel();
        $view->setVariable('gameGrid', $this->gameGrid);
        $view->setVariable('game', $this->game);
        $view->setVariable('gameVesselTypes', $this->game->getGameVesselTypes());
        $view->setVariable('gameShots', $this->game->getGameEntity()->getMovesCnt());
        $view->setVariable('gameVesselsInfo', $this->game->getGameVesselsInfo());
        $view->setVariable('hits', $this->game->getHits());
        $view->setVariable('missed', $this->game->getMissedShots());
        $view->setVariable('cheat', $cheat);

        return $view;
    }

    /**
     * Console Application Main Action
     *
     * @return string
     * @throws \Exception
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    public function consoleAction()
    {
        // Get Console App Request.
        $request = $this->getRequest();
        $gameId = $request->getParam('id', false);

        // Determines whether this is a new game or an existing one.
        if ($gameId !== false) {
            $battleshipGameSession = new Container('battleshipGameSession');
            $battleshipGameSession->gameId = $gameId;
        }

        try {
            $this->gameCommonLogic();
        } catch (\Exception $e) {
            return $e->getMessage() . PHP_EOL;
        }

        // Get Request Params.
        $coordinates = $request->getParam('coordinates', false);
        $cheat = $request->getParam('cheat', false);

        if ($cheat != 0 && $cheat != 1)  {
            $cheat = false;
        } else if ($cheat == 1) {
            $cheat = true;
        }

        // In case of passed coordinates tries to fire a shot.
        if ($coordinates !== false) {
            try {
                $params = \Battleship\Repository\Game::convertCoordinates($coordinates);
                echo PHP_EOL;
                // Try to actually fire the shot.
                $this->game->fireShot($params);
                $shotInfo = $this->game->getShotInfo();
                $displayCoordinates =  \Battleship\Repository\Game::$letters[$params['coordinateX']];
                $displayCoordinates .= ($params['coordinateY'] + 1);
                if ($shotInfo['hit'] === true) {
                    if ($shotInfo['sunk_vessel'] === true) {
                        $sunkVessel = $shotInfo['hit_vessel']->getVesselType()->getName();
                        $sunkVessel .= ' #' . $shotInfo['hit_vessel']->getId();
                        echo sprintf('Vessel %s is sunk.', $sunkVessel) . PHP_EOL;
                    }
                    echo sprintf('Successful shot on field %s.', $displayCoordinates) . PHP_EOL;
                } else {
                    echo sprintf('Miss on field %s.', $displayCoordinates) . PHP_EOL;
                }
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }

        // Prepare Console View.
        $basePath = realpath(__DIR__ . '/../../../view/battleship');
        $renderer = new PhpRenderer();
        $renderer->resolver()->addPath($basePath);

        $view = new ViewModel();
        $view->setTemplate('index/console.phtml');
        $view->setVariable('gameGrid', $this->gameGrid);
        $view->setVariable('game', $this->game);
        $view->setVariable('gameVesselTypes', $this->game->getGameVesselTypes());
        $view->setVariable('gameShots', $this->game->getGameEntity()->getMovesCnt());
        $view->setVariable('gameVesselsInfo', $this->game->getGameVesselsInfo());
        $view->setVariable('hits', $this->game->getHits());
        $view->setVariable('missed', $this->game->getMissedShots());
        $view->setVariable('cheat', $cheat);

        $textContent = $renderer->render($view);
        return $textContent;
    }

    /**
     * Common controller logic for both - Web and Console Apps.
     *
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    private function gameCommonLogic()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $this->game = $objectManager->getRepository('Battleship\Entity\Game');
        $this->game->startGame();

        // Setup the Game Battle Field.
        $this->gameGrid = $this->game->setupBoard();
    }

    /**
     * Web Fire Shot Application
     *
     * @return \Zend\Http\Response
     * @author Momchil Milev <momchil.milev@gmail.com>
     */
    public function fireAction()
    {
        // Prepare to fire a Shot.
        if ($this->getRequest()->isPost()) {
            $objectManager = $this
                ->getServiceLocator()
                ->get('Doctrine\ORM\EntityManager');

            /** @var \Battleship\Repository\Game $game */
            $game = $objectManager->getRepository('Battleship\Entity\Game');
            $game->startGame();

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
        }
        return $this->redirect()->toRoute('battleship/default', array(
            'controller' => 'index',
            'action' => 'play',
        ));
    }
}