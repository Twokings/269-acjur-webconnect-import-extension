<?php

namespace Bolt\Extension\TwoKings\ImportWebConnect\Nut;

use Bolt\Nut\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command for the ImportWebConnect extension.
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class ImportWebConnectEventsCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('importwebconnectevents:import')
            ->setDescription('Import events from WebConnect')
            ->addOption(
                'log',
                null,
                InputOption::VALUE_NONE,
                'Show log in console.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messages = [];

        $results = $this->app['importwebconnect.events.service']->fetchData();

        $message = 'Starting WebConnect import';
        if ($input->getOption('log')) {
            $output->writeln($message);
        }
        $this->app['logger.system']->info($message, ['event' => 'import']);

        $this->app['importwebconnect.events.service']->depublishAllEvents();

        $number_of_events = 0;

        foreach ($results->result as $event) {
            $message = $this->app['importwebconnect.events.service']->saveEvent($event);

            if ($input->getOption('log')) {
                $output->writeln($message);
            }

            $number_of_events++;
        }

        $message = 'Finished WebConnect import, ' . $number_of_events . ' events records imported.';
        if ($input->getOption('log')) {
            $output->writeln($message);
        }
        $this->app['logger.system']->info($message, ['event' => 'import']);
    }
}
