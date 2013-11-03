<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

/*
 * This command performs a benchmark about the performance of publishing
 * books using different formats (PDF, EPUB, MOBI, and HTML).
 *
 * The results of this command will be used to take decisions about the
 * code of easybook in order to improve its performance.
 */
class EasybookBenchmarkCommand extends BaseCommand
{
    private $output;

    protected function configure()
    {
        $this
            ->setName('benchmark')
            ->setDescription('Benchmarks the performance of book publishing')
            ->setDefinition(array(
                new InputOption(
                    'full-benchmark', '', InputOption::VALUE_OPTIONAL, 'If true, PDF and MOBI editions are also benchmarked (they require external libraries)', false
                ),
                new InputOption(
                    'iterations', '', InputOption::VALUE_OPTIONAL, 'The number of times that each book edition is published (if this number is low, the benchmark results aren\'t reliable enough)', '5'
                ),
            ))
            ->setHelp('The <info>benchmark</info> command performs a full book publishing benchmark');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $editions = array('ebook', 'web', 'website');
        $advancedEditions = array('kindle', 'print');

        if ($input->getOption('full-benchmark')) {
            $editions = array_merge($editions, $advancedEditions);
        }

        $iterations = intval($input->getOption('iterations'));

        $results = $this->benchmark($editions, $iterations);
        $this->displayResults($results);
        $this->tearDown();
    }

    /**
     * Performs the book publishing benchmark for the given editions
     * and returns the results.
     *
     * @param array $editions   The list of editions to use for this benchmark
     * @param int   $iterations The number of times that each edition is published
     *
     * @return array The results of the benchmark
     */
    private function benchmark(array $editions, $iterations)
    {
        $results = array();

        $step = floor(100 / count($editions));
        $progressBar = $this->getHelperSet()->get('progress');

        $this->output->write("\n");
        $this->output->write(" Benchmarking <info>easybook</info>...\n\n");
        $progressBar->start($this->output, 100);

        foreach ($editions as $edition) {
            $results[$edition] = $this->benchmarkEdition($edition, $iterations);
            $progressBar->advance($step);
        }

        $progressBar->setCurrent(100);
        $progressBar->finish();

        $meanResults = $this->calculateMeanResults($results);

        return $meanResults;
    }

    /**
     * Performs the benchmark of the given edition by publishing it
     * the number of times indicated by the second argument.
     *
     * @param string $edition    The edition to be published
     * @param int    $iterations The number of times that this edition is published
     *
     * @return array The results of this edition benchmark
     *
     * @throws \RuntimeException If the edition cannot be published
     */
    private function benchmarkEdition($edition, $iterations)
    {
        $results = array();

        $publishBookCommand = sprintf(
            "./book publish --dir=%s sherlock-holmes %s",
            __DIR__.'/../../../../app/Resources/Books/',
            $edition
        );

        for ($i=0; $i<$iterations; $i++) {
            $process = new Process($publishBookCommand);

            $start  = microtime(true);
            $process->run();
            $finish = microtime(true);

            if (!$process->isSuccessful()) {
                throw new \RuntimeException(sprintf(
                    "[ERROR] The benchmark couldn't be completed because there was\n"
                        ." an error while publishing the book with this command:\n"
                        ." %s\n\n"
                        ." Command result:\n"
                        ." %s",
                    $publishBookCommand,
                    $process->getOutput()
                ));
            }

            $elapsedTime = 1000 * ($finish - $start);
            $consumedMemory = memory_get_peak_usage(true);
            $score = $this->getScore($elapsedTime, $consumedMemory, $edition);

            $results[] = array(
                'format' => $edition,
                'time'   => $elapsedTime,
                'memory' => $consumedMemory,
                'score'  => $score,
            );
        }

        return $results;
    }

    /**
     * Calculates the mean benchmark results by performing the arithmetic mean of
     * the results of each iteration.
     *
     * @param array $results The original results of each benchmark iteration
     *
     * @return array The mean results for all the iterations of the benchmark
     */
    private function calculateMeanResults(array $results)
    {
        $meanResults = array();

        foreach ($results as $edition => $editionResults) {
            $totalTime   = 0;
            $totalMemory = 0;
            $totalScore  = 0;

            $iterations = count($editionResults);

            foreach ($editionResults as $result) {
                $totalTime   += $result['time'];
                $totalMemory += $result['memory'];
                $totalScore  += $result['score'];
            }

            $meanResults[$edition] = array(
                'edition' => $edition,
                'time'    => number_format($totalTime/$iterations, 2, '.', ','),
                'memory'  => number_format($totalMemory/$iterations, 2, '.', ','),
                'score'   => number_format($totalScore/$iterations, 2, '.', ','),
            );
        }

        return $meanResults;
    }

    /**
     * Displays the results of the benchmark in the console as an
     * easy to understand table and computes the final mean score
     * of the benchmark.
     *
     * @param array $results The benchmark results to be displayed
     */
    private function displayResults(array $results)
    {
        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Format', 'Time (msec.)', 'Memory (bytes)', 'Score'))
            ->setRows($results)
        ;

        $this->output->write("\n");
        $table->render($this->output);

        $meanScore = 0;
        foreach ($results as $result) {
            $meanScore += $result['score'];
        }
        $meanScore = number_format($meanScore / count($results), 2, '.', ',');

        $this->output->write("\n");
        $this->output->write(" YOUR SCORE: $meanScore / 100.00 \n");
        $this->output->write(str_repeat(' ', 13).str_repeat('~', 14)."\n\n");
    }

    /**
     * Deletes any file/folder generated during the benchmark to restore
     * the original state of the system.
     *
     * @throws \RuntimeException If the published book cannot be deleted
     */
    private function tearDown()
    {
        $deletePublishedBookCommand = sprintf(
            'cd %s && rm -fr %s',
            __DIR__.'/../../../../app/Resources/Books/sherlock-holmes',
            'Output'
        );

        $process = new Process($deletePublishedBookCommand);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                "[ERROR] The benchmark didn't terminate in a clean way \n"
                    ." because the published book couldn't be deleted:\n\n"
                    ." %s",
                $process->getOutput()
            ));
        }
    }

    /**
     * Calculates the score of the given benchmark results and
     * normalizes that score in a 0..100 scale.
     *
     * @param float  $elapsedTime    The time elapsed to complete the benchmark in milliseconds
     * @param int    $consumedMemory The memory consumed during the benchmark in bytes.
     * @param string $edition        The name of the edition being published
     *
     * @return float  The score of the benchmark in a 0..100 scale
     */
    private function getScore($elapsedTime, $consumedMemory, $edition)
    {
        $maxAcceptableValue = array(//   msec.              bytes
            'ebook'   => array('time' => 3000,  'memory' => 3 * 1024 * 1024),
            'kindle'  => array('time' => 6000,  'memory' => 6 * 1024 * 1024),
            'print'   => array('time' => 20000, 'memory' => 9 * 1024 * 1024),
            'web'     => array('time' => 3000,  'memory' => 3 * 1024 * 1024),
            'website' => array('time' => 3000,  'memory' => 3 * 1024 * 1024),
        );

        $timeScore   = min($elapsedTime, $maxAcceptableValue[$edition]['time']) / $maxAcceptableValue[$edition]['time'];
        $memoryScore = min($consumedMemory, $maxAcceptableValue[$edition]['memory']) / $maxAcceptableValue[$edition]['memory'];

        // in our case, speed is preferable over memory consumption
        $score = 0.8 * $timeScore + 0.2 * $memoryScore;

        return 100 * (1 - $score);
    }
}
