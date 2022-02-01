<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Protocol\Serial\Spi;

use EmbeddedPhp\Core\Gpio\GpioInterface;
use RuntimeException;
use SPI\Bus;
use Webmozart\Assert\Assert;

/**
 * Wraps a SPI bus to provide sendData and sendCommand methods.
 *
 * @link https://github.com/rm-hull/luma.core/blob/master/luma/core/interface/serial.py
 * @link https://github.com/embedded-php/ext-spi
 */
final class PhpSpiExt extends BitBang {
  /**
   * SPI bus instance.
   *
   * @var \SPI\Bus
   */
  protected Bus $bus;

  protected function sendBytes(int ...$bytes): void {
    Assert::maxCount(
      $bytes,
      $this->transferSize,
      sprintf(
        'Max bytes to transfer in one go is limited to %d bytes, got %%s',
        $this->transferSize
      )
    );

    $this->bus->write($bytes);
  }

  /**
   * @param GpioInterface $gpio
   * @param int           $busId            SPI port, usually 0 (default) or 1.
   * @param int           $chipSelect       SPI device, usually 0 (default) or 1.
   * @param int           $mode             SPI mode as two bit pattern of clock polarity and phase [CPOL|CPHA], 0-3.
   * @param int           $bits             SPI number of bits per word.
   * @param int           $speed            SPI bus speed, defaults to 8MHz.
   * @param int           $delay            SPI delay between write to the bus.
   * @param int           $pinDC            The GPIO pin to connect data/command select (DC) to (defaults to 24 BCM).
   * @param int           $pinRST           The GPIO pin to connect reset (RES / RST) to (defaults to 25 BCM).
   * @param int           $transferSize     Max bytes to transfer in one go.
   * @param int           $resetHoldTime    The number of microseconds to hold reset active.
   * @param int           $resetReleaseTime The number of microseconds to delay after reset.
   */
  public function __construct(
    GpioInterface $gpio,
    int $busId = 0,
    int $chipSelect = 0,
    int $mode = 3,
    int $bits = 8,
    int $speed = 8000000,
    int $delay = 0,
    int $pinDC = 24,
    int $pinRST = 25,
    int $transferSize = 4096,
    int $resetHoldTime = 500000,
    int $resetReleaseTime = 1000000
  ) {
    if (! extension_loaded('spi')) {
      throw new RuntimeException(
        sprintf(
          'The "spi" extension must be loaded to use %s',
          __CLASS__
        )
      );
    }

    $hz = [
      500000,
      1000000,
      2000000,
      4000000,
      8000000,
      16000000,
      20000000,
      24000000,
      28000000,
      32000000,
      36000000,
      40000000,
      44000000,
      48000000,
      50000000,
      52000000
    ];

    if (in_array($speed, $hz) === false) {
      throw new RuntimeException(
        sprintf(
          'Invalid bus speed "%d", valid values are: %s',
          $speed,
          implode(', ', $hz)
        )
      );
    }

    parent::__construct($gpio, -1, -1, -1, $pinDC, $pinRST, $transferSize, $resetHoldTime, $resetReleaseTime);
    $this->bus = new Bus($busId, $chipSelect, $mode, $bits, $speed, $delay);
  }

  public function cleanup(): void {
    unset($this->bus);
    parent::cleanup();
  }
}
