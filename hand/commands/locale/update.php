<?php
namespace Hand\Commands\Locale;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Hand\Commands\Locale\Action\Update as UpdateAction;

class Update extends Command {

    protected function configure() {
        $this->setName('locale:update')
             ->setDescription('Update all locale files')
             ->setAliases(['localeupdate'])
             ->addOption('--locale_path', 'nn', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $locale_path = $input->getOption('locale_path') ?: WWW_ROOT.DIRECTORY_SEPARATOR.'locale';

        $output->writeln(sprintf('Your locale path is <info>%s</info>', $locale_path));

        $update_action = new UpdateAction([APP_ROOT.'/controllers', APP_ROOT.'/views'], $locale_path);
        $update_action->setOutput($output);
        $update_action->execute();
    }

}
