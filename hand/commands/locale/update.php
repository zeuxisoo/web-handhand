<?php
namespace Hand\Commands\Locale;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Update extends Command {

    protected function configure() {
        $this->setName('locale:update')
             ->setDescription('Update all locale files')
             ->setAliases(['update-locale'])
             ->addArgument('locale_name', InputArgument::REQUIRED)
             ->addOption('--locale_path', 'nn', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $locale_name = $input->getArgument('locale_name');
        $locale_path   = $input->getOption('locale_path') ?: dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'locale';

        $output->writeln(sprintf('Your locale name is <info>%s</info>', $locale_name));
        $output->writeln(sprintf('Your locale path is <info>%s</info>', $locale_path));

        // TODO: extract all strings into locale files when find pattern /locale(string)/ in view and php
    }

}
