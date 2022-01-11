<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Protocol\Serial\I2c;

use EmbeddedPhp\Core\Protocol\ProtocolInterface;
use I2C\Bus;
use RuntimeException;

/**
 * Wraps a I2C bus to provide sendData and sendCommand methods.
 *
 * @link https://github.com/rm-hull/luma.core/blob/master/luma/core/interface/serial.py
 * @link https://github.com/embedded-php/ext-i2c
 */
final class PhpI2cExt implements ProtocolInterface {
  /**
   * I2C bus instance.
   *
   * @var \I2C\Bus
   */
  protected Bus $bus;

  public function __construct(int $busId, int $deviceAddress) {
    if (! extension_loaded('phpi2c')) {
      throw new RuntimeException(
        sprintf(
          'The "phpi2c" extension must be loaded to use %s',
          __CLASS__
        )
      );
    }

    $this->bus = new Bus($busId, $deviceAddress);
  }

  public function sendCommand(int ...$commands): void {
    $this->bus->writeBlock(0x00, ...$commands);
  }

  public function sendData(int ...$data): void {
    $this->bus->writeBlock(0x40, ...$data);
  }

  public function cleanup(): void {
    // there is no cleanup to be done afaik
  }

}
