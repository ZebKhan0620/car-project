<?php
namespace Classes\Report;

use FPDF;

class CostReportGenerator extends FPDF {
    private $reportData;
    private $currency = 'USD';

    public function __construct() {
        parent::__construct();
        $this->SetAutoPageBreak(true, 15);
        $this->AddPage();
    }

    public function generateReport(array $calculationData, array $metadata): string {
        $this->reportData = $calculationData;
        
        $this->addReportHeader($metadata);
        $this->addCostSummary();
        $this->addDetailedBreakdown();
        $this->addExchangeRates();
        $this->addFooter();

        $filename = "cost_report_" . uniqid() . ".pdf";
        $filepath = $this->getReportPath($filename);
        
        $this->Output("F", $filepath);
        return $filename;
    }

    private function addReportHeader(array $metadata): void {
        $this->SetFont("Arial", "B", 16);
        $this->Cell(0, 10, "Vehicle Import Cost Report", 0, 1, "C");
        
        $this->SetFont("Arial", "", 10);
        $this->Cell(0, 6, "Generated: " . date("F j, Y H:i"), 0, 1, "R");
        $this->Cell(0, 6, "Reference: " . $metadata["reference"], 0, 1, "R");
        
        $this->Ln(10);
    }

    private function addCostSummary(): void {
        $this->SetFont("Arial", "B", 12);
        $this->Cell(0, 10, "Cost Summary", 0, 1);
        
        $this->SetFont("Arial", "", 10);
        $total = $this->reportData["total"] ?? 0;
        $this->Cell(120, 8, "Total Import Cost:", 1);
        $this->Cell(70, 8, $this->formatCurrency($total), 1, 1, "R");
    }

    private function addDetailedBreakdown(): void {
        $this->Ln(5);
        $this->SetFont("Arial", "B", 12);
        $this->Cell(0, 10, "Detailed Breakdown", 0, 1);
        
        $costs = [
            "Vehicle Cost" => $this->reportData["vehicle_cost"] ?? 0,
            "Shipping Cost" => $this->reportData["shipping"]["total"] ?? 0,
            "Customs Duty" => $this->reportData["duties"]["total_duties"] ?? 0
        ];

        foreach ($costs as $label => $amount) {
            $this->Cell(120, 8, $label, 1);
            $this->Cell(70, 8, $this->formatCurrency($amount), 1, 1, "R");
        }
    }

    private function addExchangeRates(): void {
        if (!empty($this->reportData["exchange_rates"])) {
            $this->Ln(5);
            $this->SetFont("Arial", "B", 12);
            $this->Cell(0, 10, "Exchange Rates", 0, 1);
            
            foreach ($this->reportData["exchange_rates"] as $currency => $rate) {
                $this->Cell(120, 8, "1 USD = $rate $currency", 1);
                $this->Ln();
            }
        }
    }

    private function addFooter(): void {
        $this->SetY(-30);
        $this->SetFont("Arial", "I", 8);
        $this->Cell(0, 10, "Note: All costs are estimates and subject to change.", 0, 1, "C");
    }

    private function formatCurrency(float $amount): string {
        return $this->currency . " " . number_format($amount, 2);
    }

    private function getReportPath(string $filename): string {
        $directory = __DIR__ . "/../../../../public/reports";
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        return $directory . "/" . $filename;
    }
}
