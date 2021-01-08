<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Rl;

use Drupal\date_recur\DateRange;
use Drupal\date_recur\DateRecurHelperInterface;
use Drupal\date_recur\Exception\DateRecurHelperArgumentException;
use RRule\RfcParser;
use RRule\RRule;
use RRule\RSet;

/**
 * Helper for recurring rules implemented with rlanvin/rrule.
 *
 * @ingroup RLanvinPhpRrule
 */
class RlHelper implements DateRecurHelperInterface {

  /**
   * The RRULE set.
   *
   * @var \RRule\RSet
   */
  protected $set;

  /**
   * The time zone used to normalise other date objects.
   *
   * @var \DateTimeZone
   */
  protected $timeZone;

  /**
   * Difference between start date and start date end.
   *
   * Calculated value.
   *
   * @var \DateInterval
   */
  protected $recurDiff;

  /**
   * Constructor for DateRecurHelper.
   *
   * @param string $string
   *   The repeat rule.
   * @param \DateTimeInterface $dtStart
   *   The initial occurrence start date.
   * @param \DateTimeInterface|null $dtStartEnd
   *   The initial occurrence end date, or NULL to use start date.
   */
  public function __construct(string $string, \DateTimeInterface $dtStart, ?\DateTimeInterface $dtStartEnd = NULL) {
    $dtStartEnd = $dtStartEnd ?? clone $dtStart;
    $this->recurDiff = $dtStart->diff($dtStartEnd);
    $this->timeZone = $dtStart->getTimezone();

    // Ensure the string is prefixed with RRULE if not multiline.
    if (strpos($string, "\n") === FALSE && strpos($string, 'RRULE:') !== 0) {
      $string = "RRULE:$string";
    }

    $parts = [
      'RRULE' => [],
      'RDATE' => [],
      'EXRULE' => [],
      'EXDATE' => [],
    ];

    $lines = explode("\n", $string);
    foreach ($lines as $n => $line) {
      $line = trim($line);

      if (FALSE === strpos($line, ':')) {
        throw new DateRecurHelperArgumentException(sprintf('Multiline RRULE must be prefixed with either: RRULE, EXDATE, EXRULE, or RDATE. Missing for line %s', $n + 1));
      }

      [$part, $partValue] = explode(':', $line, 2);
      if (!isset($parts[$part])) {
        throw new DateRecurHelperArgumentException("Unsupported line: " . $part);
      }
      $parts[$part][] = $partValue;
    }

    if (($count = count($parts['RRULE'])) !== 1) {
      throw new DateRecurHelperArgumentException(sprintf('One RRULE must be provided. %d provided.', $count));
    }

    $this->set = new RSet();

    foreach ($parts as $type => $values) {
      foreach ($values as $value) {
        switch ($type) {
          case 'RRULE':
            $this->set->addRRule(new RRule($value, $dtStart));
            break;

          case 'RDATE':
            $dates = RfcParser::parseRDate('RDATE:' . $value);
            array_walk($dates, function (\DateTimeInterface $value): void {
              $this->set->addDate($value);
            });
            break;

          case 'EXDATE':
            $dates = RfcParser::parseExDate('EXDATE:' . $value);
            array_walk($dates, function (\DateTimeInterface $value): void {
              $this->set->addExDate($value);
            });
            break;

          case 'EXRULE':
            $this->set->addExRule($value);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(string $string, \DateTimeInterface $dtStart, ?\DateTimeInterface $dtStartEnd = NULL): DateRecurHelperInterface {
    return new static($string, $dtStart, $dtStartEnd);
  }

  /**
   * {@inheritdoc}
   */
  public function getRules(): array {
    return array_map(
      function (RRule $rule): RlDateRecurRule {
        // RL returns all parts, even if no values originally provided. Filter
        // out the useless parts.
        $parts = array_filter($rule->getRule());
        return new RlDateRecurRule($parts);
      },
      $this->set->getRRules()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isInfinite(): bool {
    return $this->set->isInfinite();
  }

  /**
   * {@inheritdoc}
   */
  public function generateOccurrences(?\DateTimeInterface $rangeStart = NULL, ?\DateTimeInterface $rangeEnd = NULL): \Generator {
    foreach ($this->set as $occurrenceStart) {
      /** @var \DateTime $occurrence */
      $occurrenceEnd = clone $occurrenceStart;
      $occurrenceEnd->add($this->recurDiff);

      if ($rangeStart) {
        if ($occurrenceStart < $rangeStart && $occurrenceEnd < $rangeStart) {
          continue;
        }
      }

      if ($rangeEnd) {
        if ($occurrenceStart > $rangeEnd && $occurrenceEnd > $rangeEnd) {
          break;
        }
      }

      yield new DateRange($occurrenceStart, $occurrenceEnd);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOccurrences(\DateTimeInterface $rangeStart = NULL, ?\DateTimeInterface $rangeEnd = NULL, ?int $limit = NULL): array {
    if ($this->isInfinite() && !isset($rangeEnd) && !isset($limit)) {
      throw new \InvalidArgumentException('An infinite rule must have a date or count limit.');
    }

    $generator = $this->generateOccurrences($rangeStart, $rangeEnd);
    if (isset($limit)) {
      if (!is_int($limit) || $limit < 0) {
        // Limit must be a number and more than zero.
        throw new \InvalidArgumentException('Invalid count limit.');
      }

      // Generate occurrences until the limit is reached.
      $occurrences = [];
      foreach ($generator as $value) {
        if (count($occurrences) >= $limit) {
          break;
        }
        $occurrences[] = $value;
      }
      return $occurrences;
    }

    return iterator_to_array($generator);
  }

  /**
   * {@inheritdoc}
   */
  public function getExcluded(): array {
    // Implementation normally returns the same time zone as the EXDATE from the
    // rule string, normalise it here.
    return array_map(function (\DateTime $date): \DateTime {
      return $date->setTimezone($this->timeZone);
    }, $this->set->getExDates());
  }

  /**
   * {@inheritdoc}
   */
  public function current(): DateRange {
    $occurrenceStart = $this->set->current();
    $occurrenceEnd = clone $occurrenceStart;
    $occurrenceEnd->add($this->recurDiff);
    return new DateRange($occurrenceStart, $occurrenceEnd);
  }

  /**
   * {@inheritdoc}
   */
  public function next(): void {
    $this->set->next();
  }

  /**
   * {@inheritdoc}
   */
  public function key(): ?int {
    return $this->set->key();
  }

  /**
   * {@inheritdoc}
   */
  public function valid(): bool {
    return $this->set->valid();
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    $this->set->rewind();
  }

  /**
   * Get the set.
   *
   * @return \RRule\RSet
   *   Returns the set.
   *
   * @internal this method is specific to rlanvin/rrule implementation only.
   */
  public function getRlRuleset(): RSet {
    return $this->set;
  }

}
