<?php

namespace EslovCustomisation\Customisations;

/**
 * Stops public event submissions from using dates in the past.
 *
 * - acf/load_field sets min_date on the event occasion dates (api-event-manager
 *   defines them without one).
 * - ComponentLibrary/Component/Field/Data writes min/max on the input as Y-m-d.
 *   DatePickerFieldMapper passes minDate/maxDate at the top level, which the Field
 *   component ignores (it reads `datepicker[minDate]`), and the component would
 *   format them with the site's date_format, which <input type=date> rejects.
 * - acf/validate_value rejects past dates server side.
 *
 * The load_field and validate parts are skipped in wp-admin so editors can still
 * maintain past events.
 *
 * @upstream-shim id=frontend-form-date-min-max
 * @upstream-repo modularity-frontend-form (source/php/FieldMapping/Mapper/Acf/DatePickerFieldMapper.php, source/php/DataProcessor/Validators/FieldValidationWithAcf.php); api-event-manager (source/php/AcfFields/json/event-time-fields.json)
 * @upstream-broken DatePickerFieldMapper puts minDate/maxDate at the top level, so no min/max attribute is rendered. FieldValidationWithAcf never checks min_date/max_date. api-event-manager sets no min_date on occasions date/untilDate. Past dates pass in the browser and on the server.
 * @upstream-fix-needed modularity-frontend-form#163: render min/max as Y-m-d and validate min_date/max_date server side. api-event-manager#161: set min_date on date and untilDate.
 * @remove-when Both helsingborg-stad/modularity-frontend-form#163 and helsingborg-stad/api-event-manager#161 are released and deployed here. Grep DatePickerFieldMapper.php for min/max in fieldAttributeList, FieldValidationWithAcf.php for min_date, and event-time-fields.json for min_date. Then delete this class, its App.php line and the sv_SE string.
 * @verify-removal On the event site form, step 4 date inputs have a min of today, and a past date is rejected in the browser and by the REST endpoint (POST a past date).
 */
class EventFormNoPastDates
{
    private const FIELD_KEYS = [
        'field_68d28f0c89b49', // occasions > date
        'field_68d28f0c8a008', // occasions > untilDate
    ];

    public function __construct()
    {
        add_filter('acf/load_field/type=date_picker', [$this, 'setMinDate']);
        add_filter('ComponentLibrary/Component/Field/Data', [$this, 'setDateInputLimits']);
        add_filter('acf/validate_value/type=date_picker', [$this, 'validate'], 10, 4);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function setMinDate(array $field): array
    {
        if ($this->appliesTo($field)) {
            $field['min_date'] = $this->today();
        }

        return $field;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function setDateInputLimits(array $data): array
    {
        if (($data['type'] ?? null) !== 'date') {
            return $data;
        }

        foreach (['min' => 'minDate', 'max' => 'maxDate'] as $attribute => $limit) {
            $timestamp = empty($data[$limit]) ? false : strtotime((string) $data[$limit]);
            if ($timestamp !== false) {
                $data['fieldAttributeList'][$attribute] = wp_date('Y-m-d', $timestamp);
            }
        }

        return $data;
    }

    /**
     * @param true|string $valid
     * @param mixed $value
     * @param array<string, mixed> $field
     * @return true|string
     */
    public function validate($valid, $value, array $field)
    {
        if ($valid !== true || !$this->appliesTo($field) || !is_string($value) || $value === '') {
            return $valid;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, wp_timezone());
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return $valid;
        }

        if ($value < $this->today()) {
            return __('The date cannot be in the past.', 'eslov-customisation');
        }

        return $valid;
    }

    private function appliesTo(array $field): bool
    {
        return !is_admin() && in_array($field['key'] ?? '', self::FIELD_KEYS, true);
    }

    private function today(): string
    {
        return wp_date('Y-m-d');
    }
}
