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
class ImportWebConnectCursussenService
{
    private $app;
    private $config;
    private $headers;           // headers for the request
    private $results;           // results from soap request
    private $cursussenRepository; // content repository cursussen
    private $planningenRepository; // content repository planningen
    private $docentenRepository; // content repository docenten
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
        $this->cursussenRepository = $this->app['storage']->getRepository($this->config['remote']['get_courses']['target']['contenttype']);
        $this->planningenRepository = $this->app['storage']->getRepository($this->config['remote']['get_courses']['target']['planningcontenttype']);
        $this->docentenRepository = $this->app['storage']->getRepository($this->config['remote']['get_courses']['target']['docentencontenttype']);
    }

    /**
     * This set the headers and payload, initates a SOAP request and returns the data
     */
    public function fetchData()
    {
        // ini_set('max_execution_time', (5*60)); //300 seconds = 5 minutes
        $this->setupHeaders();

        // $uselocal = $this->config['local']['enabled'];
        $useremote = $this->config['remote']['get_courses']['enabled'];
        // only try to call the remote if the configuration allows us
        if ($useremote) {
            $target = $this->config['remote']['host'];
            $this->app['logger.system']->info('Importing from remote source: ' . $target, ['event' => 'import']);
            $this->webServiceCallRequest();
        } else {
            $this->app['logger.system']->error('No available source to import.', ['event' => 'import']);
            return false;
        }
// dump($this->results);
        foreach ($this->results->result as $result) {
            # code...

            // dump($result); die();
            if(!is_string($result) && property_exists($result, 'informatie') && is_array($result->informatie)){

                // dump($result);
            }
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
        $options['query'] = $this->config['remote']['get_courses']['query'];

        try {
            $this->results = $this->client->request('GET', $url, $options)->getBody();
            $this->results = json_decode($this->results);
            $this->app['logger.system']->error('Imported: '. $url, ['event' => 'import']);
        } catch (\Exception $e) {
            $this->errormessage = 'Error occurred during fetch of remote import source: ' . $e->getMessage();
            $this->app['logger.system']->error($this->errormessage, ['event' => 'import']);
            $this->app['logger.system']->error('Failed: '. $url, ['event' => 'import']);
            // return something empty
            $this->results = false;
        }
    }

    /**
     *
     *
     */
    private function importWebConnectData($data)
    {
        $cursussen = $data->result;

        foreach($cursussen as $cursuskey => $cursus ) {
            $this->saveCursus($cursus);
        }
    }

    /**
     * Public wrapper for saveAllCursussen
     */
    public function depublishAllCursussen()
    {
      $tablename = $this->cursussenRepository->getTableName();
      $active = $this->config['remote']['get_courses']['target']['active'];
      $inactive = $this->config['remote']['get_courses']['target']['inactive'];
      if ($active !== $inactive) {
        return $this->depublishSaveAllCursussen(
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
     * Used to depublish all cursussen before a new import with $this->depublishAllCursussen
     *
     * @param $newvalues
     */
    private function depublishSaveAllCursussen($newvalues)
    {
      return $this->app['db']->prepare('UPDATE ' . $newvalues['table'] . ' SET ' . $newvalues['field'] . ' = "' . $newvalues['value'] . '"')->execute();
    }

    /**
     * Public wrapper for deletePlannigen
     */
    public function depublishAllPlanningenByCursus($cursus_id)
    {
      $tablename = $this->planningenRepository->getTableName();
      return $this->deletePlannigen([
        'table' => $tablename,
        'field' => 'cursus_id',
        'value' => $cursus_id
      ]);
    }

    /**
     * Delete all planningen that match a condition
     *
     * Used by $this->depublishAllPlanningenByCursus
     *
     * @param $newvalues
     */
    private function deletePlannigen($newvalues)
    {
      return $this->app['db']->prepare('DELETE FROM ' . $newvalues['table'] . ' WHERE ' . $newvalues['field'] . ' = "' . $newvalues['value'] . '"')->execute();
    }

    /**
     * Public wrapper for insertCursus
     */
    public function saveCursus($cursus)
    {
        return $this->insertCursus($cursus);
    }

    /**
     * Save a cursus to the contenttype given in the config
     *
     * Cursussen with negative studiepunten are unpublished by default
     * Also saves planningen for a cursus if they exist
     *
     * @param $cursus: A cursus that has gone through $this->parseNiceRecord
     *
     * @return string (message with some status info)
     */
    private function insertCursus($cursus)
    {
        $cursusRecord = $this->cursussenRepository->findOneBy(['cursusid' => $cursus->cursus_id]);
        $message = 'Cursus: %s was updated (%d - %d)';

        // no record found - prepare a blank one
        if(!$cursusRecord) {
            $cursusRecord = new Content();
            $cursusRecord->datepublish = new DateTime();
            $cursusRecord->ownerid = $this->config['remote']['get_courses']['target']['ownerid'];
            $message = 'Cursus: %s was inserted (%d - %d)';
        }

        $cursusRecord->naam = isset($cursus->naam_cursus) ? $cursus->naam_cursus : '' ;
        // $cursusRecord->academie = $cursus->academie; Not in resulset from WebConnect
        $cursusRecord->theme = isset($cursus->themas) ? implode(', ', $cursus->themas) : '';
        // $cursusRecord->level = isset($cursus->level) ? $cursus->level : ''; Not in resulset from WebConnect
        $cursusRecord->pwo = isset($cursus->pwo_punten) ? $cursus->pwo_punten : '';
        $cursusRecord->new = isset($cursus->notitie) ? $cursus->notitie : '';
        // $cursusRecord->show_as_new = $cursus->show_as_new Not in resulset from WebConnect
        // $cursusRecord->comment = $cursus->comment Not in resulset from WebConnect
        // $cursusRecord->docent = isset($cursus->docent) ? $cursus->docent : ''; TODO: waiting for answer which docent goes in this field

        if(isset($cursus->informatie) && count($cursus->informatie) >=1) {

                $cursusbody = '';
                $cursusgoals = '';
                foreach ($cursus->informatie as $info) {
                    if($info->titel == "Inhoud") {
                        $cursusbody .= $info->inhoud;
                    } elseif($info->titel == "Resultaat") {
                        $cursusgoals .= $info->inhoud;
                    } else {
                        $cursusbody .= $info->inhoud;
                    }
                }
                $cursusRecord->body = $cursusbody;
                $cursusRecord->goals = $cursusgoals;
        }

        $cursusRecord->cost = isset($cursus->prijzen) ? $this->parsePrices($cursus->prijzen) : '';
        // $cursusRecord->length = $cursus->length Not in resulset from WebConnect
        // $cursusRecord->targetaudience = isset($cursus->targetaudience) ? $cursus->targetaudience : ''; Not in resulset from WebConnect
        // $cursusRecord->uitgelicht = $cursus->uitgelicht Not in resulset from WebConnect
        // $cursusRecord->uitgelichttext = $cursus->uitgelichttext Not in resulset from WebConnect

        if($cursus->aantal_deelnemers >= $cursus->max_deelnemers) {
            $cursusRecord->inschrijven_mogelijk = 1;
        } else {
            $cursusRecord->inschrijven_mogelijk = 0;
        }

        // link is a reserved name, so rewrite it to inschrijf_link
        $cursusRecord->inschrijf_link = isset($cursus->link) ? $cursus->link : '' ;

        // $cursusRecord->formulier = $cursus->formulier Not in resulset from WebConnect
        $cursusRecord->start_date = isset($cursus->start_datum) ? $cursus->start_datum : '';
        $cursusRecord->end_date = isset($cursus->eind_datum) ? $cursus->eind_datum : '';
        // $cursusRecord->estimate_date = $cursus->estimate_date Not in resulset from WebConnect
        // $cursusRecord->dates = isset($cursus->dates) ? $this->parsePrices($cursus->dates) : ''; Not in resulset from WebConnect
        // $cursusRecord->newdate = $cursus->newdate Not in resulset from WebConnect
        // $cursusRecord->review = isset($cursus->review) ? $this->parsePrices($cursus->review) : ''; Not in resulset from WebConnect
        // $cursusRecord->review_image = $cursus->review_image Not in resulset from WebConnect
        // $cursusRecord->searchname = isset($cursus->searchname) ? $this->parsePrices($cursus->searchname) : ''; Not in resulset from WebConnect
        $cursusRecord->cursusid = isset($cursus->cursus_id) ? $cursus->cursus_id : '';
        // $cursusRecord->projectcode = isset($cursus->projectcode) ? $this->parsePrices($cursus->projectcode) : ''; Not in resulset from WebConnect
        // $cursusRecord->notities = $cursus->notities Not in resulset from WebConnect
        $cursusRecord->slug = $this->app['slugify']->slugify($cursus->naam_cursus);
        $cursusRecord->status = $this->config['remote']['get_courses']['target']['active'];

        $this->cursussenRepository->save($cursusRecord);

        if (!empty($cursus->rooster) && count($cursus->rooster) >= 1) {
            $count = count($cursus->rooster);
            // echo '<p>saving ' . $count . ' cursusplanningen for '. $cursusRecord->id . "- $cursusRecord->naam" . '</p>';
            $this->savePlanningen($cursus);
        }

        if (!empty($cursus->docent) && count($cursus->docent) >= 1) {
            $count = count($cursus->docent);
            foreach ($cursus->docent as $docent) {
                $this->saveDocent($docent);
            }
        }

        $message = sprintf($message, $cursusRecord->naam, $cursusRecord->cursusid, $cursusRecord->id);

        return $message;

    }

    private function parsePrices($prices)
    {
        $parsedPrices = [];

        foreach ($prices as $price) {
            $price = number_format($price['bedrag_excl'], 2, '.', '');
            $priceText = $price['omschrijving'];
            $parsedPrices = $price . ' ' . $priceText;
        }

        $parsedPrices = implode(', ', $parsedPrices);

        return $parsedPrices;
    }

    /**
     * Save Related planning
     *
     * This part removes all planningen for a given cursus ($cursus->uitvoering_id) before inserting the new ones
     *
     * @param $cursus
     * @param $record
     */
    private function savePlanningen($cursus)
    {
        $this->depublishAllPlanningenByCursus($cursus->uitvoering_id);
        foreach($cursus->rooster as $planning) {
            $planrecord = $this->planningenRepository->findOneBy(['rooster_id' => $planning->rooster_id, 'cursus_id' => $cursus->uitvoering_id]);

            if(!$planrecord) {
                // echo '<p>saving cursusplanning '.$planning->start_tijd .' for '. $cursus->uitvoering_id . '</p>';
                $planrecord = new Content();
                $planrecord->datepublish = new DateTime();
                $planrecord->ownerid = $this->config['remote']['get_courses']['target']['ownerid'];
                $planrecord->status = 'published';
                $planrecord->onderwerp = $planning->naam;
                $planrecord->slug = $this->app['slugify']->slugify($planning->naam);
                $startdate = date("Y-m-d H:i:s", strtotime($planning->datum . ' ' . $planning->start_tijd));
                $planrecord->start_date = $startdate;
                $enddate = date("Y-m-d H:i:s", strtotime($planning->datum . ' ' . $planning->eind_tijd));
                $planrecord->end_date = $enddate;
                $planrecord->cursus_id = $cursus->uitvoering_id;
                $planrecord->rooster_id = $planning->rooster_id;
                $planrecord->locatie = $planning->locatie;
                $docentenIds = [];
                foreach($planning->docenten as $docent) {
                    array_push($docentenIds, $docent->id);
                }
                $planrecord->docent = join(',', $docentenIds); //Comma separeted list of IDs

            } elseif( $this->config['save_only'] == false ) {
                $planrecord->ownerid = $this->config['remote']['get_courses']['target']['ownerid'];
                $planrecord->status = 'published';
                $planrecord->onderwerp = $planning->naam;
                $startdate = date("Y-m-d H:i:s", strtotime($planning->datum . ' ' . $planning->start_tijd));
                $planrecord->start_date = $startdate;
                $enddate = date("Y-m-d H:i:s", strtotime($planning->datum . ' ' . $planning->eind_tijd));
                $planrecord->end_date = $enddate;
                $planrecord->locatie = $planning->locatie;
                $docentenIds = [];
                foreach($planning->docenten as $docent) {
                    array_push($docentenIds, $docent->id);
                }
                $planrecord->docent = join(',', $docentenIds); //Comma separeted list of IDs
            }

            $this->planningenRepository->save($planrecord);
        }
    }

    /**
     * Save/Update Docenten from Cursussen
     *
     * This function will Save a new Docent or Update a Docent if it already exists.
     * For updates to work 'save_only' option has to be set to false in extensions' cofig.yml
     *
     * @param $cursus
     * @param $record
     */
    private function saveDocent($docent)
    {
        $docentRecord = $this->docentenRepository->findOneBy(['docent_id' => $docent->docent_id]);

        if(!$docentRecord) {
            $message = 'Docent: %s was inserted (%d - %d)';
            $docentRecord = new Content();
            $docentRecord->datepublish = new DateTime();
            $docentRecord->datecreated = new DateTime();
            $docentRecord->ownerid = $this->config['remote']['get_courses']['target']['ownerid'];
            $docentRecord->slug = $this->app['slugify']->slugify($docent->naam_docent);
            $docentRecord->docent_id = $docent->docent_id;
            $docentRecord->naam_docent = $docent->naam_docent;
            $docentRecord->functie = $docent->functie;
            $docentRecord->naam_bedrijf = $docent->naam_bedrijf;
        } elseif($this->config['save_only'] == false) {
            $docentRecord->datechanged = new DateTime();
            $docentRecord->naam_docent = $docent->naam_docent;
            $docentRecord->functie = $docent->functie;
            $docentRecord->naam_bedrijf = $docent->naam_bedrijf;
        }

        $docentRecord->status = 'published';

        $this->docentenRepository->save($docentRecord);

    }

}
