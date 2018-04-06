<?php
/*
 * This file is part of Solano-PHPUnit.
 *
 * (c) Solano Labs https://www.solanolabs.com/
 *
 */

use SebastianBergmann\Environment\Console;
use PHPUnit\TextUI\ResultPrinter;
/**
 * PHPUnit Printer for Solano-PHPUnit
 *
 * @package    Solano-PHPUnit
 * @author     Isaac Chapman <isaac@solanolabs.com>
 * @copyright  Solano Labs https://www.solanolabs.com/
 * @link       https://www.solanolabs.com/
 */

class SolanoLabs_PHPUnit_Printer extends ResultPrinter
{
    /**
     * @var array
     */
    private static $ansiCodes = array(
      'bold'       => 1,
      'fg-black'   => 30,
      'fg-red'     => 31,
      'fg-green'   => 32,
      'fg-yellow'  => 33,
      'fg-cyan'    => 36,
      'fg-white'   => 37,
      'bg-red'     => 41,
      'bg-green'   => 42,
      'bg-yellow'  => 43
    );

    public function __construct($out = null, $verbose = false, $colors = self::COLOR_DEFAULT, $debug = false, $numberOfColumns = 80)
    {
        parent::__construct($out, $verbose, $colors, $debug, $numberOfColumns);
    }

    /**
     * @var string
     */
    private $lastTestName = '';

    /**
     * An error occurred.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Exception              $e
     * @param float                  $time
     */
    public function addError(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        $this->writeProgressWithColor('fg-red, bold', 'ERROR');
        $this->lastTestFailed = true;

        if (getenv('TDDIUM')) {
            print "\n" . $t->__toString();
        }
    }

    /**
     * A failure occurred.
     *
     * @param \PHPUnit\Framework\Test                 $test
     * @param \PHPUnit\Framework\AssertionFailedError $e
     * @param float                                  $time
     */
    public function addFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e, float $time): void
    {
        $this->writeProgressWithColor('bg-red, fg-white', 'FAIL');
        $this->lastTestFailed = true;
        if (getenv('TDDIUM')) {
            print "\n" . $e->__toString();
        }
    }

    /**
     * A warning occurred.
     *
     * @param \PHPUnit\Framework\Test    $test
     * @param \PHPUnit\Framework\Warning $e
     * @param float                     $time
     */
    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, float $time): void
    {
        $this->writeProgressWithColor('fg-red, bold', 'WARNING');
        $this->lastTestFailed = true;
        if (getenv('TDDIUM')) {
            print "\n" . $e->__toString();
        }
    }

    /**
     * Incomplete test.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Exception              $e
     * @param float                  $time
     */
    public function addIncompleteTest(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        $this->writeProgressWithColor('fg-yellow, bold', 'INCOMPLETE');
        $this->lastTestFailed = true;
        if (getenv('TDDIUM')) {
            print "\n" . $t->__toString();
        }
    }

    /**
     * Risky test.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Exception              $e
     * @param float                  $time
     */
    public function addRiskyTest(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        $this->writeProgressWithColor('fg-yellow, bold', 'RISKY');
        $this->lastTestFailed = true;
        if (getenv('TDDIUM')) {
            print "\n" . $t->__toString();
        }
    }

    /**
     * Skipped test.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Exception              $e
     * @param float                  $time
     */
    public function addSkippedTest(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        // PHPUnit will skip a test without "starting" or "ending" it if a dependency isn't being met.
        if ($test->getName() != $this->lastTestName) {
            $this->writeNewLine();
            $this->writeProgressWithColor('fg-cyan, bold', 'SKIPPING: ' . PHPUnit_Util_Test::describe($test));
            $this->writeNewLine();
            $this->writeProgress($e->getMessage());
            $this->writeNewLine();
        } else {
            $this->writeProgressWithColor('fg-cyan, bold', 'SKIPPED');
            $this->lastTestFailed = true;
        }
    }

    /**
     * A test started.
     *
     * @param \PHPUnit\Framework\Test $test
     */
    public function startTest(\PHPUnit\Framework\Test $test): void
    {
        $this->write(
            sprintf(
                "\nStarting test '%s'.\n",
                PHPUnit_Util_Test::describe($test)
            )
        );
        $this->lastTestName = $test->getName();
    }

    /**
     * A test ended.
     *
     * @param \PHPUnit\Framework\Test $test
     */
public function endTest(\PHPUnit\Framework\Test $test, float $time): void
    {
        if (!$this->lastTestFailed) {
            $this->writeProgressWithColor('fg-green, bold', 'PASS');
        }

        if ($test instanceof \PHPUnit\Framework\TestCase) {
            $this->numAssertions += $test->getNumAssertions();
        } elseif ($test instanceof PHPUnit_Extensions_PhptTestCase) {
            $this->numAssertions++;
        }

        $this->lastTestFailed = false;
        $this->lastTestName = '';

        if ($test instanceof \PHPUnit\Framework\TestCase) {
            if (!$test->hasExpectationOnOutput()) {
                if ($output = $test->getActualOutput()) {
                    $this->writeNewLine();
                    $this->write($output);
                }
            }
        }
        $this->writeNewLine();
    }

    /**
     * Formats a buffer with a specified ANSI color sequence if colors are
     * enabled.
     *
     * @param  string $color
     * @param  string $buffer
     * @return string
     */
    protected function formatWithColor($color, $buffer)
    {
        if (!$this->colors) {
            return $buffer;
        }

        $codes = array_map('trim', explode(',', $color));
        $lines = explode("\n", $buffer);
        $padding = max(array_map('strlen', $lines));

        $styles = array();
        foreach ($codes as $code) {
            $styles[] = self::$ansiCodes[$code];
        }
        $style = sprintf("\x1b[%sm", implode(';', $styles));

        $styledLines = array();
        foreach ($lines as $line) {
            $styledLines[] = $style . str_pad($line, $padding) . "\x1b[0m";
        }

        return implode("\n", $styledLines);
    }

    /**
     * @param string $buffer
     */
    public function write($buffer)
    {
        if ($this->out) {
            fwrite($this->out, $buffer);

            if ($this->autoFlush) {
                $this->incrementalFlush();
            }
        } else {
            if (PHP_SAPI != 'cli') {
                $buffer = htmlspecialchars($buffer);
            }

            print $buffer;

            if ($this->autoFlush) {
                $this->incrementalFlush();
            }
        }
        if (getenv('TDDIUM')) {
            global $tddium_output_buffer;
            $tddium_output_buffer .= $buffer;
        }
    }
}
