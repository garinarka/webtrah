<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil {{ $person->display_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1f2937;
            background: #f9fafb;
            padding: 0;
        }

        .page {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
        }

        /* header */
        .header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            padding: 36px 40px;
            color: white;
        }

        .header-top {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
        }

        .header h1 {
            font-size: 26px;
            font-weight: 700;
        }

        .header .subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.75);
            margin-top: 4px;
        }

        .badges {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .badge.deceased {
            background: rgba(0, 0, 0, 0.2);
        }

        .meta-bar {
            display: flex;
            gap: 24px;
            padding: 12px 40px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }

        .meta-bar span strong {
            color: #334155;
        }

        /* content */
        .content {
            padding: 32px 40px;
        }

        .section {
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #9ca3af;
            margin-bottom: 14px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 6px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .detail-item dt {
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 3px;
        }

        .detail-item dd {
            font-size: 14px;
            color: #1f2937;
            font-weight: 500;
        }

        .detail-item dd.empty {
            color: #d1d5db;
            font-style: italic;
            font-weight: 400;
        }

        /* relationships */
        .rel-group {
            margin-bottom: 16px;
        }

        .rel-group-label {
            font-size: 11px;
            font-weight: 700;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 8px;
        }

        .rel-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .rel-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .rel-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .rel-dot.male {
            background: #60a5fa;
        }

        .rel-dot.female {
            background: #f472b6;
        }

        .rel-dot.unknown {
            background: #d1d5db;
        }

        .rel-name {
            font-size: 13px;
            font-weight: 500;
            color: #374151;
        }

        .empty-rel {
            font-size: 13px;
            color: #d1d5db;
            font-style: italic;
        }

        /* footer */
        .footer {
            margin-top: 40px;
            padding: 16px 40px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #9ca3af;
        }

        @media print {
            body {
                background: white;
            }

            .page {
                max-width: 100%;
            }

            .print-btn {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="page">

        <!-- header -->
        <div class="header">
            <div class="header-top">
                <div class="avatar">{{ mb_strtoupper(mb_substr($person->display_name, 0, 1)) }}</div>
                <div>
                    <h1>{{ $person->display_name }}</h1>
                    <p class="subtitle">
                        {{ $person->familyUnit?->name ?? 'Unit Keluarga Tidak Diketahui' }}
                    </p>
                    <div class="badges">
                        <span class="badge">
                            {{ match($person->gender) { 'male' => 'Laki-laki', 'female' => 'Perempuan', default => 'Gender Tidak Diketahui' } }}
                        </span>
                        <span class="badge">{{ ucfirst($person->status) }}</span>
                        @if($person->death_date)
                        <span class="badge deceased">† Almarhum/Almarhumah</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- meta bar -->
        <div class="meta-bar">
            <span>Dicetak: <strong>{{ now()->translatedFormat('d F Y') }}</strong></span>
            <span>Dibuat oleh: <strong>{{ $person->creator?->name ?? 'System' }}</strong></span>
            <span>ID: <strong style="font-family:monospace">{{ substr($person->id, 0, 8) }}…</strong></span>
        </div>

        <div class="content">

            <!-- data diri -->
            <div class="section">
                <p class="section-title">Data Diri</p>
                <dl class="detail-grid">
                    <div class="detail-item">
                        <dt>Tanggal Lahir</dt>
                        <dd>
                            @php
                            $bd = $person->birth_date;
                            $ba = $person->birth_accuracy;
                            $birthStr = match(true) {
                            !$bd => null,
                            $ba === 'year' => $bd->format('Y'),
                            $ba === 'year_month' => $bd->translatedFormat('F Y'),
                            default => $bd->translatedFormat('d F Y'),
                            };
                            @endphp
                            @if($birthStr)
                            {{ $birthStr }}
                            @if($ba !== 'exact' && $ba !== 'unknown') <span style="color:#9ca3af;font-size:12px">(perkiraan)</span> @endif
                            @else
                            <span class="empty">Tidak diketahui</span>
                            @endif
                        </dd>
                    </div>

                    @if($person->death_date)
                    <div class="detail-item">
                        <dt>Tanggal Meninggal</dt>
                        <dd>
                            @php
                            $dd = $person->death_date;
                            $da = $person->death_accuracy;
                            $deathStr = match(true) {
                            !$dd => null,
                            $da === 'year' => $dd->format('Y'),
                            $da === 'year_month' => $dd->translatedFormat('F Y'),
                            default => $dd->translatedFormat('d F Y'),
                            };
                            @endphp
                            @if($deathStr) {{ $deathStr }} @else <span class="empty">Tidak diketahui</span> @endif
                        </dd>
                    </div>
                    @endif

                    <div class="detail-item">
                        <dt>Jenis Kelamin</dt>
                        <dd>{{ match($person->gender) { 'male' => 'Laki-laki', 'female' => 'Perempuan', default => 'Tidak Diketahui' } }}</dd>
                    </div>

                    <div class="detail-item">
                        <dt>Unit Keluarga</dt>
                        <dd>{{ $person->familyUnit?->name ?? '—' }}</dd>
                    </div>

                    <div class="detail-item">
                        <dt>Status Data</dt>
                        <dd>{{ ucfirst($person->status) }}</dd>
                    </div>

                    <div class="detail-item">
                        <dt>Tanggal Dicatat</dt>
                        <dd>{{ $person->created_at->translatedFormat('d F Y') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- relasi keluarga -->
            <div class="section">
                <p class="section-title">Relasi Keluarga</p>

                @php
                $parents = $relationships->filter(fn($r) => in_array($r->type, ['parent','step_parent','adopted_parent']) && $r->object_id === $person->id);
                $children = $relationships->filter(fn($r) => in_array($r->type, ['parent','step_parent','adopted_parent']) && $r->subject_id === $person->id);
                $spouses = $relationships->filter(fn($r) => $r->type === 'spouse');
                $typeMap = ['parent' => 'Orang Tua', 'step_parent' => 'Orang Tua Tiri', 'adopted_parent' => 'Orang Tua Angkat'];
                @endphp

                <!-- orang Tua -->
                <div class="rel-group">
                    <p class="rel-group-label">Orang Tua</p>
                    @if($parents->isEmpty())
                    <p class="empty-rel">Tidak ada data orang tua</p>
                    @else
                    <div class="rel-list">
                        @foreach($parents as $r)
                        @php $p2 = $r->subject; @endphp
                        <div class="rel-card">
                            <div class="rel-dot {{ $p2?->gender ?? 'unknown' }}"></div>
                            <span class="rel-name">{{ $p2?->display_name ?? '—' }}</span>
                            @if($r->type !== 'parent')
                            <span style="font-size:11px;color:#9ca3af">({{ $typeMap[$r->type] }})</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- pasangan -->
                <div class="rel-group">
                    <p class="rel-group-label">Pasangan</p>
                    @if($spouses->isEmpty())
                    <p class="empty-rel">Tidak ada data pasangan</p>
                    @else
                    <div class="rel-list">
                        @foreach($spouses as $r)
                        @php $p2 = $r->subject_id === $person->id ? $r->object : $r->subject; @endphp
                        <div class="rel-card">
                            <div class="rel-dot {{ $p2?->gender ?? 'unknown' }}"></div>
                            <span class="rel-name">{{ $p2?->display_name ?? '—' }}</span>
                            @if($r->started_at)
                            <span style="font-size:11px;color:#9ca3af">m. {{ $r->started_at->format('Y') }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- anak -->
                <div class="rel-group">
                    <p class="rel-group-label">Anak</p>
                    @if($children->isEmpty())
                    <p class="empty-rel">Tidak ada data anak</p>
                    @else
                    <div class="rel-list">
                        @foreach($children as $r)
                        @php $p2 = $r->object; @endphp
                        <div class="rel-card">
                            <div class="rel-dot {{ $p2?->gender ?? 'unknown' }}"></div>
                            <span class="rel-name">{{ $p2?->display_name ?? '—' }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- footer -->
        <div class="footer">
            <span>Dokumen ini dibuat otomatis oleh sistem Webtrah</span>
            <span>{{ now()->format('d/m/Y H:i') }}</span>
        </div>

    </div>

    <div style="position:fixed;bottom:20px;right:20px;z-index:999" class="print-btn">
        <button onclick="window.print()" style="
        background:#4f46e5;color:white;border:none;
        padding:10px 20px;border-radius:8px;font-size:14px;
        cursor:pointer;font-weight:600;box-shadow:0 4px 12px rgba(79,70,229,0.3);
    ">🖨 Cetak / Simpan PDF</button>
    </div>
</body>

</html>