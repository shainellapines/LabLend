<?php

/*
| LabLend business rules (CLAUDE.md section 7).
| Read them with config('lablend.max_days_ahead') etc. Never hard-code these numbers elsewhere.
*/

return [
    'min_lead_minutes' => 60,   // reserve at least 1 hour ahead
    'max_days_ahead' => 14,
    'max_hours' => [            // longest allowed reservation, per role
        'student' => 8,
        'faculty' => 72,
        'admin' => 168,
    ],
    'student_max_active' => 3,  // pending + approved + released at the same time
    'overdue_reminder_time' => '08:00',
    'reference_prefix' => 'RSV', // reference numbers look like RSV-2026-0001
];
