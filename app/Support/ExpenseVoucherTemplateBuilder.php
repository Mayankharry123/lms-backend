<?php

namespace App\Support;

use App\Models\User;
use App\Models\VoucherType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class ExpenseVoucherTemplateBuilder
 *
 * Dedicated builder responsible for constructing, styling, and rendering
 * the Expense/Reimbursement Voucher sample Excel template spreadsheet.
 */
class ExpenseVoucherTemplateBuilder
{
    /**
     * Path to default template file if available.
     */
    protected string $templatePath;

    public function __construct(?string $templatePath = null)
    {
        $this->templatePath = $templatePath ?: base_path('resources/templates/expense-voucher-template.xlsx');
    }

    /**
     * Build the spreadsheet instance for the specified voucher type and user.
     *
     * @param VoucherType $voucherType
     * @param User|null $user
     * @return Spreadsheet
     */
    public function build(VoucherType $voucherType, ?User $user = null): Spreadsheet
    {
        if (file_exists($this->templatePath)) {
            $spreadsheet = IOFactory::load($this->templatePath);
        } else {
            $spreadsheet = $this->buildFromScratch();
        }

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($voucherType->name, 0, 31));

        // Populate user details if available
        if ($user) {
            if (!empty($user->name)) {
                $sheet->setCellValue('B1', strtoupper((string) $user->name));
            }
            $designation = $user->profile?->designation?->name ?? $user->designation ?? null;
            if (!empty($designation)) {
                $sheet->setCellValue('B2', strtoupper((string) $designation));
            }
        }
        $sheet->setCellValue('B4', date('M-y'));

        // Show voucher type on row 5 banner
        $sheet->setCellValue('A5', 'VOUCHER TYPE: ' . strtoupper($voucherType->name));
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Row 6 sheet header
        $sheet->setCellValue('A6', 'Reimbursement/Conveyance Sheet');

        return $spreadsheet;
    }

    /**
     * Render the spreadsheet to a binary XLSX string.
     *
     * @param VoucherType $voucherType
     * @param User|null $user
     * @return string
     */
    public function render(VoucherType $voucherType, ?User $user = null): string
    {
        $spreadsheet = $this->build($voucherType, $user);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = (string) ob_get_clean();

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        return $content;
    }

    /**
     * Programmatically construct clean spreadsheet layout matching the official format from scratch.
     *
     * @return Spreadsheet
     */
    public function buildFromScratch(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('EXPENSE FORMAT');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(78);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(14);

        // Row heights
        for ($r = 1; $r <= 4; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(19);
        }
        $sheet->getRowDimension(5)->setRowHeight(14);
        $sheet->getRowDimension(6)->setRowHeight(20);
        $sheet->getRowDimension(7)->setRowHeight(20);
        for ($r = 8; $r <= 20; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(16);
        }
        $sheet->getRowDimension(21)->setRowHeight(20);

        // Meta Box
        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'RAKESH KUMAR');
        $sheet->mergeCells('B1:E1');

        $sheet->setCellValue('A2', 'Designation');
        $sheet->setCellValue('B2', 'ACCOUNTANT');
        $sheet->mergeCells('B2:E2');

        $sheet->setCellValue('A3', 'Company');
        $sheet->setCellValue('B3', 'MOBIYOUND DIGITAL AD AGENCY PVT LTD');
        $sheet->mergeCells('B3:E3');

        $sheet->setCellValue('A4', 'Month');
        $sheet->setCellValue('B4', date('M-y'));
        $sheet->mergeCells('B4:E4');

        $sheet->mergeCells('A5:E5');

        $sheet->setCellValue('A6', 'Reimbursement/Conveyance Sheet');
        $sheet->mergeCells('A6:E6');
        $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6')->getFont()->setBold(true);

        $headers = ['A7' => 'Date', 'B7' => 'Particuler', 'C7' => 'Purpose', 'D7' => 'Mode', 'E7' => 'Amount'];
        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Borders
        $thin = Border::BORDER_THIN;
        $sheet->getStyle('A1:E4')->getBorders()->getAllBorders()->setBorderStyle($thin);
        $sheet->getStyle('A1:A4')->getFont()->setBold(true);
        $sheet->getStyle('A5:E5')->getBorders()->getAllBorders()->setBorderStyle($thin);
        $sheet->getStyle('A6:E21')->getBorders()->getAllBorders()->setBorderStyle($thin);

        // Row 21 Total
        $sheet->setCellValue('A21', 'Total');
        $sheet->mergeCells('A21:D21');
        $sheet->getStyle('A21')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('E21', '=SUM(E8:E20)');
        $sheet->getStyle('E21')->getFont()->setBold(true);

        // Signature section
        $sheet->mergeCells('A22:D22');
        $sheet->getStyle('A22:E22')->getBorders()->getAllBorders()->setBorderStyle($thin);

        $sheet->setCellValue('A23', 'Employee Sign');
        $sheet->setCellValue('E23', 'Authorised Sign');
        $sheet->getStyle('A23')->getFont()->setBold(true);
        $sheet->getStyle('E23')->getFont()->setBold(true);
        $sheet->getStyle('E23')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->mergeCells('A24:C24');
        $sheet->setCellValue('A24', 'Employee Name                                          Verified By');
        $sheet->mergeCells('D24:E24');
        $sheet->setCellValue('D24', 'Approved By');
        $sheet->getStyle('A24:E24')->getFont()->setBold(true);

        // Border around signature box
        $sheet->getStyle('A24:E28')->getBorders()->getOutline()->setBorderStyle($thin);
        $sheet->getStyle('A24:E24')->getBorders()->getBottom()->setBorderStyle($thin);
        $sheet->getStyle('D24:D28')->getBorders()->getLeft()->setBorderStyle($thin);

        return $spreadsheet;
    }
}
