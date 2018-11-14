<?php

namespace Bolt\Extension\TwoKings\ImportWebConnect\Controller;

use Bolt\Controller\Base;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller class to handle WebConnect get_events call
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class ImportWebConnectEventsController extends Base
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $ctr)
    {
        $ctr
            ->match('/', [$this, 'importwebconnectBackendPage'])
            ->before([$this, 'before'])
            ->bind('webconnect.importevents.get');

        return $ctr;
    }

    /**
     * Check if the current user is logged in.
     *
     * @param Request $request
     * @param Application $app
     */
    public function before(Request $request, Application $app)
    {
        $token = $app['session']->get('authentication', false);

        if (!$token) {
            return $this->redirectToRoute('dashboard');
        }
    }

    /**
     * Callback function to exeute when /extensions/importwebconnect/events
     * route is matched.
     *
     * @param Application $app
     * @param Request $request
     */
    public function importwebconnectBackendPage(Request $request)
    {
        $results = $this->app['importwebconnect.events.service']->fetchData();
        $messages = [];

        $config = $this->app['importwebconnect.config'];
        $url = $config['remote']['host'] . $config['remote']['uri'];
        $options = $config['remote']['get_events']['query'];
        $message_url = $url . '?' . http_build_query($options);
        $messages[] = "Importing from: <a href='" . $message_url . "'>" . $url . "</a>";

        if ($request->query->get('confirmed') == 'looksgood') {
            $message = 'Starting WebConnect import from site.';
            $this->app['logger.system']->info($message, ['event' => 'import']);

            $messages[] = $message;

            $number_of_events = 0;
            $this->app['importwebconnect.events.service']->depublishAllEvents();

            foreach ($results->result as $event) {
                $messages[] = $this->app['importwebconnect.events.service']->saveEvent($event);
                $number_of_events++;
            }

            $message = 'Finished WebConnect import, ' . $number_of_events . ' events records imported.';
            $this->app['logger.system']->info($message, ['event' => 'import']);
            $messages[] = $message;
            $results = null;
        }

        $html = $this->render('@importwebconnect/import_webconnect_events.twig', [
            'title' => 'Import WebConnect Events',
            'results' => $results,
            'messages' => $messages
        ], []);

        return $html;
    }
}
