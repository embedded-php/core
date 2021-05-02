<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Gpio;

use const GPIO\REQUEST_DIRECTION_INPUT;
use const GPIO\REQUEST_DIRECTION_OUTPUT;
use const GPIO\REQUEST_FLAG_NONE;

use GPIO\Chip;
use GPIO\Line;
use EmbeddedPhp\Core\Gpio\GpioInterface;
use Pastry\Pinout;
use RuntimeException;

/**
 * @link https://github.com/flavioheleno/phpgpio
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
  private int $pinType;
  /**
   * @var \GPIO\Line[]
   */
  private array $lines = [];

  private function pinMap(int $pin): int {
    if ($this->pinType === self::PIN_LOG) {
      return $pin;
    }

    if ($this->pinType === self::PIN_PHYS) {
      return $pin - 1;
    }

    static $map = [];
    if (isset($map[$pin]) === false) {
      switch ($this->pinType) {
        case self::PIN_GPIO:
          $gpio = Pinout::fromGpio($pin);
          break;
        case self::PIN_BCM:
          $gpio = Pinout::fromBcm($pin);
          break;
        case self::PIN_WPI:
          $gpio = Pinout::fromWiringPi($pin);
          break;
        default:
          throw new RuntimeException(
            sprintf(
              'Invalid pin type: "%d"',
              $this->pinType
            )
          );
      }

      $map[$pin] = $gpio->getLogical();
    }

    return $map[$pin];
  }

  private function getValue(int $pin): int {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->lines[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in input mode',
          $pinNumber
        )
      );
    }

    return $this->lines[$pinNumber]->getValue();
  }

  private function setValue(int $pin, int $value): void {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->lines[$pinNumber]) === false) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is not in output mode',
          $pinNumber
        )
      );
    }

    $this->lines[$pinNumber]->setValue($value);
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

    if (Chip::isDevice($device) === false) {
      throw new RuntimeException(
        sprintf(
          '"%s" is not a device',
          $device
        )
      );
    }

    $this->chip = new Chip($device);
    $this->pinType = $pinType;
  }

  public function __destruct() {
    foreach ($this->lines as $line) {
      $line->setConfig(REQUEST_DIRECTION_INPUT, 0);
      unset($line);
    }
  }

  public function setInputMode(int $pin): void {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->lines[$pinNumber]) === false) {
      $this->lines[$pinNumber] = $this->chip->getLine($pinNumber);
    }

    if ($this->lines[$pinNumber]->isUsed()) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is currently being used by another process',
          $pin
        )
      );
    }

    $this->lines[$pinNumber]->request(
      'php-iot',
      REQUEST_DIRECTION_INPUT,
      REQUEST_FLAG_NONE
    );
  }

  public function setOutputMode(int $pin): void {
    $pinNumber = $this->pinMap($pin);
    if (isset($this->lines[$pinNumber]) === false) {
      $this->lines[$pinNumber] = $this->chip->getLine($pinNumber);
    }

    if ($this->lines[$pinNumber]->isUsed()) {
      throw new RuntimeException(
        sprintf(
          'Pin %d is currently being used by another process',
          $pin
        )
      );
    }

    $this->lines[$pinNumber]->request(
      'php-iot',
      REQUEST_DIRECTION_OUTPUT,
      REQUEST_FLAG_NONE,
      Line::VALUE_LOW
    );
  }

  public function isHigh(int $pin): bool {
    return $this->getValue($pin) === Line::VALUE_HIGH;
  }

  public function isLow(int $pin): bool {
    return $this->getValue($pin) === Line::VALUE_LOW;
  }

  public function setHigh(int $pin): void {
    $this->setValue($pin, Line::VALUE_HIGH);
  }

  public function setLow(int $pin): void {
    $this->setValue($pin, Line::VALUE_LOW);
  }

  public function release(int $pin): void {
    $pin = $this->pinMap($pin);
    if (isset($this->lines[$pin])) {
      unset($this->lines[$pin]);
    }
  }
}
