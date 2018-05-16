<?php

namespace App\Commands;

use App\Filters\ContainsAnyFilter;
use App\Filters\ContainsFilter;
use App\Filters\DateRangeFilter;
use App\Filters\EqualFilter;
use App\Filters\NotEmptyFilter;
use App\Filters\StartsWithAnyFilter;
use App\Filters\TruthyFilter;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class SalesCommand extends Command
{
    protected $signature = 'sales
                        {type : all|yearly|monthly}
                        {--agent= : Agent Number (optional)}';

    protected $description = 'Gets sales details for specified period and criteria.';

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

        $allowedTypes = explode('|', 'all|yearly|monthly');

        if (!in_array($type, $allowedTypes)) {
            $this->error('Invalid type specified: ' . $type . '. Expected: all|yearly|monthly');
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
        # SALES FILTERS
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

        if ($type === 'monthly') {

            // Filter records that have policy status (MSTATDESCR) matching below items
            ContainsAnyFilter::filter($rows, [5 => ['Premium Paying (Active)', 'Fully Paid-Up', 'Single Premium']]);

            if ($debug) {
                $this->warn('Filter records that have policy status (MSTATDESCR) matching below items: ' . \count($rows));
            }

            $sDate = date('m/1/Y');
            $eDate = date('m/d/Y');

            DateRangeFilter::filter($rows, [9 => [$sDate, $eDate]]);

            if ($debug) {
                $this->warn('Monthly filter: ' . \count($rows));
            }
        }

        if ($type === 'yearly') {
            $sDate = date('1/1/Y');
            $eDate = date('m/d/Y');

            DateRangeFilter::filter($rows, [9 => [$sDate, $eDate]]);

            if ($debug) {
                $this->warn('Yearly filter: ' . \count($rows));
            }
        }

        $this->info('Calculating Sales...');

        foreach ($rows as $key => $row) {
            foreach ($row as $key2 => $array) {
                if ($key2 !== 34) {
                    unset($rows[$key][$key2]);
                }
            }
        }

        $sales = collect($rows)->flatten()->sum();

        $this->line(ucfirst($type) . ' Sales: $' . number_format($sales, 2));

        $this->notify('TSLApp', 'Finished Calculating Sales');

        // memory cleanup
        unset($rows);

    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
