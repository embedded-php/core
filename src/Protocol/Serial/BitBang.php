<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Protocol\Serial;

use EmbeddedPhp\Core\Gpio\GpioInterface;
use EmbeddedPhp\Core\Protocol\ProtocolInterface;
use RuntimeException;

/**
 * Wraps a SPI bus to provide sendData and sendCommand methods.
 * This is a software implementation and is thus a lot slower than the default SPI interface.
 *
 * @link https://github.com/rm-hull/luma.core/blob/master/luma/core/interface/serial.py
 */
class BitBang implements ProtocolInterface {
  protected GpioInterface $gpio;

  protected int $pinSCLK;
  protected int $pinSDA;
  protected int $pinCE;
  protected int $pinDC;
  protected int $pinRST;
  protected int $transferSize;

  protected function sendBytes(int ...$bytes): void {
    if ($this->pinCE > 0) {
      $this->gpio->setLow($this->pinCE);
    }

    foreach ($bytes as $byte) {
      for ($i = 0; $i < 8; $i++) {
        $this->gpio->setValue($this->pinSDA, ($byte & 0x80) !== 0);
        $this->gpio->setHigh($this->pinSCLK);
        $byte <<= 1;
        $this->gpio->setLow($this->pinSCLK);
      }
    }

    if ($this->pinCE > 0) {
      $this->gpio->setHigh($this->pinCE);
    }
  }

  /**
   * Notes:
   * 1. Some devices may require a $resetHoldTime of 100000 (100ms) or more to fully reset the display;
   * 2. Some devices may require a $resetReleaseTime of 150000 (150ms) or more after reset was triggered before the
   * device can accept the initialization sequence.
   *
   * @param GpioInterface $gpio             GPIO interface.
   * @param int           $pinSCLK          The GPIO pin to connect the SPI clock to.
   * @param int           $pinSDA           The GPIO pin to connect the SPI data (MOSI) line to.
   * @param int           $pinCE            The GPIO pin to connect the SPI chip enable (CE) line to.
   * @param int           $pinDC            The GPIO pin to connect data/command select (DC) to.
   * @param int           $pinRST           The GPIO pin to connect reset (RES / RST) to.
   * @param int           $transferSize     Max bytes to transfer in one go.
   * @param int           $resetHoldTime    The number of microseconds to hold reset active.
   * @param int           $resetReleaseTime The number of microseconds to delay afer reset.
   */
  public function __construct(
    GpioInterface $gpio,
    int $pinSCLK,
    int $pinSDA,
    int $pinCE,
    int $pinDC,
    int $pinRST,
    int $transferSize = 4096,
    int $resetHoldTime = 0,
    int $resetReleaseTime = 0
  ) {
    $this->gpio = $gpio;
    $this->pinSCLK = $pinSCLK;
    $this->pinSDA = $pinSDA;
    $this->pinCE = $pinCE;
    $this->pinDC = $pinDC;
    $this->pinRST = $pinRST;
    $this->transferSize = $transferSize;

    foreach ([$pinSCLK, $pinSDA, $pinCE, $pinDC, $pinRST] as $pin) {
      if ($pin > 0) {
        $this->gpio->setOutputMode($pin);
      }
    }

    if ($this->pinRST > 0) {
      // Reset the device
      $this->gpio->setLow($this->pinRST);
      usleep($resetHoldTime);
      // Keep RESET pulled high
      $this->gpio->setHigh($this->pinRST);
      usleep($resetReleaseTime);
    }
  }

  public function sendCommand(int ...$commands): void {
    if ($this->pinDC > 0) {
      $this->gpio->setLow($this->pinDC);
    }

    $this->sendBytes(...$commands);
  }

  public function sendData(int ...$data): void {
    if ($this->pinDC > 0) {
      $this->gpio->setHigh($this->pinDC);
    }

    $dataSize = count($data);
    for ($i = 0; $i < $dataSize; $i += $this->transferSize) {
      $this->sendBytes(...array_slice($data, $i, $this->transferSize));
    }
  }

  public function cleanup(): void {
    foreach ([$this->pinSCLK, $this->pinSDA, $this->pinCE, $this->pinDC, $this->pinRST] as $pin) {
      if ($pin > 0) {
        $this->gpio->release($pin);
      }
    }
  }
}
