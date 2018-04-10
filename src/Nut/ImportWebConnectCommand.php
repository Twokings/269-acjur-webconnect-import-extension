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
class ImportWebConnectCommand extends BaseCommand
{
    protected function configure()
  {
    $this
      ->setName('importwebconnect:import')
      ->setDescription('Import cursussen from WebConnect')
      ->addOption(
        'log',
        null,
        InputOption::VALUE_NONE,
        'Show log in console.'
     )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $messages = [];

    $results = $this->app['importwebconnect.service']->fetchData();

    $message = 'Starting WebConnect import';
    if ($input->getOption('log')) {
      $output->writeln($message);
    }
    $this->app['logger.system']->info($message, ['event' => 'import']);

    $this->app['importwebconnect.service']->depublishAllCursussen();

    $number_of_cursussen = 0;

    foreach($results->result as $cursus) {
        $message = $this->app['importwebconnect.service']->saveCursus($cursus);

        if ($input->getOption('log')) {
          $output->writeln($message);
        }

        $number_of_cursussen++;
    }

    $message = 'Finished WebConnect import, ' . $number_of_cursussen . ' cursus records imported.';
    if ($input->getOption('log')) {
      $output->writeln($message);
    }
    $this->app['logger.system']->info($message, ['event' => 'import']);
  }
}
