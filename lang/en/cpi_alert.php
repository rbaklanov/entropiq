<?php

return [
    'subject' => ':app: EMISS CPI sync failed',
    'heading' => 'CPI data update failed',
    'intro' => 'The daily cpi:sync command finished with an error.',
    'reason' => 'Reason',
    'period' => 'Requested period',
    'time' => 'Time',
    'stored_data' => 'Previously stored values were not changed. Until the sync is restored, real amounts for new months are estimated.',
    'next_step' => 'Check that fedstat.ru is reachable and its response format has not changed, then run the command manually:',
    'reason_empty' => 'EMISS returned no data for the requested period.',
    'reason_nothing_stored' => 'EMISS returned data, but no value passed validation.',
];
