<?php

namespace App\Exports;

use App\Exports\Sheets\InvestorsSummarySheet;
use App\Exports\Sheets\InvestorInvestmentTransactionsSheet;
use App\Exports\Sheets\InvestorCommissionTransactionsSheet;
use App\Exports\Sheets\InvestorTasksSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AllInvestorsComprehensiveExport implements WithMultipleSheets
{
    protected $fromDate;
    protected $toDate;
    protected $investorIds;

    public function __construct($fromDate = null, $toDate = null, $investorIds = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->investorIds = $investorIds ? (array)$investorIds : null;
    }

    public function sheets(): array
    {
        return [
            new InvestorsSummarySheet($this->fromDate, $this->toDate, $this->investorIds),
            new InvestorInvestmentTransactionsSheet($this->fromDate, $this->toDate, $this->investorIds),
            new InvestorCommissionTransactionsSheet($this->fromDate, $this->toDate, $this->investorIds),
            new InvestorTasksSheet($this->fromDate, $this->toDate, $this->investorIds),
        ];
    }
}
