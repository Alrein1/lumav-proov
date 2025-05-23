<?php

namespace Laminas\I18n\Validator;

use IntlDateFormatter;
use IntlException;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception as ValidatorException;
use Locale;

use function date_default_timezone_get;
use function intl_is_failure;
use function is_string;

/** @final */
class DateTime extends AbstractValidator
{
    public const INVALID          = 'datetimeInvalid';
    public const INVALID_DATETIME = 'datetimeInvalidDateTime';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::INVALID          => 'Invalid type given. String expected',
        self::INVALID_DATETIME => 'The input does not appear to be a valid datetime',
    ];

    /**
     * Optional locale
     *
     * @var string|null
     */
    protected $locale;

    /** @var int|null */
    protected $dateType;

    /** @var int|null */
    protected $timeType;

    /**
     * Optional timezone
     *
     * @var string|null
     */
    protected $timezone;

    /** @var string|null */
    protected $pattern;

    /** @var int|null */
    protected $calendar;

    /** @var IntlDateFormatter|null */
    protected $formatter;

    /**
     * Is the formatter invalidated
     * Invalidation occurs when immutable properties are changed
     *
     * @var bool
     */
    protected $invalidateFormatter = false;

    /**
     * Constructor for the Date validator
     *
     * @param iterable<string, mixed> $options
     */
    public function __construct($options = [])
    {
        // Delaying initialization until we know ext/intl is available
        $this->dateType = IntlDateFormatter::NONE;
        $this->timeType = IntlDateFormatter::NONE;
        $this->calendar = IntlDateFormatter::GREGORIAN;

        parent::__construct($options);

        if (null === $this->locale) {
            $this->locale = Locale::getDefault();
        }
        if (null === $this->timezone) {
            $this->timezone = date_default_timezone_get();
        }
    }

    /**
     * Sets the calendar to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param int|null $calendar
     * @return $this
     */
    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;

        return $this;
    }

    /**
     * Returns the calendar to by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return int|null
     */
    public function getCalendar()
    {
        if ($this->formatter && ! $this->invalidateFormatter) {
            return $this->getIntlDateFormatter()->getCalendar();
        }

        return $this->calendar;
    }

    /**
     * Sets the date format to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param int|null $dateType
     * @return $this
     */
    public function setDateType($dateType)
    {
        $this->dateType            = $dateType;
        $this->invalidateFormatter = true;

        return $this;
    }

    /**
     * Returns the date format used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return int|null
     */
    public function getDateType()
    {
        return $this->dateType;
    }

    /**
     * Sets the pattern to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param string|null $pattern
     * @return $this
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Returns the pattern used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return string|null
     */
    public function getPattern()
    {
        if ($this->formatter && ! $this->invalidateFormatter) {
            return $this->getIntlDateFormatter()->getPattern();
        }

        return $this->pattern;
    }

    /**
     * Sets the time format to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param int|null $timeType
     * @return $this
     */
    public function setTimeType($timeType)
    {
        $this->timeType            = $timeType;
        $this->invalidateFormatter = true;

        return $this;
    }

    /**
     * Returns the time format used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return int|null
     */
    public function getTimeType()
    {
        return $this->timeType;
    }

    /**
     * Sets the timezone to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param string|null $timezone
     * @return $this
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Returns the timezone used by the IntlDateFormatter or the system default if none given
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return string|null
     */
    public function getTimezone()
    {
        if ($this->formatter && ! $this->invalidateFormatter) {
            return $this->getIntlDateFormatter()->getTimeZoneId();
        }

        return $this->timezone;
    }

    /**
     * Sets the locale to be used by the IntlDateFormatter
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param string|null $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale              = $locale;
        $this->invalidateFormatter = true;

        return $this;
    }

    /**
     * Returns the locale used by the IntlDateFormatter or the system default if none given
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return string|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Returns true if and only if $value is a floating-point value
     *
     * @param  string $value
     * @return bool
     * @throws ValidatorException\InvalidArgumentException
     */
    public function isValid($value)
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);

            return false;
        }

        $this->setValue($value);

        try {
            $formatter = $this->getIntlDateFormatter();

            if (intl_is_failure($formatter->getErrorCode())) {
                throw new ValidatorException\InvalidArgumentException($formatter->getErrorMessage());
            }
        } catch (IntlException $intlException) {
            throw new ValidatorException\InvalidArgumentException($intlException->getMessage(), 0, $intlException);
        }

        try {
            $timestamp = $formatter->parse($value);

            if (intl_is_failure($formatter->getErrorCode()) || $timestamp === false) {
                $this->error(self::INVALID_DATETIME);
                $this->invalidateFormatter = true;
                return false;
            }
        } catch (IntlException) {
            $this->error(self::INVALID_DATETIME);
            $this->invalidateFormatter = true;
            return false;
        }

        return true;
    }

    /**
     * Returns a non lenient configured IntlDateFormatter
     *
     * @return IntlDateFormatter
     */
    protected function getIntlDateFormatter()
    {
        if ($this->formatter === null || $this->invalidateFormatter) {
            $this->formatter = new IntlDateFormatter(
                $this->getLocale(),
                $this->getDateType(),
                $this->getTimeType(),
                $this->timezone,
                $this->calendar,
                $this->pattern ?? ''
            );

            $this->formatter->setLenient(false);

            $this->setTimezone($this->formatter->getTimezone());
            $this->setCalendar($this->formatter->getCalendar());
            $this->setPattern($this->formatter->getPattern());

            $this->invalidateFormatter = false;
        }

        return $this->formatter;
    }
}
