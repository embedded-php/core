<?php
declare(strict_types = 1);

namespace EmbeddedPhp\Core\Gpio;

interface GpioInterface {
  /**
   * Set the $pin in input mode.
   *
   * @param int $pin GPIO Pin number
   */
  public function setInputMode(int $pin): void;
  /**
   * Set the $pin in output mode.
   *
   * @param int $pin GPIO Pin number
   */
  public function setOutputMode(int $pin): void;
  /**
   * Return if $pin level is HIGH ($pin should be in input mode).
   *
   * @param int $pin GPIO Pin number
   *
   * @return boolean
   */
  public function isHigh(int $pin): bool;
  /**
   * Return if $pin level is LOW ($pin should be in input mode).
   *
   * @param int $pin GPIO Pin number
   *
   * @return boolean
   */
  public function isLow(int $pin): bool;
  /**
   * Set the $pin level to HIGH ($pin should be in output mode).
   *
   * @param int $pin GPIO Pin number
   */
  public function setHigh(int $pin): void;
  /**
   * Set the $pin level to LOW ($pin should be in output mode).
   *
   * @param int $pin GPIO Pin number
   */
  public function setLow(int $pin): void;
  /**
   * Release $pin from current state.
   *
   * @param int $pin GPIO Pin number
   */
  public function release(int $pin): void;
  /**
   * Wait for a FALLING EDGE (HIGH to LOW) change on $pin level ($pin should be in input mode).
   * Returns FALSE if $timeout interval has been reached before the event.
   *
   * @param int $pin     GPIO Pin number
   * @param int $timeout Timeout interval in nanoseconds
   *
   * @return boolean
   */
  public function waitForFalling(int $pin, int $timeout): bool;
  /**
   * Wait for a RISING EDGE (LOW to HIGH) change on $pin level ($pin should be in input mode).
   * Returns FALSE if $timeout interval has been reached before the event.
   *
   * @param int $pin     GPIO Pin number
   * @param int $timeout Timeout interval in nanoseconds
   *
   * @return boolean
   */
  public function waitForRising(int $pin, int $timeout): bool;
  /**
   * Return the number of microseconds the $pin was kept in HIGH.
   * Note: $pin must be LOW when calling this method.
   * Sequence:
   *   1. LOW
   *   2. RISE EDGE (start measure)
   *   3. HIGH (time being measured)
   *   4. FALL EDGE (end measure)
   *   5. LOW
   *
   * @param int $pin     GPIO Pin number
   * @param int $timeout Timeout interval in nanoseconds
   *
   * @return int
   */
  public function timeInHigh(int $pin, int $timeout): int;
  /**
   * Return the number of microseconds the $pin was kept in LOW.
   * Note: $pin must be HIGH when calling this method.
   * Sequence:
   *   1. HIGH
   *   2. FALL EDGE (start measure)
   *   3. LOW (time being measured)
   *   4. RISE EDGE (end measure)
   *   5. HIGH
   *
   * @param int $pin     GPIO Pin number
   * @param int $timeout Timeout interval in nanoseconds
   *
   * @return int
   */
  public function timeInLow(int $pin, int $timeout): int;
}
