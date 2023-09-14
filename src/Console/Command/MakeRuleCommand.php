<?php

declare(strict_types=1);

namespace Rector\Core\Console\Command;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MakeRuleCommand extends Command
{
    public function __construct(
        private readonly SymfonyStyle $symfonyStyle
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('make:rule');
        $this->setDescription('Make a new Rector rule with tests and config');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ruleName = (string) $this->symfonyStyle->ask('What is the name of the rule?');

        if ($ruleName === '') {
            $this->symfonyStyle->error('Rule name cannot be empty!');
            return self::FAILURE;
        }

        if (! Strings::endsWith($ruleName, 'Rector')) {
            $ruleName .= 'Rector';
        }

        $rulesDirectory = (string) $this->symfonyStyle->ask(
            'What is the directory of your rules?',
            'rules'
        );

        $mainNamespace = (string) $this->symfonyStyle->ask(
            'What is the main namespace your rule will have?',
            'Rector'
        );

        $ruleNamespace = (string) $this->symfonyStyle->ask(
            'What is the namespace of the rule?',
            "CodeQuality\\Rector\\Array_"
        );

        $testsDirectory = (string) $this->symfonyStyle->ask(
            'What is the directory of your tests?',
            'rules-tests'
        );

        $ruleDirectoryPath = getcwd() . '/' . $rulesDirectory . '/' . str_replace('\\', '/', $ruleNamespace);

        if (! file_exists($ruleDirectoryPath)) {
            FileSystem::createDir($ruleDirectoryPath);
        }

        $testsDirectoryPath = getcwd() . '/' . $testsDirectory . '/' . str_replace('\\', '/', $ruleNamespace) . '/' . $ruleName;

        if (! file_exists($testsDirectoryPath)) {
            FileSystem::createDir($testsDirectoryPath);
        }

        $this->createRule($ruleDirectoryPath, $ruleName, $mainNamespace, $ruleNamespace);

        $this->createTests($testsDirectoryPath, $ruleName, $mainNamespace, $ruleNamespace);

        $this->createConfig($testsDirectoryPath, $ruleName, $mainNamespace, $ruleNamespace);

        $this->createFixture($testsDirectoryPath, $ruleName, $mainNamespace, $ruleNamespace);

        return self::SUCCESS;
    }

    private function createRule(string $ruleDirectoryPath, string $ruleName, string $mainNamespace, string $ruleNamespace): void
    {
        $rectorRuleFilePath = $ruleDirectoryPath . '/' . $ruleName . '.php';

        if (file_exists($rectorRuleFilePath)) {
            $this->symfonyStyle->info('Rule already exists!');
            return;
        }

        $ruleTemplate = FileSystem::read(__DIR__ . '/../../../templates/rule/rule.php.dist');

        $ruleContents = strtr($ruleTemplate, [
            '__RULE_NAME__' => $ruleName,
            '__MAIN_NAMESPACE__' => $mainNamespace,
            '__RULE_NAMESPACE__' => $this->getRuleNamespace($mainNamespace, $ruleNamespace),
            '__TEST_NAMESPACE__' => $this->getTestNamespace($mainNamespace, $ruleNamespace, $ruleName),
        ]);

        FileSystem::write($rectorRuleFilePath, $ruleContents);

        $this->symfonyStyle->newLine();

        $this->symfonyStyle->success("The [{$rectorRuleFilePath}] file was added!");
    }

    private function createTests(string $testsDirectoryPath, string $ruleName, string $mainNamespace, string $ruleNamespace): void
    {
        $testFilePath = $testsDirectoryPath . '/' . $ruleName . 'Test.php';

        if (file_exists($testFilePath)) {
            $this->symfonyStyle->info('Test already exists!');
            return;
        }

        $testTemplate = FileSystem::read(__DIR__ . '/../../../templates/rule/rule-test.php.dist');

        $ruleContents = strtr($testTemplate, [
            '__RULE_NAME__' => $ruleName,
            '__TEST_NAMESPACE__' => $this->getTestNamespace($mainNamespace, $ruleNamespace, $ruleName),
        ]);

        FileSystem::write($testFilePath, $ruleContents);

        $this->symfonyStyle->newLine();

        $this->symfonyStyle->success("The [{$testFilePath}] file was added!");
    }

    private function createConfig(string $testsDirectoryPath, string $ruleName, string $mainNamespace, string $ruleNamespace): void
    {
        $configFilePath = $testsDirectoryPath . '/config/configured_rule.php';

        if (file_exists($configFilePath)) {
            $this->symfonyStyle->info('Config already exists!');
            return;
        }

        if (! file_exists($testsDirectoryPath . '/config')) {
            FileSystem::createDir($testsDirectoryPath . '/config');
        }

        $configTemplate = FileSystem::read(__DIR__ . '/../../../templates/rule/rule-config.php.dist');

        $ruleContents = strtr($configTemplate, [
            '__RULE_NAME__' => $ruleName,
            '__RULE_NAMESPACE__' => $this->getRuleNamespace($mainNamespace, $ruleNamespace),
        ]);

        FileSystem::write($configFilePath, $ruleContents);

        $this->symfonyStyle->newLine();

        $this->symfonyStyle->success("The [{$configFilePath}] file was added!");
    }

    private function createFixture(string $testsDirectoryPath, string $ruleName, string $mainNamespace, string $ruleNamespace): void
    {
        $fixtureFilePath = $testsDirectoryPath . '/Fixture/fixture.php.inc';

        if (file_exists($fixtureFilePath)) {
            $this->symfonyStyle->info('Fixture already exists!');
            return;
        }

        if (! file_exists($testsDirectoryPath . '/Fixture')) {
            FileSystem::createDir($testsDirectoryPath . '/Fixture');
        }

        $fixtureTemplate = FileSystem::read(__DIR__ . '/../../../templates/rule/rule-fixture.php.dist');

        $ruleContents = strtr($fixtureTemplate, [
            '__TEST_NAMESPACE__' => $this->getTestNamespace($mainNamespace, $ruleNamespace, $ruleName),
        ]);

        FileSystem::write($fixtureFilePath, $ruleContents);

        $this->symfonyStyle->newLine();

        $this->symfonyStyle->success("The [{$fixtureFilePath}] file was added!");
    }

    private function getRuleNamespace(string $mainNamespace, string $ruleNamespace): string
    {
        return "{$mainNamespace}\\{$ruleNamespace}";
    }

    private function getTestNamespace(string $mainNamespace, string $ruleNamespace, string $ruleName): string
    {
        return "{$mainNamespace}\\Tests\\{$ruleNamespace}\\{$ruleName}";
    }
}
