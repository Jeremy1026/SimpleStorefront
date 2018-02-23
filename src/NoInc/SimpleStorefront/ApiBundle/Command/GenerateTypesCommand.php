<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) K�vin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace NoInc\SimpleStorefront\ApiBundle\Command;

use ApiPlatform\SchemaGenerator\CardinalitiesExtractor;
use ApiPlatform\SchemaGenerator\GoodRelationsBridge;
use Doctrine\Common\Util\Inflector;
use NoInc\SimpleStorefront\ApiBundle\Generator\TypesGeneratorConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use NoInc\SimpleStorefront\ApiBundle\Generator\TypesGenerator;

/**
 * Generate entities command.
 *
 * @author K�vin Dunglas <dunglas@gmail.com>
 */
final class GenerateTypesCommand extends Command
{
    const DEFAULT_CONFIG_FILE = 'schema.yaml';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('generate-types')
            ->setDescription('Generate types')
            ->addArgument('output', InputArgument::REQUIRED, 'The output directory')
            ->addArgument('config', InputArgument::OPTIONAL, 'The config file to use (default to "schema.yaml" in the current directory, will generate all types if no config file exists)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $outputDir = $input->getArgument('output');
        if ($dir = realpath($input->getArgument('output'))) {
            if (!is_dir($dir)) {
                throw new \InvalidArgumentException(sprintf('The file "%s" is not a directory.', $dir));
            }

            if (!is_writable($dir)) {
                throw new \InvalidArgumentException(sprintf('The "%s" directory is not writable.', $dir));
            }

            $outputDir = $dir;
        } elseif (!@mkdir($outputDir, 0777, true)) {
            throw new \InvalidArgumentException(sprintf('Cannot create the "%s" directory. Check that the parent directory is writable.', $outputDir));
        } else {
            $outputDir = realpath($outputDir);
        }

        $configArgument = $input->getArgument('config');
        if ($configArgument) {
            if (!file_exists($configArgument)) {
                throw new \InvalidArgumentException(sprintf('The file "%s" doesn\'t exist.', $configArgument));
            }

            if (!is_file($configArgument)) {
                throw new \InvalidArgumentException(sprintf('"%s" isn\'t a file.', $configArgument));
            }

            if (!is_readable($configArgument)) {
                throw new \InvalidArgumentException(sprintf('The file "%s" isn\'t readable.', $configArgument));
            }

            $parser = new Parser();
            $config = $parser->parse(file_get_contents($configArgument));
            unset($parser);
        } elseif (is_readable(self::DEFAULT_CONFIG_FILE)) {
            $parser = new Parser();
            $config = $parser->parse(file_get_contents(self::DEFAULT_CONFIG_FILE));
            unset($parser);
        } else {
            $config = [];
        }

        if ( array_key_exists('folders', $config) ) {
            $parser = new Parser();
            $folders = $config['folders'];

            foreach ( $folders as $folder )
            {
                $folderLocation = __DIR__ . $folder;
                $finder = new Finder();

                $files = $finder->files()->in($folderLocation)->name('*.yml');

                foreach ($files as $file)
                {
                    $data = $parser->parse($file->getContents());
                    if ( is_array($data) ) {
                        $config = array_merge_recursive($config, $data);
                    }
                }
            }

        }

        $processor = new Processor();
        $configuration = new TypesGeneratorConfiguration();
        $processedConfiguration = $processor->processConfiguration($configuration, [$config]);
        $processedConfiguration['output'] = $outputDir;
        if (!$processedConfiguration['output']) {
            throw new \RuntimeException('The specified output is invalid');
        }

        $graphs = [];
        foreach ($processedConfiguration['rdfa'] as $rdfa) {
            $graph = new \EasyRdf_Graph();
            if ('http://' === substr($rdfa['uri'], 0, 7) || 'https://' === substr($rdfa['uri'], 0, 8)) {
                $graph->load($rdfa['uri'], $rdfa['format']);
            } else {
                $graph->parseFile($rdfa['uri'], $rdfa['format']);
            }

            $graphs[] = $graph;
        }

        $relations = [];
        foreach ($processedConfiguration['relations'] as $relation) {
            $relations[] = new \SimpleXMLElement($relation, 0, true);
        }

        $goodRelationsBridge = new GoodRelationsBridge($relations);
        $cardinalitiesExtractor = new CardinalitiesExtractor($graphs, $goodRelationsBridge);

        $loader = new \Twig_Loader_Filesystem(__DIR__.'/../Resources/templates/');
        $twig = new \Twig_Environment($loader, ['autoescape' => false, 'debug' => $processedConfiguration['debug']]);
        $twig->addFilter(new \Twig_SimpleFilter('ucfirst', 'ucfirst'));
        $twig->addFilter(new \Twig_SimpleFilter('pluralize', [Inflector::class, 'pluralize']));
        $twig->addFilter(new \Twig_SimpleFilter('singularize', [Inflector::class, 'singularize']));

        if ($processedConfiguration['debug']) {
            $twig->addExtension(new \Twig_Extension_Debug());
        }

        $logger = new ConsoleLogger($output);

        $entitiesGenerator = new TypesGenerator($twig, $logger, $graphs, $cardinalitiesExtractor, $goodRelationsBridge);
        $entitiesGenerator->generate($processedConfiguration);
    }
}
