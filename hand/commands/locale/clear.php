<?php
namespace Hand\Commands\Locale;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Hand\Commands\Locale\Action\Clear as ClearAction;

class Clear extends Command {

    protected function configure() {
        $this->setName('locale:clear')
             ->setDescription('Clear message in all/specified locale directory')
             ->setAliases(['localeclear'])
             ->addArgument('locale_name', InputArgument::OPTIONAL)
             ->addOption('--locale_path', 'nn', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $locale_name = $input->getArgument('locale_name') ?: '';
        $locale_path = $input->getOption('locale_path') ?: WWW_ROOT.DIRECTORY_SEPARATOR.'locale';

        if (empty($locale_name) === true) {
            $output->writeln('Action: <info>clear all locale directory</info>');
        }else{
            $output->writeln('Action: <info>clear locale directory</info>');
            $output->writeln('');
            $output->writeln(sprintf('Your locale name is <info>%s</info>', $locale_name));
            $output->writeln(sprintf('Your locale path is <info>%s</info>', $locale_path));
        }

        $clear_action = new ClearAction($locale_path.DIRECTORY_SEPARATOR, $locale_name);
        $clear_action->setOutput($output);
        $clear_action->execute();
    }

}
