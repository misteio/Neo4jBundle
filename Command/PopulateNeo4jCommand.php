<?php

namespace Misteio\Neo4jBundle\Command;

use Doctrine\ORM\EntityManager;
use Misteio\Neo4jBundle\Factory\Neo4jFactory;
use Misteio\Neo4jBundle\Helper\Neo4jHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;


class PopulateNeo4jCommand extends ContainerAwareCommand
{
    /** @var  OutputInterface */
    protected $output;
    /** @var  integer */
    protected $threads;
    /** @var  integer */
    protected $limit;
    /** @var  integer */
    protected $offset;
    /** @var bool  */
    protected $batch = false;
    /** @var bool  */
    protected $reset = false;
    /** @var bool  */
    protected $resetIndex = false;
    /** @var  string */
    protected $type;
    /** @var  array */
    protected $aTypes;
    /** @var  Neo4jHelper */
    protected $neo4jHelper;
    /** @var  array */
    protected $mappings;
    /** @var  EntityManager */
    protected $em;
    /** @var  Neo4jFactory */
    protected $neo4jFactory;
    /** @var  string */
    protected $consoleDir;

    protected function configure()
    {
        $this
            ->setName('misteio:neo4j:populate')
            ->setDescription('Repopulate Neo4j Database')
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit For selected Type',
                0
            )
            ->addOption(
                'offset',
                null,
                InputOption::VALUE_OPTIONAL,
                'Offset For selected Type',
                0
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_OPTIONAL,
                'Type of document you want to populate. You must to have configure it before use',
                null
            )
            ->addOption(
                'threads',
                null,
                InputOption::VALUE_OPTIONAL,
                'number of simultaneous threads',
                null
            )
            ->addOption(
                'reset',
                null)

            ->addOption(
                'reset_index',
                null)

            ->addOption(
                'batch',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of Document per batch',
                null
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->threads                              = $input->getOption('threads') ?: 2;
        $this->limit                                = $input->getOption('limit') ?: null;
        $this->offset                               = $input->getOption('offset') ?: 0;
        $this->type                                 = $input->getOption('type');
        $this->batch                                = $input->getOption('batch');
        $this->reset                                = $input->getOption('reset');
        $this->resetIndex                           = $input->getOption('reset_index');
        $this->output                               = $output;
        $this->neo4jHelper                          = $this->getContainer()->get('misteio.neo4j.helper');
        $this->mappings                             = $this->getContainer()->getParameter('neo4j.mappings');
        $this->neo4jFactory                         = $this->getContainer()->get('misteio.neo4j.factory');
        $this->em                                   = $this->getContainer()->get('doctrine.orm.entity_manager');
        foreach ($this->mappings as $key=>$mapping){
            $this->aTypes[] = $key;
        }

        // We add a limit per batch which equal of the batch option
        if($input->getOption('batch')){
            $this->limit = $this->batch ;
        }

        $symfony_version    = \Symfony\Component\HttpKernel\Kernel::VERSION;
        $this->consoleDir   = $symfony_version[0] == 2 ? 'app/console' : 'bin/console';

        if($input->getOption('type')){
            return $this->_switchType($this->type, $this->batch);
        }else{
            foreach ($this->aTypes as $type){
                $this->_switchType($type, $this->batch);
            }
        }
    }

    /**
     * @param $type
     * @param $batch
     * @return int
     */
    private function _switchType($type, $batch){
        if(in_array($type, $this->aTypes)){
            $this->output->writeln("********************** BEGIN {$type} ************************");
            if($this->reset){
                $this->_reset($type);
                $this->output->writeln("********************** RESET TYPE ***********************");
            }
            if(!$batch){
                $this->processBatch($type,$this->getContainer()->get($this->mappings[$type]['transformer']));
            }else{
                $this->beginBatch($type);
            }
            $this->output->writeln("********************** FINISH {$type} ***********************");

        }else{
            $this->output->writeln("********************** Wrong Type ***********************");
            return 1;
        }
    }




    /**
     * @param $progressBar
     * @param array $processes
     * @param $maxParallel
     * @param int $poll
     */
    public function runParallel(ProgressBar $progressBar, array $processes, $maxParallel, $poll = 1000)
    {
        $helper = $this->getHelper('process');
        // do not modify the object pointers in the argument, copy to local working variable
        $processesQueue = $processes;
        // fix maxParallel to be max the number of processes or positive
        $maxParallel = min(abs($maxParallel), count($processesQueue));
        // get the first stack of processes to start at the same time
        /** @var Process[] $currentProcesses */
        $currentProcesses = array_splice($processesQueue, 0, $maxParallel);
        // start the initial stack of processes
        foreach ($currentProcesses as $process) {
            $helper->run($this->output, $process, 'The process failed :(', function ($type, $data) {
                if (Process::ERR === $type) {
                    if(strlen($data) > 50){
                        $this->output->writeln($data);
                    }
                }
            });
        }
        do {
            // wait for the given time
            usleep($poll);
            // remove all finished processes from the stack
            foreach ($currentProcesses as $index => $process) {
                if (!$process->isRunning()) {
                    unset($currentProcesses[$index]);
                    $progressBar->advance($this->limit);
                    // directly add and start new process after the previous finished
                    if (count($processesQueue) > 0) {
                        $nextProcess = array_shift($processesQueue);
                        $helper->run($this->output, $nextProcess, 'The process failed :(', function ($type, $data) {
                            if (Process::ERR === $type) {
                                if(strlen($data) > 50){
                                    $this->output->writeln($data);
                                }
                            }
                        });
                        $currentProcesses[] = $nextProcess;
                    }
                }
            }
            // continue loop while there are processes being executed or waiting for execution
        } while (count($processesQueue) > 0 || count($currentProcesses) > 0);

    }

    /**
     * @param $type
     */
    public function beginBatch($type){
        $numberObjects = $this->em->createQuery("SELECT COUNT(u) FROM {$this->mappings[$type]['class']} u")->getResult()[0][1];
        $aProcess = [];
        $total    =  floor(($numberObjects - $this->offset) / $this->limit);
        $progressBar = new ProgressBar($this->output,$numberObjects - $this->offset);
        for ($i = 0; $i <= $total; $i++) {
            $_offset = $this->offset + ($this->limit * $i);
            $process = new Process("php $this->consoleDir misteio:neo4j:populate --type={$type} --limit={$this->limit} --offset={$_offset}");
            $aProcess[] = $process;
        }

        $max_parallel_processes = $this->threads;
        $polling_interval = 1000; // microseconds
        $this->runParallel($progressBar,$aProcess, $max_parallel_processes, $polling_interval);

        return;
    }


    /**
     * @param $type
     * @param $transformer
     */
    public function processBatch($type, $transformer){
        $this->output->writeln("********************** Creating Type {$type} and Mapping ***********************");
        $connectionName         = $this->mappings[$type]['connection'];

        $this->output->writeln("********************** Finish Type {$type} and Mapping ***********************");
        $this->output->writeln("********************** Start populate {$type} ***********************");


        $iResults = $this->em->createQuery("SELECT COUNT(u) FROM {$this->mappings[$type]['class']} u")->getResult()[0][1];

        $q = $this->em->createQuery("select u from {$this->mappings[$type]['class']} u");

        if($this->offset){
            $q->setFirstResult($this->offset);
            $iResults = $iResults - $this->offset;
        }

        if($this->limit){
            $q->setMaxResults($this->limit);
            $iResults = $this->limit;

        }

        $iterableResult = $q->iterate();

        $progress = new ProgressBar($this->output, $iResults);
        $progress->start();


        foreach ($iterableResult as $row){
            $transformer->transform($row[0], $connectionName);
            $this->em->detach($row[0]);
            $progress->advance();
        }

        $this->output->writeln("********************** Start populate {$type} ***********************");

        $progress->finish();
        $this->output->writeln('');
        $this->output->writeln("<info>********************** Finish populate {$type} ***********************</info>");

    }

    /**
     * Reset Index + Entities from Neo4j
     * @param $type string
     */
    private function _reset($type){
        $this->neo4jFactory->removeAllFromNeo4j($type, $this->mappings[$type]['connection']);
        if(array_key_exists('indexes', $this->mappings[$type])) {
            foreach ($this->mappings[$type]['indexes'] as $index) {
                if($this->resetIndex){
                    $this->neo4jFactory->dropIndex($type, $this->mappings[$type]['connection'], $index);
                }
                $this->neo4jFactory->createIndex($type, $this->mappings[$type]['connection'], $index);
            }
        }

        if(array_key_exists('composite_indexes', $this->mappings[$type])){
            foreach ($this->mappings[$type]['composite_indexes'] as $index){
                if($this->resetIndex){
                    $this->neo4jFactory->dropCompositeIndex($type, $this->mappings[$type]['connection'], $index);
                }
                $this->neo4jFactory->createCompositeIndex($type, $this->mappings[$type]['connection'], $index);
            }
        }

    }
}