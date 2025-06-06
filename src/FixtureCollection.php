<?php

declare(strict_types=1);

namespace Shopware\FixtureBundle;


class FixtureCollection
{
    /**
     * @var array<class-string, FixtureInterface>
     */
    private array $fixtures = [];

    /**
     * @var array<class-string, array{priority: int, dependsOn: array, groups: array}>
     */
    private array $fixtureMetadata = [];

    public function add(
        FixtureInterface $fixture,
        int $priority = 0,
        array $dependsOn = [],
        array $groups = ['default']
    ): void {
        $class = $fixture::class;
        $this->fixtures[$class] = $fixture;
        $this->fixtureMetadata[$class] = [
            'priority' => $priority,
            'dependsOn' => $dependsOn,
            'groups' => $groups
        ];
    }

    /**
     * @return FixtureInterface[]
     */
    public function getFixtures(?string $group = null): array
    {
        $filtered = $this->filterByGroup($group);
        $sorted = $this->sortByPriorityAndDependencies($filtered);

        return array_map(fn($class) => $this->fixtures[$class], $sorted);
    }

    /**
     * Get metadata for a specific fixture
     * @return array{priority: int, dependsOn: array, groups: array}
     */
    public function getFixtureMetadata(string $fixtureClass): array
    {
        return $this->fixtureMetadata[$fixtureClass] ?? [
            'priority' => 0,
            'dependsOn' => [],
            'groups' => ['default']
        ];
    }

    /**
     * @return array<class-string>
     */
    private function filterByGroup(?string $group): array
    {
        if ($group === null) {
            return array_keys($this->fixtures);
        }

        return array_filter(
            array_keys($this->fixtures),
            fn($class) => in_array($group, $this->fixtureMetadata[$class]['groups'], true)
        );
    }

    /**
     * @param array<class-string> $classes
     * @return array<class-string>
     */
    private function sortByPriorityAndDependencies(array $classes): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        foreach ($classes as $class) {
            $this->visitFixture($class, $classes, $sorted, $visited, $visiting);
        }

        // Sort by priority (higher priority first) while maintaining dependency order
        usort($sorted, function ($a, $b) use ($sorted) {
            // Check if there's a dependency relationship
            if ($this->hasDependency($a, $b)) {
                return 1; // b depends on a, so a comes first
            }
            if ($this->hasDependency($b, $a)) {
                return -1; // a depends on b, so b comes first
            }

            // No dependency relationship, sort by priority
            $priorityA = $this->fixtureMetadata[$a]['priority'];
            $priorityB = $this->fixtureMetadata[$b]['priority'];

            return $priorityB <=> $priorityA; // Higher priority first
        });

        return $sorted;
    }

    /**
     * @param class-string $class
     * @param array<class-string> $availableClasses
     * @param array<class-string> &$sorted
     * @param array<class-string, bool> &$visited
     * @param array<class-string, bool> &$visiting
     */
    private function visitFixture(
        string $class,
        array $availableClasses,
        array &$sorted,
        array &$visited,
        array &$visiting
    ): void {
        if (isset($visited[$class])) {
            return;
        }

        if (isset($visiting[$class])) {
            throw new \RuntimeException(sprintf('Circular dependency detected for fixture: %s', $class));
        }

        $visiting[$class] = true;

        $dependencies = $this->fixtureMetadata[$class]['dependsOn'];
        foreach ($dependencies as $dependency) {
            if (!in_array($dependency, $availableClasses, true)) {
                if (!isset($this->fixtures[$dependency])) {
                    throw new \RuntimeException(sprintf(
                        'Fixture "%s" depends on "%s", but it is not registered',
                        $class,
                        $dependency
                    ));
                }
                // Skip dependency if it's not in the current group
                continue;
            }

            $this->visitFixture($dependency, $availableClasses, $sorted, $visited, $visiting);
        }

        $sorted[] = $class;
        $visited[$class] = true;
        unset($visiting[$class]);
    }

    /**
     * Check if $dependent has a direct or transitive dependency on $dependency
     */
    private function hasDependency(string $dependent, string $dependency): bool
    {
        $dependencies = $this->fixtureMetadata[$dependent]['dependsOn'];

        if (in_array($dependency, $dependencies, true)) {
            return true;
        }

        foreach ($dependencies as $dep) {
            if (isset($this->fixtures[$dep]) && $this->hasDependency($dep, $dependency)) {
                return true;
            }
        }

        return false;
    }
}
