<?php

namespace Hoogi91\ReleaseFlow;

use Exception;
use Hoogi91\ReleaseFlow\Command\AbstractFlowCommand;
use Hoogi91\ReleaseFlow\Command\DevelopCommand;
use Hoogi91\ReleaseFlow\Command\FinishCommand;
use Hoogi91\ReleaseFlow\Command\HotfixCommand;
use Hoogi91\ReleaseFlow\Command\StartCommand;
use Hoogi91\ReleaseFlow\Command\StatusCommand;
use Hoogi91\ReleaseFlow\Configuration\Composer;
use Hoogi91\ReleaseFlow\Utility\PathUtility;
use Hoogi91\ReleaseFlow\VersionControl\VersionControlInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The console application.
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @codeCoverageIgnore
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * version control interface to interact with
     *
     * @var VersionControlInterface
     */
    protected $vcs;

    /**
     * holds composer configuration
     *
     * @var Composer
     */
    protected $composer;

    /**
     * Application constructor.
     *
     * @param string $name
     * @param string $version
     */
    public function __construct(string $name = 'release-flow', string $version = '0.1.1')
    {
        try {
            parent::__construct($name, $version);

            // read composer.json file configuration for usage
            $this->composer = new Composer(PathUtility::getCwd());

            // build version control class from composer settings
            $versionControlClass = $this->composer->getVersionControlClass();
            $this->vcs = new $versionControlClass(PathUtility::getCwd());
        } catch (Exception $e) {
            $this->outputErrorAndExit($e->getMessage());
        }
    }

    /**
     * @return Composer
     */
    public function getComposer(): Composer
    {
        return $this->composer;
    }

    /**
     * @param string $message
     */
    protected function outputErrorAndExit($message): void
    {
        $output = new ConsoleOutput();
        $output->writeln(sprintf('<error>%s</error>', $message));
        exit(255);
    }

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition(
            array(
                new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

                new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
                new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
                new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
                new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
            )
        );
    }

    /**
     * Creates and adds a new command.
     *
     * @param string $name command class name
     */
    protected function addCommand($name): void
    {
        /** @var AbstractFlowCommand $command */
        $command = new $name();
        $command->setApplication($this);
        $command->setVersionControl($this->vcs);
        $this->add($command);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $output = $output ?? new ConsoleOutput();
        $outputStyle = new OutputFormatterStyle('black', 'yellow', ['bold']);
        $output->getFormatter()->setStyle('notice', $outputStyle);

        $this->addCommand(StatusCommand::class);
        $this->addCommand(StartCommand::class);
        $this->addCommand(HotfixCommand::class);
        $this->addCommand(DevelopCommand::class);
        $this->addCommand(FinishCommand::class);
        return parent::run($input, $output);
    }
}
