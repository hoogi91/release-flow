<?php

namespace Hoogi91\ReleaseFlow\Command;

use Hoogi91\ReleaseFlow\Exception;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * This commands asks for the version increment and then creates a regular release branch.
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class StartCommand extends AbstractFlowCommand
{

    /**
     * creates a release branch with version increment
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('start')->setDescription('creates a release branch with version increment');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = parent::execute($input, $output);
        if ($result !== 0) {
            return $result;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            '<question>Please enter the version increment for your next release:</question>',
            [
                self::MAJOR => '1.x.y => 2.0.0 (MUST on backwards <comment>incompatible</comment> changes)',
                self::MINOR => '1.2.x => 1.3.0 (MUST on new backwards compatible functionality or public functionality marked as deprecated. MAY on substantial new functionality or improvements)',
                self::PATCH => '1.2.3 => 1.2.4 (MUST if only backwards compatible bug fixes introduced)',
            ]
        );
        $question->setErrorMessage('Option %s is invalid.');

        // get increment choice, create next version and ask user if new version is correct
        $nextVersion = $this->getNextVersion($helper->ask($input, $output, $question));
        if ($helper->ask($input, $output, $this->getNextVersionConfirmation($nextVersion))) {
            // create release branch for new version in version control if confirmed
            $output->writeln($this->versionControl->startRelease($nextVersion));
        }
        return 0;
    }
}
