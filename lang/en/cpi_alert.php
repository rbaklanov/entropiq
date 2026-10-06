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
    'stale_subject' => ':app: CPI data is stale',
    'stale_heading' => 'CPI data has not been updated for a long time',
    'stale_intro' => 'The daily check found no recent consumer price index values. The sync may have stopped working without an explicit error.',
    'stale_latest' => 'Latest published month',
    'stale_expected' => 'Expected no earlier than',
    'stale_none' => 'no data',
    'stale_causes' => 'Possible causes: the scheduler is not running (check storage/logs/scheduler.log and supervisor), the source (EMISS or Rosstat) is unreachable from the server, changed its data format or stopped returning "All goods and services". Until data appears, real amounts for new months are estimated.',
    'reason_empty' => 'The data source returned no values for the requested period.',
    'reason_nothing_stored' => 'EMISS returned data, but no value passed validation.',
];
