<?php
namespace Battleship\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Doctrine\ORM\Query\Expr;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\View\Renderer\PhpRenderer;
use ZendService\ReCaptcha\Exception;
use Zend\Json\Json;

class IndexController extends AbstractActionController implements EventManagerAwareInterface
{
    private $game;
    private $gameGrid;
    private $battleshipGameSession;

    public function __construct()
    {
        $this->battleshipGameSession = new Container('battleshipGameSession');
    }

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
        unset($this->battleshipGameSession->gameId);
        unset($this->battleshipGameSession->cheat);
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
        try {
            $this->gameCommonLogic();
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage($e->getMessage());
            return $this->redirect()->toRoute('home');
        }

        $cheat = $this->params('cheat', false);
        if ($cheat != 0 && $cheat != 1)  {
            $cheat = false;
        } else if ($cheat == 1) {
            $cheat = true;
        }

        $this->battleshipGameSession->cheat = $cheat;

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
            $this->battleshipGameSession->gameId = $gameId;
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
        $this->battleshipGameSession->cheat = $cheat;

        // In case of passed coordinates tries to fire a shot.
        if ($coordinates !== false) {
            try {
                $params = \Battleship\Repository\Game::convertCoordinates($coordinates);
                echo PHP_EOL;
                // Try to actually fire the shot.
                $this->game->fireShot($params);
                $shotInfo = $this->game->getShotInfo();
                $displayCoordinates =  \Battleship\Repository\Game::$letters[$params['coordinateX']];
                $displayCoordinates .= $params['coordinateY'];
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
        $messages = array();
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
                $displayCoordinates .= $params['coordinateY'];
                if ($shotInfo['hit'] === true) {
                    if ($shotInfo['sunk_vessel'] === true) {
                        $sunkVessel = $shotInfo['hit_vessel']->getVesselType()->getName();
                        $sunkVessel .= ' #' . $shotInfo['hit_vessel']->getId();

                        $messages[] = array(
                            'type' => 'success',
                            'text' => sprintf('Vessel %s is sunk.', $sunkVessel),
                        );
                    }
                    $messages[] = array(
                        'type' => 'success',
                        'text' => sprintf('Successful shot on field %s.', $displayCoordinates),
                    );
                } else {
                    $messages[] = array(
                        'type' => 'error',
                        'text' => sprintf('Miss on field %s.', $displayCoordinates),
                    );
                }
            } catch (\Exception $e) {
                $messages[] = array(
                    'type' => 'error',
                    'text' => $e->getMessage()
                );
            }
        }

        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->fireAjax($messages);
        } else {
            return $this->fireNonAjax($messages);
        }
    }

    private function fireAjax($messages)
    {
        $data = array(
            'success' => true,
            'messages' => $messages,
        );

        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setContent(Json::encode($data));

        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'application/json');

        return $response;
    }

    private function fireNonAjax($messages)
    {
        foreach ($messages as $message) {
            if ($message['type'] == 'error') {
                $this->flashMessenger()->addErrorMessage($message['text']);
            } else if ($message['type'] == 'success') {
                $this->flashMessenger()->addSuccessMessage($message['text']);
            }
        }

        return $this->redirect()->toRoute('battleship/default', array(
            'controller' => 'index',
            'action' => 'play',
            'cheat' => (int)$this->battleshipGameSession->cheat,
        ));
    }
}