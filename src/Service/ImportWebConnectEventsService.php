<?php

namespace Bolt\Extension\TwoKings\ImportWebConnect\Service;

use Silex\Application;
use Bolt\Storage\Entity\Content;
use \DateTime;

/**
 * ImportWebConnect service.
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class ImportWebConnectEventsService
{
    private $app;
    private $config;
    private $headers;           // headers for the request
    private $results;           // results from soap request
    private $eventsRepository; // content repository events
    private $client;
    private $logger;

    /**
     * Constructor.
     *
     * Sets up the environment needed for the import
     * Mostly initializes the repositories and configuration variables
     *
     * @param \Silex\Application $app
     */
    public function __construct(Application $app, $client, $logger)
    {
        $this->app = $app;
        $this->config = $app['importwebconnect.config'];
        $this->client = $client;
        $this->eventsRepository = $this->app['storage']->getRepository($this->config['remote']['get_events']['target']['contenttype']);
    }

    /**
     * This set the headers and payload, initates a SOAP request and returns the data
     */
    public function fetchData()
    {
        // ini_set('max_execution_time', (5*60)); //300 seconds = 5 minutes
        $this->setupHeaders();

        $useremote = $this->config['remote']['get_events']['enabled'];
        // only try to call the remote if the configuration allows us
        if ($useremote) {
            $target = $this->config['remote']['host'];
            $this->app['logger.system']->info('Importing from remote source: ' . $target, ['event' => 'import']);
            $this->webServiceCallRequest();
        } else {
            $this->app['logger.system']->error('No available source to import.', ['event' => 'import']);
            return false;
        }

        return $this->results;
    }

    /**
     * Set the headers for the SOAP request
     */
    private function setupHeaders()
    {
        $this->headers = $this->config['remote']['headers'];
    }

    /**
     * Perform the web service request
     */
    private function webServiceCallRequest()
    {
        $url = $this->config['remote']['host'] . $this->config['remote']['uri'];
        $options['headers'] = $this->headers;
        $options['query'] = $this->config['remote']['get_events']['query'];

        try {
            $this->results = $this->client->request('GET', $url, $options)->getBody();
            $this->results = json_decode($this->results);
        } catch (\Exception $e) {
            $this->errormessage = 'Error occurred during fetch of remote import source: ' . $e->getMessage();
            $this->app['logger.system']->error($this->errormessage, ['event' => 'import']);
            // return something empty
            $this->results = false;
        }
    }

    /**
     * Helper function to Truncate bolt_events Table
     */
    public function depublishAllEvents()
    {
      $tablename = $this->eventsRepository->getTableName();
      $active = $this->config['remote']['get_events']['target']['active'];
      $inactive = $this->config['remote']['get_events']['target']['inactive'];
      if ($active !== $inactive) {
        return $this->depublishSaveAllEvents(
            [
              'table' => $tablename,
              'field' => 'status',
              'value' => $inactive
            ]
          );
      }
    }

    /**
     * Set a field for all records
     *
     * Used to depublish all events before a new import with $this->depublishAllEvents()
     *
     * @param $newvalues
     */
    private function depublishSaveAllEvents($newvalues)
    {
      return $this->app['db']->prepare('UPDATE ' . $newvalues['table'] . ' SET ' . $newvalues['field'] . ' = "' . $newvalues['value'] . '"')->execute();
    }

    /**
     * Public wrapper for insertEvent
     */
    public function saveEvent($event)
    {
        return $this->insertEvent($event);
    }

    /**
     * Save an event to the contenttype given in the config
     *
     * @param $event: An event that has to be inserted
     *
     * @return string (message with some status info)
     */
    private function insertEvent($event)
    {
        $eventRecord = $this->eventsRepository->findOneBy(['event_id' => $event->event_id]);
        $message = 'Event: %s was updated (%d - %d)';
        // no record found - prepare a blank one
        if(!$eventRecord) {
            $eventRecord = new Content();
            $eventRecord->datepublish = new DateTime();
            $eventRecord->ownerid = $this->config['remote']['get_events']['target']['ownerid'];
            $message = 'Event: %s was inserted (%d - %d)';
        }
        $eventRecord->event_id = isset($event->event_id) ? $event->event_id : '' ;
        $eventRecord->title = isset($event->naam_event) ? $event->naam_event : '' ;
        // $eventRecord->subtitle = isset($event->subtitle) ? $event->subtitle : ''; Not in resulset from WebConnect
        $eventRecord->date = isset($event->datum) ? $event->datum : '' ;
        $eventRecord->location = isset($event->locatie) ? $event->locatie : '' ;
        $eventRecord->eventtype = isset($event->type_event) ? strtolower($event->type_event) : '' ;
        $eventRecord->additionele_info = isset($event->additionele_info) ? $event->additionele_info : '';
        $eventRecord->doelgroep = isset($event->doelgroep) ? $event->doelgroep : '';

        if(isset($event->informatie) && count($event->informatie) >=1) {

            $eventbody = '';
            foreach ($event->informatie as $info) {
                $eventbody .= $info->inhoud;
            }

            $eventRecord->body = $eventbody;
        }
        $eventRecord->starttime = isset($event->start_tijd) ? $event->start_tijd : '';
        $eventRecord->endtime = isset($event->eind_tijd) ? $event->eind_tijd : '';
        // $eventRecord->inschrijven_mogelijk = isset($event->inschrijven_mogelijk) ? $event->inschrijven_mogelijk : '' ; Not in resulset from WebConnect
        $eventRecord->pwo_punten = isset($event->pwo_punten) ? $event->pwo_punten : '';
        $eventRecord->subscribe_link = isset($event->subscribe_link) ? $event->subscribe_link : '';
        // $eventRecord->verslag = isset($event->verslag) ? $event->verslag : ''; Not in resulset from WebConnect
        $eventRecord->slug = $this->app['slugify']->slugify($event->naam_event . '-' . $event->event_id);
        $eventRecord->status = 'published';

        $this->eventsRepository->save($eventRecord);

        $message = sprintf($message, $eventRecord->title, $eventRecord->event_id, $eventRecord->id);

        return $message;
    }

}
