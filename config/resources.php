<?php

return [
    // Absolute tolerance in resource unit (mc, buc, ore) before a discrepancy blocks payment.
    'quantity_tolerance' => env('RESOURCE_QUANTITY_TOLERANCE', 0.20),
];