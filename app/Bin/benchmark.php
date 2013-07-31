<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__.'/../../vendor/autoload.php';

/*
 * This script performs a benchmark about the performance of publishing
 * books using different formats (PDF, EPUB, MOBI, and HTML).
 *
 * The results of executing regularly this script will be used to take
 * decisions about the code of easybook in order to improve its performance.
 */

// -- CONFIGURATION OPTIONS ---------------------------------------------------
$parameters = array(
    // if 'true', PDF and MOBI editions are also benchmarked (they are disabled
    // by default because they require external libraries)
    'extendedBenchmark' => false,

    // the number of times that each book edition is published (if this number is
    // low, the benchmark results aren't reliable enough)
    'numIterations'     => 5,
);

// -- BENCHMARK ---------------------------------------------------------------
$config  = setUp($parameters);
$results = benchmark($config);
displayResults($results);
tearDown();

// -- FUNCTIONS ---------------------------------------------------------------

/*
 * Prepares the benchmark configuration.
 *
 * @param $parameters array The configuration parameters for this benchmark
 *
 * @return array The benchmark configuration
 */
function setUp(array $parameters)
{
    $config = array();

    $editions = array('ebook', 'web', 'website');
    $extendedEditions = array('kindle', 'print');

    if ($parameters['extendedBenchmark']) {
        $editions = array_merge($editions, $extendedEditions);
    }

    $config['editions']   = $editions;
    $config['iterations'] = $parameters['numIterations'];

    return $config;
}

/**
 * Performs the book publishing benchmark for the given editions
 * and returns the results.
 *
 * @param array $config The configuration options for this benchmark
 *                      (editions to be published, number of iterations, etc.)
 *
 * @return array The results of the benchmark
 *
 * @throws RuntimeException If some edition cannot be published
 */
function benchmark(array $config)
{
    $results = array();

    // prepare the progress bar of the benchmark
    $totalIterations = count($config['editions']) * $config['iterations'];
    $step = floor(100 / $totalIterations);

    $console  = new Application();
    $output   = new ConsoleOutput();
    $progressBar = $console->getHelperSet()->get('progress');

    $output->write("\n");
    $progressBar->start($output, 100);

    foreach ($config['editions'] as $edition) {
        $publishBookCommand = sprintf(
            "cd %s && ./book publish --dir=%s sherlock-holmes %s",
            __DIR__.'/../../',
            __DIR__.'/../Resources/Books/',
            $edition
        );

        for ($i=0; $i<$config['iterations']; $i++) {
            $process = new Process($publishBookCommand);

            $start  = microtime(true);
            $process->run();
            $finish = microtime(true);

            $progressBar->advance($step);

            if ($process->isSuccessful()) {
                $elapsedTime = 1000 * ($finish - $start);
                $consumedMemory = memory_get_peak_usage(true);
                $score = getScore($elapsedTime, $consumedMemory, $edition);

                $results[$edition][] = array(
                    'format' => $edition,
                    'time'   => $elapsedTime,
                    'memory' => $consumedMemory,
                    'score'  => $score,
                );
            } else {
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
        }
    }

    $progressBar->setCurrent(100);
    $progressBar->finish();

    // calculate for each edition the mean value of all its iterations
    $meanResults = array();
    foreach ($results as $edition => $iterations) {
        $totalTime   = 0;
        $totalMemory = 0;
        $totalScore  = 0;

        foreach ($iterations as $iteration) {
            $totalTime   += $iteration['time'];
            $totalMemory += $iteration['memory'];
            $totalScore  += $iteration['score'];
        }

        $meanResults[$edition] = array(
            'edition' => $edition,
            'time'    => number_format($totalTime/count($iterations), 2, '.', ','),
            'memory'  => number_format($totalMemory/count($iterations), 2, '.', ','),
            'score'   => number_format($totalScore/count($iterations), 2, '.', ','),
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
function displayResults(array $results)
{
    $console = new Application();
    $output  = new ConsoleOutput();

    $table = $console->getHelperSet()->get('table');
    $table
        ->setHeaders(array('Format', 'Time (msec.)', 'Memory (bytes)', 'Score'))
        ->setRows($results)
    ;

    $output->write("\n");
    $table->render($output);

    $meanScore = 0;
    foreach ($results as $result) {
        $meanScore += $result['score'];
    }
    $meanScore = number_format($meanScore / count($results), 2, '.', ',');

    $output->write("\n");
    $output->write(" YOUR SCORE: $meanScore / 100.00 \n");
    $output->write(str_repeat(' ', 13).str_repeat('~', 14)."\n\n");
}

/**
 * Deletes any file/folder generated during the benchmark to restore
 * the original state of the system.
 *
 * @throws RuntimeException If the published book cannot be deleted
 */
function tearDown()
{
    $deletePublishedBookCommand = sprintf(
        'cd %s && rm -fr %s',
        __DIR__.'/../Resources/Books/sherlock-holmes',
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

/*
 * Calculates the score of the given benchmark results and
 * normalizes that score in a 0..100 scale.
 *
 * @param $elapsedTime    The time elapsed to complete the benchmark in milliseconds
 * @param $consumedMemory The memory consumed during the benchmark in bytes.
 * @param $edition        The name of the edition being published
 *
 * @return float  The score of the benchmark in a 0..100 scale
 */
function getScore($elapsedTime, $consumedMemory, $edition)
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
