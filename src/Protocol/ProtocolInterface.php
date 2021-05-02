<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Protocol;

interface ProtocolInterface {
  /**
   * Sends a command or sequence of commands through to the device.
   */
  public function sendCommand(int ...$commands): void;

  /**
   * Sends a data byte or sequence of data bytes through to the device.
   */
  public function sendData(int ...$data): void;

  /**
   * Clean up resources.
   */
  public function cleanup(): void;
}
