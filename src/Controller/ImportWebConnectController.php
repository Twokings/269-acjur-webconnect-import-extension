<?php

namespace Bolt\Extension\TwoKings\ImportWebConnect\Controller;

use Bolt\Controller\Base;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class ImportWebConnectController extends Base
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $ctr)
    {
        $ctr
            ->match('/extensions/importwebconnect', [$this, 'importwebconnectBackendPage'])
            ->before([$this, 'before'])
            ->bind('webconnect.import.get');

        return $ctr;
    }

    /**
     * Check if the current user is logged in.
     *
     * @param Request     $request
     * @param Application $app
     */
    public function before(Request $request, Application $app)
    {
        $token = $app['session']->get('authentication', false);

        if (! $token) {
            return $this->redirectToRoute('dashboard');
        }
    }

    /**
     *
     *
     * @param Application $app
     * @param Request     $request
     */
    public function importwebconnectBackendPage(Application $app, Request $request)
    {

    }
}
