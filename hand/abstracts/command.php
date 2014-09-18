<?php
namespace Hand\Abstracts;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Command {

    private $output = null;

    public function setOutput(OutputInterface $output) {
        $this->output = $output;
    }

    public function success($message) {
        $style = new OutputFormatterStyle('green', 'black', array('bold', 'underscore'));
        $this->output->writeln('');
        $this->output->writeln($style->apply($message));
    }

    public function fail($message) {
        $style = new OutputFormatterStyle('red', 'black', array('bold', 'underscore'));
        $this->output->writeln('');
        $this->output->writeln($style->apply($message));
    }

}
