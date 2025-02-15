<?php

declare(strict_types=1);

namespace Behatch\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;

class SystemContext implements Context
{
    private $root;
    private $output;
    private $lastExecutionTime;
    private $lastReturnCode;
    private $createdFiles = [];

    public function __construct($root = '.')
    {
        $this->root = $root;
    }

    public static function getTranslationResources()
    {
        return glob(__DIR__.'/../../i18n/*.xliff');
    }

    /**
     * Execute a command.
     *
     * @Given(I)execute :command
     */
    public function iExecute($cmd): void
    {
        $start = microtime(true);

        exec($cmd, $this->output, $this->lastReturnCode);

        $this->lastExecutionTime = microtime(true) - $start;
    }

    /**
     * Execute a command from project root.
     *
     * @Given(I)execute :command from project root
     */
    public function iExecuteFromProjectRoot($cmd): void
    {
        $cmd = $this->root.\DIRECTORY_SEPARATOR.$cmd;
        $this->iExecute($cmd);
    }

    /**
     * Display the last command output.
     *
     * @Then(I)display the last command output
     */
    public function iDumpCommandOutput(): void
    {
        echo implode(\PHP_EOL, $this->output);
    }

    /**
     * Command should succeed.
     *
     * @Then command should succeed
     */
    public function commandShouldSucceed(): void
    {
        if (0 !== $this->lastReturnCode) {
            throw new \Exception(sprintf('Command should succeed %b', $this->lastReturnCode));
        }
    }

    /**
     * Command should fail.
     *
     * @Then command should fail
     */
    public function commandShouldFail(): void
    {
        if (0 === $this->lastReturnCode) {
            throw new \Exception(sprintf('Command should fail %b', $this->lastReturnCode));
        }
    }

    /**
     * Command should last less than.
     *
     * @Then command should last less than :seconds seconds
     */
    public function commandShouldLastLessThan($seconds): void
    {
        if ($this->lastExecutionTime > $seconds) {
            throw new \Exception(sprintf('Last command last %s which is more than %s seconds', $this->lastExecutionTime, $seconds));
        }
    }

    /**
     * Command should last more than.
     *
     * @Then command should last more than :seconds seconds
     */
    public function commandShouldMoreLessThan($seconds): void
    {
        if ($this->lastExecutionTime < $seconds) {
            throw new \Exception(sprintf('Last command last %s which is less than %s seconds', $this->lastExecutionTime, $seconds));
        }
    }

    /**
     * Checks, that output contains specified text.
     *
     * @Then output should contain :text
     */
    public function outputShouldContain($text): void
    {
        $regex = '~'.$text.'~ui';

        $check = false;
        foreach ($this->output as $line) {
            if (1 === preg_match($regex, $line)) {
                $check = true;
                break;
            }
        }

        if (false === $check) {
            throw new \Exception(sprintf("The text '%s' was not found anywhere on output of command.\n%s", $text, implode("\n", $this->output)));
        }
    }

    /**
     * Checks, that output not contains specified text.
     *
     * @Then output should not contain :text
     */
    public function outputShouldNotContain($text): void
    {
        $regex = '~'.$text.'~ui';

        foreach ($this->output as $line) {
            if (1 === preg_match($regex, $line)) {
                throw new \Exception(sprintf("The text '%s' was found somewhere on output of command.\n%s", $text, implode("\n", $this->output)));
            }
        }
    }

    /**
     * @Given output should be:
     */
    public function outputShouldBe(PyStringNode $string): void
    {
        $expected = $string->getStrings();
        foreach ($this->output as $index => $line) {
            if ($line !== $expected[$index]) {
                throw new \Exception(sprintf("instead of\n%s", implode("\n", $this->output)));
            }
        }
    }

    /**
     * @Given output should not be:
     */
    public function outputShouldNotBe(PyStringNode $string): void
    {
        $expected = $string->getStrings();

        $check = false;
        foreach ($this->output as $index => $line) {
            if ($line !== $expected[$index]) {
                $check = true;
                break;
            }
        }

        if (false === $check) {
            throw new \Exception('Output should not be');
        }
    }

    /**
     * @Given(I)create the file :filename containing:
     * @Given(I)create the file :filename contening:
     */
    public function iCreateTheFileContaining($filename, PyStringNode $string): void
    {
        if (!is_file($filename)) {
            file_put_contents($filename, $string);
            $this->createdFiles[] = $filename;
        } else {
            throw new \RuntimeException("'$filename' already exists.");
        }
    }

    /**
     * @Then print the content of :filename file
     */
    public function printTheContentOfFile($filename): void
    {
        if (is_file($filename)) {
            echo file_get_contents($filename);
        } else {
            throw new \RuntimeException("'$filename' doesn't exists.");
        }
    }

    /**
     * @AfterScenario
     */
    public function after(): void
    {
        foreach ($this->createdFiles as $filename) {
            unlink($filename);
        }
    }
}
