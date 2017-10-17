<?php
/******************************************************************************
 * Copyright (c) 2017.                                                        *
 ******************************************************************************/

namespace OrangeRT\AnonymizeBundle\Command;


use Doctrine\Common\Persistence\ManagerRegistry;
use OrangeRT\AnonymizeBundle\AnonymizeProcessor;
use OrangeRT\AnonymizeBundle\AnonymizeStopwatchProcessor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\StopwatchEvent;

class AnonymizeCommand extends ContainerAwareCommand
{
    public function run(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        if (!$this->getContainer()->has('doctrine')) {
            throw new \Exception("Doctrine was not found in the container", 2);
        }
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');

        $em = $doctrine->getManager($input->getOption('em'));

        $anonymizer = AnonymizeStopwatchProcessor::fromAnonymizer($this->getContainer()->get('orange_rt_anonymize.metadata.processor'));

        $anonymizer->anonymize($em);

        $watch = $anonymizer->getStopwatch();

        foreach($watch->getSections() as $section)
        {
            $style->section($section->getId());
            $headers = ['Start', 'End', 'Duration', 'Memory'];
            $rows = [];
            foreach($section->getEvents() as $event)
            {
                $rows[] = [$event->getStartTime(), $event->getEndTime(), $event->getDuration(), $event->getMemory()];
            }
            $style->table($headers, $rows);
        };
    }

    protected function configure()
    {
        $this->setName('anonymizer:anonymize');
        $this->setDescription('Anonymize the database, based on the anonymize tags.');
        $this->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use', 'default');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, "Prints out the entities with their properties to change");
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces the anonimizing');
        $this->addOption('paging', 'p', InputOption::VALUE_OPTIONAL, 'The amount of entities to modify in a single batch', AnonymizeProcessor::BATCH_SIZE);
    }
}