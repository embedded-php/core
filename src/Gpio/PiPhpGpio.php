<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Gpio;

use PiPHP\GPIO\GPIO;
use PiPHP\GPIO\Pin\InputPin;
use PiPHP\GPIO\Pin\OutputPin;
use PiPHP\GPIO\Pin\PinInterface;
use RuntimeException;

/**
 * @link https://github.com/PiPHP/GPIO
 */
final class PiPhpGpio implements GpioInterface {
  /**
   * @var \PiPHP\GPIO\GPIO
   */
  private GPIO $gpio;
  /**
   * @var \PiPHP\GPIO\Pin[]
   */
  private array $pins;

  public function __construct() {
    if (! class_exists('PiPHP\GPIO\GPIO')) {
      throw new RuntimeException(
        sprintf(
          'The "piphp/gpio" package must be installed to use %s',
          __CLASS__
        )
      );
    }

    $this->gpio = new GPIO();
  }

  public function setInputMode(int $pin): void {
    if (isset($this->pins[$pin]) === false) {
      $this->pins[$pin] = $this->gpio->getInputPin($pin);

      return;
    }

    if ($this->pins[$pin] instanceof OutputPin) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is already set as output',
          $pin
        )
      );
    }
  }

  public function setOutputMode(int $pin): void {
    if (isset($this->pins[$pin]) === false) {
      $this->pins[$pin] = $this->gpio->getOutputPin($pin);

      return;
    }

    if ($this->pins[$pin] instanceof InputPin) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is already set as input',
          $pin
        )
      );
    }
  }

  public function isHigh(int $pin): bool {
    if (isset($this->pins[$pin]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pin
        )
      );
    }

    if ($this->pins[$pin] instanceof OutputPin) {
      throw new RuntimeException(
        sprintf(
          'Cannot get value of an output pin',
          $pin
        )
      );
    }

    return $this->pins[$pin]->getValue() === PinInterface::VALUE_HIGH;
  }

  public function isLow(int $pin): bool {
    if (isset($this->pins[$pin]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pin
        )
      );
    }

    if ($this->pins[$pin] instanceof OutputPin) {
      throw new RuntimeException(
        sprintf(
          'Cannot get value of an output pin',
          $pin
        )
      );
    }

    return $this->pins[$pin]->getValue() === PinInterface::VALUE_LOW;
  }

  public function setHigh(int $pin): void {
    if (isset($this->pins[$pin]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in output mode',
          $pin
        )
      );
    }

    if ($this->pins[$pin] instanceof InputPin) {
      throw new RuntimeException(
        sprintf(
          'Cannot set value on an input pin',
          $pin
        )
      );
    }

    $this->pins[$pin]->setValue(PinInterface::VALUE_HIGH);
  }

  public function setLow(int $pin): void {
    if (isset($this->pins[$pin]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in output mode',
          $pin
        )
      );
    }

    if ($this->pins[$pin] instanceof InputPin) {
      throw new RuntimeException(
        sprintf(
          'Cannot set value on an input pin',
          $pin
        )
      );
    }

    $this->pins[$pin]->setValue(PinInterface::VALUE_LOW);
  }

  public function release(int $pin): void {
    if (isset($this->lines[$pin])) {
      unset($this->lines[$pin]);
    }
  }

  public function waitForFalling(int $pin, int $timeout): bool {
    if (isset($this->pins[$pin]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pin
        )
      );
    }

    if ($this->pins[$pin] instanceof OutputPin) {
      throw new RuntimeException(
        sprintf(
          'Cannot wait for a falling edge event on an output pin',
          $pin
        )
      );
    }

    while ($this->pins[$pin]->getValue() === PinInterface::VALUE_HIGH && --$timeout > 0) {
      time_nanosleep(0, 1);
    }

    return $timeout !== 0;
  }

  public function waitForRising(int $pin, int $timeout): bool {
    if (isset($this->pins[$pin]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pin
        )
      );
    }

    if ($this->pins[$pin] instanceof OutputPin) {
      throw new RuntimeException(
        sprintf(
          'Cannot wait for a raising edge event on an output pin',
          $pin
        )
      );
    }

    while ($this->pins[$pin]->getValue() === PinInterface::VALUE_LOW && --$timeout > 0) {
      time_nanosleep(0, 1);
    }

    return $timeout !== 0;
  }

  public function timeInHigh(int $pin, int $timeout): int {
    $t0 = microtime(true);
    if ($this->waitForRising($pin, $timeout) === 0) {
      return 0;
    }

    if ($this->waitForFalling($pin, $timeout) === 0) {
      return 0;
    }

    return (int)(microtime(true) - $t0);
  }

  public function timeInLow(int $pin, int $timeout): int {
    $t0 = microtime(true);
    if ($this->waitForFalling($pin, $timeout) === 0) {
      return 0;
    }

    if ($this->waitForRising($pin, $timeout) === 0) {
      return 0;
    }

    return (int)(microtime(true) - $t0);
  }
}
