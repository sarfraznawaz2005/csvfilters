<?php

namespace App\Commands;

use App\Filters\ContainsAnyFilter;
use App\Filters\ContainsFilter;
use App\Filters\DateRangeFilter;
use App\Filters\EqualFilter;
use App\Filters\NotEmptyFilter;
use App\Filters\StartsWithAnyFilter;
use App\Filters\TruthyFilter;
use DateTime;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class PolicyCommand extends Command
{
    protected $signature = 'policy
                        {type : approved|pending|past}
                        {--agent= : Agent Number (optional)}';

    protected $description = 'Gets policy details for specified criteria.';

    public function handle(): void
    {
        ini_set('auto_detect_line_endings', true);
        ini_set('memory_limit', '-1');
        ini_set('max_input_time', '-1');
        ini_set('max_execution_time', '0');

        set_time_limit(0);

        $debug = env('DEBUG');

        $type = $this->argument('type');
        $agent = $this->option('agent');

        $allowedTypes = explode('|', 'approved|pending|past');

        if (!in_array($type, $allowedTypes)) {
            $this->error('Invalid type specified: ' . $type . '. Expected: approved|pending|past');
            exit;
        }

        $this->info('Reading CSV...');

        $rows = array_map('str_getcsv', file(env('CSV_PATH')));
        $header = array_shift($rows);
        unset($rows[0]); // remove header

        $this->info('Applying Filters...');

        ####################################################################
        # FIXED FILTERS
        ####################################################################

        // Filter records that have company (MCOMPNAME) for TSL only
        EqualFilter::filter($rows, [0 => 'T']);

        if ($debug) {
            $this->warn('Filter records that have company (MCOMPNAME) for TSL only: ' . \count($rows));
        }

        // Filter records that DO NOT have "X" in MPOLICY field and is NOT empty
        ContainsFilter::filter($rows, [2 => 'X'], true);
        NotEmptyFilter::filter($rows, [2]);

        if ($debug) {
            $this->warn('Filter records that DO NOT have "X" in MPOLICY field and is NOT empty: ' . \count($rows));
        }

        // Filter records that have MPLAN field matching only 12, 13, 14, 16, 17, 18 as first two chars
        StartsWithAnyFilter::filter($rows, [30 => ['12', '13', '14', '16', '17', '18']]);

        if ($debug) {
            $this->warn('Filter records that have MPLAN field matching only 12, 13, 14, 16, 17, 18 as first two chars: ' . \count($rows));
        }

        ####################################################################
        # POLICY FILTERS
        ####################################################################

        if ($agent) {
            EqualFilter::filter($rows, [44 => $agent]);

            if ($debug) {
                $this->warn('Agent Filter: ' . \count($rows));
            }
        } else {
            NotEmptyFilter::filter($rows, [44]);
            TruthyFilter::filter($rows, [44]);

            if ($debug) {
                $this->warn('Agent must not be empty and greater than 0: ' . \count($rows));
            }
        }

        if ($type === 'approved') {

            // Filter records that have policy status (MSTATDESCR) matching below items
            ContainsAnyFilter::filter($rows, [5 => ['Premium Paying (Active)', 'Single Premium']]);

            if ($debug) {
                $this->warn('Filter records that have policy status (MSTATDESCR) matching below items: ' . \count($rows));
            }

            $sDate = new DateTime(date('m/d/Y'));
            $sDate->modify('-14 day');
            $sDate = $sDate->format('m/d/Y');

            $eDate = date('m/d/Y');

            DateRangeFilter::filter($rows, [9 => [$sDate, $eDate]]);

            if ($debug) {
                $this->warn('Approved filter: ' . \count($rows));
            }
        }

        if ($type === 'pending') {

            EqualFilter::filter($rows, [5 => 'Pending']);

            if ($debug) {
                $this->warn('Pending filter: ' . \count($rows));
            }
        }

        if ($type === 'past') {

            $policyTypes = [
                'Pending',
                'Issued, not Paid',
                'Suspended',
                'Premium Due',
                'Premium Paying (Active)',
                'Payor Death',
                'Payor Disability',
            ];

            $sDate30 = new DateTime(date('m/d/Y'));
            $sDate30->modify('-30 day');
            $sDate30 = $sDate30->format('m/d/Y');

            $sDate90 = new DateTime(date('m/d/Y'));
            $sDate90->modify('-90 day');
            $sDate90 = $sDate90->format('m/d/Y');

            ContainsAnyFilter::filter($rows, [5 => $policyTypes]);
            TruthyFilter::filter($rows, [41]);
            DateRangeFilter::filter($rows, [12 => [$sDate90, $sDate30]]);
            EqualFilter::filter($rows, [36 => '1']);

            if ($debug) {
                $this->warn('Past filter: ' . \count($rows));
            }
        }

        $this->line(ucfirst($type) . ': ' . \count($rows));

        $this->notify('TSLApp', 'Finished Calculating Policy');

        // memory cleanup
        unset($rows);
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
