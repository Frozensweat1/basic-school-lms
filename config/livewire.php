<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CSP-safe Livewire runtime
    |--------------------------------------------------------------------------
    |
    | livewire-alert emits SweetAlert2 callback expressions that must run in
    | Livewire's standard evaluator. Keep this aligned with the CSP policy in
    | SecurityHeaders, which permits `unsafe-eval` for that integration.
    |
    */
    'csp_safe' => false,
];
