<?php

return [
    // If true, overlapping reservations for the same equipment are blocked.
    // If false, reservation is saved and a warning message is shown.
    'strict_conflict_block' => (bool) env('EQUIPMENT_STRICT_CONFLICT_BLOCK', false),
];
