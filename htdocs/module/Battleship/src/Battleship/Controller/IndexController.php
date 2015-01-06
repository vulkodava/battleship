<?php
namespace Battleship\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use ZendService\ReCaptcha\Exception; // We need this when using sessions
use Doctrine\ORM\Query\Expr;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;

class IndexController extends AbstractActionController implements EventManagerAwareInterface
{
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
        $game = new \Battleship\Model\Game($this->getServiceLocator());

        $view = new ViewModel();

        if ($this->getRequest()->isPost()) {
            $coordinates = $this->params()->fromPost('field_coordinates');
            $params = \Battleship\Model\Game::convertCoordinates($coordinates);

            try {
                $game->fireShot($params);
            } catch (\Exception $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }
            return $this->redirect()->toRoute('battleship/default', array(
                'controller' => 'index',
                'action' => 'play',
            ));
        }

        $gameGrid = $game->setupBoard();

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
        $view->setVariable('gameId', $game->getGameEntity()->getId());
        $view->setVariable('gameVesselTypes', $game->getGameVesselTypes());
        $view->setVariable('gameShots', $game->getGameEntity()->getMovesCnt());
        $view->setVariable('hits', $hitsCount);
        $view->setVariable('missed', $missedCount);
        $view->setVariable('vessels', $vessels);

        return $view;
    }
}