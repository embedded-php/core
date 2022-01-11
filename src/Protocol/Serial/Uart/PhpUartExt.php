<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Protocol\Serial\Uart;

use EmbeddedPhp\Core\Protocol\ProtocolInterface;
use UART\Serial;
use RuntimeException;

/**
 * Wraps a UART bus to provide sendData and sendCommand methods.
 *
 * @link https://github.com/rm-hull/luma.core/blob/master/luma/core/interface/serial.py
 * @link https://github.com/embedded-php/ext-uart
 */
final class PhpUartExt implements ProtocolInterface {
  /**
   * UART Serial instance.
   *
   * @var \UART\Serial
   */
  protected Serial $serial;

  public function __construct(string $device, int $baudRate) {
    if (! extension_loaded('phpuart')) {
      throw new RuntimeException(
        sprintf(
          'The "phpuart" extension must be loaded to use %s',
          __CLASS__
        )
      );
    }

    $this->serial = new Serial($device, $baudRate);
  }

  public function sendCommand(int ...$commands): void {
    $this->serial->putString(pack('C*', ...$commands));
  }

  public function sendData(int ...$data): void {
    $this->serial->putString(pack('C*', ...$data));
  }

  public function cleanup(): void {
    // there is no cleanup to be done afaik
  }

}
