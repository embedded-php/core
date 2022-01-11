<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Gpio;

use GPIO\Chip;
use GPIO\Pin;
use Pastry\Pinout;
use RuntimeException;
use const GPIO\REQUEST_DIRECTION_INPUT;
use const GPIO\REQUEST_DIRECTION_OUTPUT;
use const GPIO\REQUEST_FLAG_NONE;

/**
 * @link https://github.com/embedded-php/ext-gpio
 */
final class PhpGpioExt implements GpioInterface {
  /**
   * Pin parameters represent the logical pin numbers.
   */
  public const PIN_LOG = 0;
  /**
   * Pin parameters represent the physical pin numbers.
   */
  public const PIN_PHYS = 1;
  /**
   * Pin parameters represent the GPIO pin numbers.
   */
  public const PIN_GPIO = 2;
  /**
   * Pin parameters represent the BCM pin numbers.
   */
  public const PIN_BCM = 3;
  /**
   * Pin parameters represent the WiringPi pin numbers.
   */
  public const PIN_WPI = 4;
  /**
   * @var \GPIO\Chip
   */
  private Chip $chip;
  /**
   * One of self::PIN_* constants.
   *
   * @var int
   */
  private int $pinType;
  /**
   * @var \GPIO\Pin[]
   */
  private array $inputPins = [];
    /**
   * @var \GPIO\Pin[]
   */
  private array $outputPins = [];

  private function pinMap(int $pin): int {
    if ($this->pinType === self::PIN_LOG) {
      return $pin;
    }

    if ($this->pinType === self::PIN_PHYS) {
      return $pin - 1;
    }

    static $map = [];
    if (isset($map[$pin]) === false) {
      $gpio = match ($this->pinType) {
        self::PIN_GPIO => Pinout::fromGpio($pin),
        self::PIN_BCM => Pinout::fromBcm($pin),
        self::PIN_WPI => Pinout::fromWiringPi($pin),
        default => throw new RuntimeException(
          sprintf(
            'Invalid pin type: "%d"',
            $this->pinType
          )
        ),
      };

      $map[$pin] = $gpio->getLogical();
    }

    return $map[$pin];
  }

  public function __construct(string $device, $pinType = self::PIN_PHYS) {
    if (! extension_loaded('phpgpio')) {
      throw new RuntimeException(
        sprintf(
          'The "phpgpio" extension must be loaded to use %s',
          __CLASS__
        )
      );
    }

    $this->chip = new Chip($device);
    $this->pinType = $pinType;
  }

  public function __destruct() {
    foreach ($this->inputPins as $pin) {
      unset($pin);
    }

    foreach ($this->outputPins as $pin) {
      unset($pin);
    }
  }

  public function setInputMode(int $pin): void {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->outputPins[$pinNumber]) === true) {
      unset($this->outputPins[$pinNumber]);
    }

    if (isset($this->inputPins[$pinNumber]) === false) {
      $this->inputPins[$pinNumber] = $this->chip->getPin($pinNumber);
    }

    if ($this->inputPins[$pinNumber]->isUsed()) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is currently being used by another process',
          $pin
        )
      );
    }

    $this->inputPins[$pinNumber]->asInput('embedded-php');
  }

  public function setOutputMode(int $pin): void {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->inputPins[$pinNumber]) === true) {
      unset($this->inputPins[$pinNumber]);
    }

    if (isset($this->outputPins[$pinNumber]) === false) {
      $this->outputPins[$pinNumber] = $this->chip->getPin($pinNumber);
    }

    if ($this->outputPins[$pinNumber]->isUsed()) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is currently being used by another process',
          $pin
        )
      );
    }

    $this->outputPins[$pinNumber]->asOutput('embedded-php');
  }

  public function isHigh(int $pin): bool {
        $pinNumber = $this->pinMap($pin);
    if (isset($this->inputPins[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pinNumber
        )
      );
    }

    return $this->inputPins[$pinNumber]->isHigh();
  }

  public function isLow(int $pin): bool {
        $pinNumber = $this->pinMap($pin);
    if (isset($this->inputPins[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pinNumber
        )
      );
    }

    return $this->inputPins[$pinNumber]->isLow();
  }

  public function setHigh(int $pin): void {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->outputPins[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in output mode',
          $pinNumber
        )
      );
    }

    $this->outputPins[$pinNumber]->setHigh();
  }

  public function setLow(int $pin): void {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->outputPins[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in output mode',
          $pinNumber
        )
      );
    }

    $this->outputPins[$pinNumber]->setLow();
  }

  public function release(int $pin): void {
    $pin = $this->pinMap($pin);
    if (isset($this->inputPins[$pin])) {
      unset($this->inputPins[$pin]);
    }

    if (isset($this->outputPins[$pin])) {
      unset($this->outputPins[$pin]);
    }
  }

  public function waitForFalling(int $pin, int $timeout): bool {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->inputPins[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pinNumber
        )
      );
    }

    return $this->inputPins[$pinNumber]->waitForEdge($timeout, Pin::FALLING_EDGE) !== null;
  }

  public function waitForRising(int $pin, int $timeout): bool {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->inputPins[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pinNumber
        )
      );
    }

    return $this->inputPins[$pinNumber]->waitForEdge($timeout, Pin::RISING_EDGE) !== null;
  }

  public function timeInHigh(int $pin, int $timeout): int {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->inputPins[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pinNumber
        )
      );
    }

    $ev1 = $this->inputPins[$pinNumber]->waitForEdge($timeout, Pin::RISING_EDGE);
    if ($ev1 === null) {
      return 0;
    }

    $ev2 = $this->inputPins[$pinNumber]->waitForEdge($timeout, Pin::FALLING_EDGE);
    if ($ev2 === null) {
      return 0;
    }

    return $ev2->getTimestamp() - $ev1->getTimestamp();
  }

  public function timeInLow(int $pin, int $timeout): int {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->inputPins[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pinNumber
        )
      );
    }

    $ev1 = $this->inputPins[$pinNumber]->waitForEdge($timeout, Pin::FALLING_EDGE);
    if ($ev1 === null) {
      return 0;
    }

    $ev2 = $this->inputPins[$pinNumber]->waitForEdge($timeout, Pin::RISING_EDGE);
    if ($ev2 === null) {
      return 0;
    }

    return $ev2->getTimestamp() - $ev1->getTimestamp();
  }
}
