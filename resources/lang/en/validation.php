<?php

return [
    'required' => 'The :attribute field is required.',
    'uuid' => 'The :attribute must be a valid UUID.',
    'string' => 'The :attribute must be a string.',
    'integer' => 'The :attribute must be an integer.',
    'date' => 'The :attribute must be a valid date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'integer' => 'The :attribute must be at least :min.',
    ],
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
        'integer' => 'The :attribute may not be greater than :max.',
    ],
];

