<?php

return [
    'convenience_fee_rate' => max(0, (float) env('CONVENIENCE_FEE_RATE', 0.05)),
    'vat_rate' => max(0, (float) env('VAT_RATE', 0)),
];
