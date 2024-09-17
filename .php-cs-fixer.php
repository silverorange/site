<?php

use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use Silverorange\PhpCodingTools\Standards\PhpCsFixer\Php82;

$config = new Php82();

$finder = (new Finder())
    ->in(__DIR__);

return $config
    // comment the following if you don't want to use parallelism to speed up processing
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setFinder($finder);
