<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Gpio;

interface GpioInterface {
  public function setInputMode(int $pin): void;
  public function setOutputMode(int $pin): void;
  public function isHigh(int $pin): bool;
  public function isLow(int $pin): bool;
  public function setHigh(int $pin): void;
  public function setLow(int $pin): void;
  public function release(int $pin): void;
}
