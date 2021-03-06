<?php
namespace Hand\Commands\Locale;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Hand\Commands\Locale\Action\Remove as RemoveAction;

class Remove extends Command {

    protected function configure() {
        $this->setName('locale:remove')
             ->setDescription('Remove message file in locale directory')
             ->setAliases(['localeremove'])
             ->addArgument('locale_name', InputArgument::REQUIRED)
             ->addOption('--locale_path', 'nn', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $locale_name = $input->getArgument('locale_name');
        $locale_path = $input->getOption('locale_path') ?: WWW_ROOT.DIRECTORY_SEPARATOR.'locale';

        $output->writeln(sprintf('Your locale name is <info>%s</info>', $locale_name));
        $output->writeln(sprintf('Your locale path is <info>%s</info>', $locale_path));

        $create_action = new RemoveAction($locale_path.DIRECTORY_SEPARATOR.$locale_name);
        $create_action->setOutput($output);
        $create_action->execute();
    }

}
