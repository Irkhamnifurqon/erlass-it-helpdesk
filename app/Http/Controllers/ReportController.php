<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);
        $month = $request->string('month')->toString();
        $period = static fn ($query) => $query->when($month, fn ($filtered) => $filtered->whereBetween('tickets.created_at', [
            $month.'-01 00:00:00',
            now()->parse($month.'-01')->endOfMonth(),
        ]));
        $monthly = Ticket::query()->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total");
        $categories = Ticket::query()->join('categories', 'categories.id', '=', 'tickets.category_id')->selectRaw('categories.name, COUNT(*) as total')->groupBy('categories.id', 'categories.name');
        $departments = Ticket::query()->join('users', 'users.id', '=', 'tickets.requester_id')->join('departments', 'departments.id', '=', 'users.department_id')->selectRaw('departments.name, COUNT(*) as total')->groupBy('departments.id', 'departments.name');
        $average = Ticket::query()->whereNotNull('resolved_at')->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as average');
        $agents = Ticket::query()
            ->join('ticket_assignments', 'ticket_assignments.ticket_id', '=', 'tickets.id')
            ->join('users', 'users.id', '=', 'ticket_assignments.assigned_to')
            ->leftJoin('ticket_ratings', 'ticket_ratings.ticket_id', '=', 'tickets.id')
            ->whereNull('ticket_assignments.unassigned_at')
            ->selectRaw('users.id, users.name, COUNT(DISTINCT tickets.id) as total, COUNT(ticket_ratings.id) as rating_count, AVG(ticket_ratings.score) as average_rating')
            ->groupBy('users.id', 'users.name');
        return Inertia::render('admin/reports', [
            'analytics' => [
                'monthly' => $period($monthly)->groupBy('month')->orderByDesc('month')->limit(12)->get(),
                'categories' => $period($categories)->orderByDesc('total')->get(),
                'departments' => $period($departments)->orderByDesc('total')->get(),
                'average_resolution_minutes' => $period($average)->value('average'),
                'agents' => $period($agents)->orderByDesc('total')->get(),
            ],
        ]);
    }

    public function excel(Request $request): Response
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);
        $rows = $this->rows($request);
        $filename = 'laporan-tiket-'.now()->format('Ymd-His').'.xls';
        return response($this->excelDocument($rows), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function pdf(Request $request): Response
    {
        abort_unless(auth()->user()->hasRole('Administrator'), 403);
        $rows = $this->rows($request);
        $period = $request->string('month')->toString() ?: 'Semua periode';
        return response($this->renderPdfDocument($rows, $period), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-tiket-'.now()->format('Ymd-His').'.pdf"',
        ]);
    }

    private function rows(Request $request): array
    {
        return Ticket::query()
            ->with(['category', 'requester.department', 'activeAssignment.assignee'])
            ->when($request->string('month')->toString(), fn ($query, $month) => $query->whereBetween('created_at', [$month.'-01 00:00:00', now()->parse($month.'-01')->endOfMonth()]))
            ->latest()
            ->get()
            ->map(fn (Ticket $ticket) => [
                $ticket->ticket_number,
                $ticket->created_at?->format('Y-m-d H:i'),
                $ticket->category?->name ?? '-',
                $ticket->requester?->department?->name ?? '-',
                $this->statusLabel($ticket->status),
                $ticket->activeAssignment?->assignee?->name ?? '-',
                $ticket->resolved_at ? $ticket->resolved_at->diffInMinutes($ticket->created_at).' menit' : '-',
            ])->all();
    }

    private function statusLabel(string $status): string
    {
        return [
            'open' => 'Terbuka',
            'assigned' => 'Ditugaskan',
            'in_progress' => 'Sedang ditangani',
            'pending_user' => 'Menunggu pelapor',
            'on_hold' => 'Ditunda',
            'resolved' => 'Selesai ditangani',
            'closed' => 'Ditutup',
            'reopened' => 'Dibuka kembali',
            'pending' => 'Menunggu',
        ][$status] ?? $status;
    }

    private function renderPdfDocument(array $rows, string $period): string
    {
        $logo = base64_encode(file_get_contents(public_path('images/erlass-logo.png')));
        $bodyRows = collect($rows)->map(function (array $row): string {
            $cells = collect($row)->map(fn ($value) => '<td>'.e((string) $value).'</td>')->implode('');

            return '<tr>'.$cells.'</tr>';
        })->implode('');
        $statusCount = collect($rows)->countBy(fn (array $row) => $row[4]);
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>
            @page { margin: 18mm 14mm 18mm 14mm; }
            body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 9.5px; }
            .header { position: fixed; top: -13mm; left: 0; right: 0; height: 27mm; border-bottom: 3px solid #2563eb; background: #ffffff; }
            .brand { float: left; width: 38mm; } .brand img { width: 36mm; }
            .title { float: left; margin-left: 4mm; padding-top: 3mm; } .title h1 { margin: 0; color: #183b69; font-size: 17px; } .title p { margin: 2mm 0 0; color: #64748b; }
            .meta { float: right; text-align: right; color: #64748b; padding-top: 4mm; }
            .summary { margin-top: 22mm; } h2 { color: #183b69; font-size: 13px; margin: 0 0 4mm; }
            .cards { width: 100%; border-collapse: collapse; table-layout: fixed; } .cards td { width: 25%; padding: 3mm; border: 1px solid #dbe7f3; background: #f7faff; }
            .cards strong { display: block; margin-top: 1mm; color: #2563eb; font-size: 15px; }
            .period { margin: 5mm 0; padding: 3mm 4mm; border-left: 3px solid #2563eb; background: #eef5ff; }
            table.data { width: 100%; border-collapse: collapse; } table.data th { padding: 2.5mm 1.8mm; background: #183b69; color: #fff; text-align: left; }
            table.data td { border: 1px solid #dbe7f3; padding: 2.2mm 1.8mm; vertical-align: top; } table.data tr:nth-child(even) td { background: #f7faff; }
            .footer { position: fixed; bottom: -13mm; left: 0; right: 0; height: 9mm; border-top: 1px solid #dbe7f3; color: #64748b; font-size: 8px; padding-top: 2mm; }
            .footer .left { float: left; } .footer .right { float: right; }
        </style></head><body>
            <div class="header">
                <div class="brand"><img src="data:image/png;base64,'.$logo.'"></div>
                <div class="title"><h1>Erlass IT Helpdesk</h1><p>Laporan resmi tiket IT</p></div>
                <div class="meta">Dicetak<br>'.now()->locale('id')->translatedFormat('d M Y, H:i').'</div>
            </div>
            <div class="summary"><h2>Ringkasan Jumlah Tiket</h2><table class="cards"><tr>
                <td>Total tiket<strong>'.count($rows).'</strong></td>
                <td>Terbuka<strong>'.$statusCount->get('Terbuka', 0).'</strong></td>
                <td>Dalam proses<strong>'.($statusCount->get('Sedang ditangani', 0) + $statusCount->get('Ditugaskan', 0)).'</strong></td>
                <td>Selesai<strong>'.$statusCount->get('Selesai ditangani', 0).'</strong></td>
            </tr></table></div>
            <div class="period"><strong>Periode laporan:</strong> '.e($period).'</div>
            <table class="data"><thead><tr><th>Nomor</th><th>Dibuat</th><th>Kategori</th><th>Departemen</th><th>Status</th><th>Petugas</th><th>Waktu</th></tr></thead><tbody>'.$bodyRows.'</tbody></table>
            <div class="footer"><span class="left">PT Erlass Prokreatif Indonesia</span><span class="right">Laporan internal Petugas IT</span></div>
        </body></html>';
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    private function excelDocument(array $rows): string
    {
        $headers = ['Nomor', 'Dibuat', 'Kategori', 'Departemen', 'Status', 'Petugas', 'Waktu Penyelesaian'];
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:Arial}h1{color:#1e3a8a}table{border-collapse:collapse;width:100%}th{background:#1e3a8a;color:#fff}th,td{border:1px solid #cbd5e1;padding:7px;text-align:left}</style></head><body><h1>LAPORAN TIKET IT HELP DESK</h1><table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>'.htmlspecialchars($header, ENT_QUOTES, 'UTF-8').'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>'.implode('', array_map(fn ($value) => '<td>'.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'</td>', $row)).'</tr>';
        }
        return $html.'</tbody></table></body></html>';
    }

    private function pdfDocument(array $rows, string $period): string
    {
        $escape = static fn (string $text): string => str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            str_replace(["\r", "\n"], ' ', substr($text, 0, 40)),
        );
        $headers = ['Nomor', 'Dibuat', 'Kategori', 'Dept.', 'Status', 'Petugas', 'Waktu'];
        $chunks = array_chunk($rows, 12);
        if ($chunks === []) {
            $chunks = [[]];
        }
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];
        $pageReferences = [];
        $totalPages = count($chunks);

        foreach ($chunks as $pageIndex => $pageRows) {
            $content = "q\n";
            $content .= "0.04 0.18 0.38 rg 0 742 612 50 re f\n";
            $content .= "0.10 0.42 0.82 rg 0 728 612 14 re f\n";
            $content .= "Q\nBT\n";
            $content .= "/F2 18 Tf 38 760 Td (ERLASS IT HELPDESK) Tj\n";
            $content .= "/F1 9 Tf 38 747 Td (Laporan Resmi Tiket IT) Tj\n";
            $content .= "/F1 9 Tf 420 760 Td (Periode: ".$escape($period).") Tj\n";
            $content .= "ET\n";

            $content .= "q\n0.90 0.94 1.00 rg 38 690 536 34 re f\n";
            $x = [38, 112, 204, 292, 350, 420, 500];
            $widths = [74, 92, 88, 58, 70, 80, 74];
            $content .= "0.04 0.18 0.38 rg\n";
            foreach ($headers as $index => $header) {
                $content .= "BT\n/F2 7.5 Tf ".$x[$index]." 704 Td (".$escape($header).") Tj\nET\n";
            }
            $content .= "Q\n";

            $y = 675;
            foreach ($pageRows as $rowIndex => $row) {
                $fill = $rowIndex % 2 === 0 ? '0.98 0.99 1.00 rg' : '0.94 0.97 1.00 rg';
                $content .= "q\n{$fill} 38 ".($y - 12)." 536 28 re f\nQ\n";
                foreach ($row as $columnIndex => $value) {
                    if ($columnIndex > 6) {
                        break;
                    }
                    $content .= "BT\n/F1 6.5 Tf ".$x[$columnIndex]." ".($y - 2)." Td (".$escape((string) $value).") Tj\nET\n";
                }
                $y -= 28;
            }
            if ($pageRows === []) {
                $content .= "BT\n/F1 10 Tf 38 650 Td (Tidak ada tiket pada periode ini.) Tj\nET\n";
            }

            $content .= "q\n0.04 0.18 0.38 rg 0 18 612 24 re f Q\n";
            $content .= "BT\n/F1 7 Tf 38 27 Td (PT Erlass Prokreatif Indonesia) Tj\n";
            $content .= "/F1 7 Tf 470 27 Td (Halaman ".($pageIndex + 1)." / ".$totalPages.") Tj\nET\n";

            $contentIndex = count($objects) + 1;
            $objects[] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
            $pageIndexObject = count($objects) + 1;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentIndex.' 0 R >>';
            $pageReferences[] = $pageIndexObject.' 0 R';
        }

        $objects[1] = '<< /Type /Pages /Kids ['.implode(' ', $pageReferences).'] /Count '.count($pageReferences).' >>';
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $objectIndex => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($objectIndex + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }
}
