<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Person;
use App\Models\Relationship;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_people' => Person::where('status', 'active')->count(),
            'total_relations' => Relationship::where('status', 'approved')->whereNull('ended_at')->count(),
            'total_users' => User::count(),
            'pending_approvals' => Approval::where('status', 'pending')->count(),
        ];

        return Inertia::render('Admin/Export/Index', compact('stats'));
    }

    // CSV: DAFTAR ANGGOTA

    public function exportPeopleCSV(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:active,pending,draft,all',
        ]);

        $status = $request->get('status', 'active');

        $query = Person::with(['familyUnit:id,name', 'creator:id,name'])
            ->orderBy('display_name');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $people = $query->get();

        $filename = 'anggota-keluarga-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($people) {
            $handle = fopen('php://output', 'w');

            // bom untuk excel utf-8
            fwrite($handle, "\xEF\xBB\xBF");

            // header row
            fputcsv($handle, [
                'ID',
                'Nama Lengkap',
                'Jenis Kelamin',
                'Status',
                'Tanggal Lahir',
                'Akurasi Lahir',
                'Tanggal Meninggal',
                'Akurasi Meninggal',
                'Unit Keluarga',
                'Dibuat Oleh',
                'Tanggal Input',
            ]);

            foreach ($people as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->display_name,
                    match ($p->gender) {
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                        default => 'Tidak Diketahui'
                    },
                    $p->status,
                    $p->birth_date?->format('Y-m-d') ?? '',
                    $p->birth_accuracy,
                    $p->death_date?->format('Y-m-d') ?? '',
                    $p->death_accuracy,
                    $p->familyUnit?->name ?? '',
                    $p->creator?->name ?? '',
                    $p->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // CSV: DAFTAR RELASI

    public function exportRelationsCSV()
    {
        $relations = Relationship::with(['subject:id,display_name', 'object:id,display_name'])
            ->where('status', 'approved')
            ->whereNull('ended_at')
            ->orderBy('type')
            ->get();

        $filename = 'relasi-keluarga-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($relations) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Pihak 1 (Subject)',
                'Jenis Relasi',
                'Pihak 2 (Object)',
                'Biologis',
                'Tanggal Mulai',
                'Tanggal Berakhir',
                'Alasan Berakhir',
                'Status',
            ]);

            $typeLabels = [
                'parent' => 'Orang Tua',
                'step_parent' => 'Orang Tua Tiri',
                'adopted_parent' => 'Orang Tua Angkat',
                'spouse' => 'Pasangan',
            ];

            foreach ($relations as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->subject?->display_name ?? $r->subject_id,
                    $typeLabels[$r->type] ?? $r->type,
                    $r->object?->display_name ?? $r->object_id,
                    $r->is_biological ? 'Ya' : 'Tidak',
                    $r->started_at?->format('Y-m-d') ?? '',
                    $r->ended_at?->format('Y-m-d') ?? '',
                    $r->ended_reason ?? '',
                    $r->status,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // PDF: PROFIL ANGGOTA

    public function exportPersonPDF(Request $request, string $personId)
    {
        $person = Person::with([
            'familyUnit',
            'creator:id,name',
        ])->findOrFail($personId);

        // load relasi
        $relationships = Relationship::with(['subject:id,display_name,gender', 'object:id,display_name,gender'])
            ->where(fn ($q) => $q->where('subject_id', $personId)->orWhere('object_id', $personId))
            ->where('status', 'approved')
            ->whereNull('ended_at')
            ->get();

        $html = view('exports.person-pdf', compact('person', 'relationships'))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.str_replace(' ', '-', $person->display_name).'.html"',
        ]);
    }

    // CSV: RINGKASAN STATISTIK

    public function exportStatisticsCSV()
    {
        $filename = 'statistik-'.now()->format('Y-m-d').'.csv';

        // hitung statistik per bulan 12 bulan terakhir
        $monthly = Person::selectRaw("DATE_TRUNC('month', created_at) as month, COUNT(*) as total")
            ->where('created_at', '>=', now()->subYear())
            ->groupByRaw("DATE_TRUNC('month', created_at)")
            ->orderBy('month')
            ->get();

        $byGender = Person::where('status', 'active')
            ->selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->get()
            ->keyBy('gender');

        $byGeneration = Person::where('status', 'active')
            ->whereNotNull('birth_date')
            ->selectRaw('FLOOR(EXTRACT(YEAR FROM birth_date) / 10) * 10 as decade, COUNT(*) as total')
            ->groupByRaw('FLOOR(EXTRACT(YEAR FROM birth_date) / 10) * 10')
            ->orderBy('decade')
            ->get();

        return response()->streamDownload(function () use ($monthly, $byGender, $byGeneration) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            // section: ringkasan
            fputcsv($handle, ['RINGKASAN STATISTIK KELUARGA']);
            fputcsv($handle, ['Dibuat pada:', now()->format('d/m/Y H:i')]);
            fputcsv($handle, []);

            // section: per bulan
            fputcsv($handle, ['DATA INPUT PER BULAN (12 BULAN TERAKHIR)']);
            fputcsv($handle, ['Bulan', 'Jumlah Input']);
            foreach ($monthly as $m) {
                fputcsv($handle, [
                    \Carbon\Carbon::parse($m->month)->translatedFormat('F Y'),
                    $m->total,
                ]);
            }
            fputcsv($handle, []);

            // section: per gender
            fputcsv($handle, ['DISTRIBUSI JENIS KELAMIN (ANGGOTA AKTIF)']);
            fputcsv($handle, ['Jenis Kelamin', 'Jumlah']);
            fputcsv($handle, ['Laki-laki',     $byGender['male']?->total ?? 0]);
            fputcsv($handle, ['Perempuan',     $byGender['female']?->total ?? 0]);
            fputcsv($handle, ['Tidak Diketahui', $byGender['unknown']?->total ?? 0]);
            fputcsv($handle, []);

            // section: per dekade
            fputcsv($handle, ['DISTRIBUSI TAHUN LAHIR PER DEKADE']);
            fputcsv($handle, ['Dekade', 'Jumlah']);
            foreach ($byGeneration as $g) {
                fputcsv($handle, ["{$g->decade}-an", $g->total]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
    // XLSX: DAFTAR ANGGOTA
    // Menggunakan SpreadsheetML (Office Open XML) murni via ZipArchive bawaan PHP.
    // Tidak memerlukan package Composer tambahan.

    public function exportPeopleXLSX(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:active,pending,draft,all',
        ]);

        $status = $request->get('status', 'active');

        $query = Person::with(['familyUnit:id,name', 'creator:id,name'])
            ->orderBy('display_name');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $people = $query->get();
        $filename = 'anggota-keluarga-'.now()->format('Y-m-d').'.xlsx';

        // Bangun konten XML untuk sheet
        $rows = [];
        $rows[] = ['ID', 'Nama Lengkap', 'Jenis Kelamin', 'Status',
            'Tanggal Lahir', 'Tanggal Meninggal', 'Unit Keluarga',
            'Dibuat Oleh', 'Tanggal Input'];

        foreach ($people as $p) {
            $rows[] = [
                (string) $p->id,
                $p->display_name,
                match ($p->gender) {
                    'male' => 'Laki-laki',
                    'female' => 'Perempuan',
                    default => 'Tidak Diketahui',
                },
                $p->status,
                $p->birth_date?->format('Y-m-d') ?? '',
                $p->death_date?->format('Y-m-d') ?? '',
                $p->familyUnit?->name ?? '',
                $p->creator?->name ?? '',
                $p->created_at->format('Y-m-d H:i'),
            ];
        }

        $xlsxContent = $this->buildXlsx($rows);

        return response($xlsxContent)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->header('Content-Length', strlen($xlsxContent));
    }

    /**
     * Bangun file XLSX sederhana menggunakan SpreadsheetML + ZipArchive.
     * Tidak perlu library eksternal — hanya PHP built-in.
     */
    private function buildXlsx(array $rows): string
    {
        // Escape XML entities
        $esc = fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        // Bangun sheet XML
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>';

        foreach ($rows as $rowIdx => $row) {
            $sheetXml .= '<row r="'.($rowIdx + 1).'">';
            foreach ($row as $colIdx => $cell) {
                $colLetter = $this->colLetter($colIdx);
                $cellRef = $colLetter.($rowIdx + 1);
                $sheetXml .= '<c r="'.$cellRef.'" t="inlineStr">'
                    .'<is><t>'.$esc((string) $cell).'</t></is></c>';
            }
            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData></worksheet>';

        // File-file wajib dalam paket XLSX (zip)
        $files = [
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',

            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'</Types>',

            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="Anggota" sheetId="1" r:id="rId1"/></sheets>'
                .'</workbook>',

            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'</Relationships>',

            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        // Buat zip di memory
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new \ZipArchive;
        $zip->open($tmpFile, \ZipArchive::OVERWRITE);
        foreach ($files as $name => $xmlContent) {
            $zip->addFromString($name, $xmlContent);
        }
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }

    /**
     * Konversi index kolom (0-based) ke huruf Excel: 0 → A, 25 → Z, 26 → AA
     */
    private function colLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
