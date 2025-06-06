<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle\Command;

use Shopware\FixtureBundle\FixtureCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('fixture:load', 'Load fixtures into the database')]
class LoadFixtureCommand extends Command
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
            'Load only fixtures from a specific group'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $group = $input->getOption('group');

        $io->title('Loading Fixtures');

        if ($group !== null) {
            $io->note(sprintf('Loading fixtures from group: %s', $group));
        }

        $fixtures = $this->fixtureCollection->getFixtures($group);

        if (empty($fixtures)) {
            $io->warning('No fixtures found to load.');
            return self::SUCCESS;
        }

        $io->progressStart(count($fixtures));

        foreach ($fixtures as $fixture) {
            $fixtureClass = $fixture::class;
            $io->text(sprintf('Loading fixture: %s', $fixtureClass));
            
            try {
                $fixture->load();
                $io->progressAdvance();
            } catch (\Exception $e) {
                $io->progressFinish();
                $io->error(sprintf(
                    'Error loading fixture %s: %s',
                    $fixtureClass,
                    $e->getMessage()
                ));
                return self::FAILURE;
            }
        }

        $io->progressFinish();
        $io->success(sprintf('Successfully loaded %d fixtures.', count($fixtures)));

        return self::SUCCESS;
    }
}
