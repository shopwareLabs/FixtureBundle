<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Command;

use Shopware\FixtureBundle\FixtureCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('fixture:list', 'List all available fixtures and their execution order')]
class ListFixtureCommand extends Command
{
    public function __construct(
        private readonly FixtureCollection $fixtureCollection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'group',
            'g',
            InputOption::VALUE_OPTIONAL,
            'Filter fixtures by group'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $group = $input->getOption('group');

        $io->title('Available Fixtures');

        if ($group !== null) {
            $io->note(sprintf('Filtering by group: %s', $group));
        }

        $fixtures = $this->fixtureCollection->getFixtures($group);

        if (empty($fixtures)) {
            $io->warning('No fixtures found.');
            return self::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Order', 'Class', 'Priority', 'Groups', 'Depends On']);

        $rows = [];
        foreach ($fixtures as $index => $fixture) {
            $fixtureClass = $fixture::class;
            $metadata = $this->fixtureCollection->getFixtureMetadata($fixtureClass);
            
            $priority = $metadata['priority'];
            $groups = implode(', ', $metadata['groups']);
            $dependsOn = $this->formatDependencies($metadata['dependsOn']);

            $rows[] = [
                $index + 1,
                $this->formatClassName($fixtureClass),
                $priority,
                $groups,
                $dependsOn
            ];
        }

        $table->setRows($rows);
        $table->render();

        return self::SUCCESS;
    }

    private function formatClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    private function formatDependencies(array $dependencies): string
    {
        if (empty($dependencies)) {
            return '-';
        }

        $formatted = array_map([$this, 'formatClassName'], $dependencies);
        return implode(', ', $formatted);
    }
}