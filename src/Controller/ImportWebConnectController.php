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
            ->match('/', [$this, 'importwebconnectBackendPage'])
            ->before([$this, 'before'])
            ->bind('webconnect.importwebconnect');

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
    public function importwebconnectBackendPage(Request $request)
    {
        $html = $this->render('@importwebconnect/import_webconnect.twig', [
            'title'  => 'Import WebConnect',
        ], []);

        return $html;
    }
}
