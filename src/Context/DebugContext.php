<?php

declare(strict_types=1);

namespace Behatch\Context;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Gherkin\Node\StepNode;
use Behat\Mink\Exception\UnsupportedDriverActionException;

class DebugContext extends BaseContext
{
    /**
     * @var string
     */
    private $screenshotDir;

    public function __construct($screenshotDir = '.')
    {
        $this->screenshotDir = $screenshotDir;
    }

    /**
     * Pauses the scenario until the user presses a key. Useful when debugging a scenario.
     *
     * @Then(I)put a breakpoint
     */
    public function iPutABreakpoint(): void
    {
        fwrite(\STDOUT, "\033[s    \033[93m[Breakpoint] Press \033[1;93m[RETURN]\033[0;93m to continue...\033[0m");
        while ('' == fgets(\STDIN, 1024)) {
        }
        fwrite(\STDOUT, "\033[u");
    }

    /**
     * Saving a screenshot.
     *
     * @When I save a screenshot in :filename
     */
    public function iSaveAScreenshotIn($filename): void
    {
        sleep(1);
        $this->saveScreenshot($filename, $this->screenshotDir);
    }

    /**
     * @AfterStep
     */
    public function failScreenshots(AfterStepScope $scope): void
    {
        if ($scope->getTestResult()->isPassed()) {
            return;
        }

        $this->displayProfilerLink();

        $suiteName = urlencode(str_replace(' ', '_', $scope->getSuite()->getName()));
        $featureName = urlencode(str_replace(' ', '_', $scope->getFeature()->getTitle()));

        if ($this->getBackground($scope)) {
            $scenarioName = 'background';
        } else {
            $scenario = $this->getScenario($scope);
            $scenarioName = urlencode(str_replace(' ', '_', $scenario->getTitle()));
        }

        $filename = sprintf('fail_%s_%s_%s_%s.png', time(), $suiteName, $featureName, $scenarioName);
        $this->saveScreenshot($filename, $this->screenshotDir);
    }

    private function displayProfilerLink(): void
    {
        try {
            $headers = $this->getMink()->getSession()->getResponseHeaders();
            echo "The debug profile URL {$headers['X-Debug-Token-Link'][0]}";
        } catch (\Exception $e) {
            /* Intentionally leave blank */
        }
    }

    /**
     * @return \Behat\Gherkin\Node\ScenarioInterface
     */
    private function getScenario(AfterStepScope $scope)
    {
        $scenarios = $scope->getFeature()->getScenarios();
        foreach ($scenarios as $scenario) {
            $stepLinesInScenario = array_map(
                function (StepNode $step) {
                    return $step->getLine();
                },
                $scenario->getSteps()
            );
            if (\in_array($scope->getStep()->getLine(), $stepLinesInScenario, true)) {
                return $scenario;
            }
        }

        throw new \LogicException('Unable to find the scenario');
    }

    /**
     * @return \Behat\Gherkin\Node\BackgroundNode|bool
     */
    private function getBackground(AfterStepScope $scope)
    {
        $background = $scope->getFeature()->getBackground();
        if (!$background) {
            return false;
        }
        $stepLinesInBackground = array_map(
            function (StepNode $step) {
                return $step->getLine();
            },
            $background->getSteps()
        );
        if (\in_array($scope->getStep()->getLine(), $stepLinesInBackground, true)) {
            return $background;
        }

        return false;
    }

    public function saveScreenshot($filename = null, $filepath = null): void
    {
        try {
            parent::saveScreenshot($filename, $filepath);
        } catch (UnsupportedDriverActionException $e) {
            return;
        }
    }
}
