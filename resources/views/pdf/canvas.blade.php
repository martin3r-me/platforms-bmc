<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $canvas->name }} - Business Model Canvas</title>
    <style>
        @page {
            margin: 8mm 10mm;
            size: A4 landscape;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            line-height: 1.3;
            color: #1f2937;
        }

        /* ── Font scale tiers ── */
        body.scale-lg { font-size: 8pt; }
        body.scale-md { font-size: 7pt; }
        body.scale-sm { font-size: 6pt; }
        body.scale-xs { font-size: 5pt; }

        .header {
            text-align: center;
            margin-bottom: 3mm;
            padding-bottom: 2mm;
            border-bottom: 0.5pt solid #d1d5db;
        }

        .scale-lg .header h1 { font-size: 14pt; }
        .scale-md .header h1 { font-size: 13pt; }
        .scale-sm .header h1 { font-size: 11pt; }
        .scale-xs .header h1 { font-size: 10pt; }

        .header h1 {
            font-weight: bold;
            color: #111827;
            margin-bottom: 1mm;
        }

        .header .meta {
            font-size: 0.85em;
            color: #6b7280;
        }

        /* ── Canvas table ── */
        .canvas-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .canvas-table td {
            border: 0.5pt solid #d1d5db;
            vertical-align: top;
            padding: 0;
        }

        .block-header {
            background: #f3f4f6;
            padding: 1.5mm 2mm;
            border-bottom: 0.5pt solid #d1d5db;
        }

        .block-header h3 {
            font-size: 1em;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            color: #374151;
        }

        .block-body {
            padding: 1.5mm 2mm;
        }

        .entry {
            margin-bottom: 1mm;
            padding: 0.8mm 1.2mm;
            background: #f9fafb;
            border: 0.3pt solid #e5e7eb;
            border-radius: 0.5mm;
        }

        .entry:last-child {
            margin-bottom: 0;
        }

        .entry-title {
            font-weight: bold;
            color: #1f2937;
        }

        .entry-content {
            font-size: 0.9em;
            color: #6b7280;
            margin-top: 0.2mm;
            word-wrap: break-word;
        }

        .empty-hint {
            color: #9ca3af;
            font-style: italic;
            text-align: center;
            padding: 2mm 0;
        }

        .footer {
            margin-top: 2mm;
            font-size: 0.8em;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body class="scale-{{ $fontScale }}">
    {{-- Header --}}
    <div class="header">
        <h1>{{ $canvas->name }}</h1>
        <div class="meta">
            Business Model Canvas
            @if($canvas->createdByUser) &middot; {{ $canvas->createdByUser->name }} @endif
            &middot; {{ $canvas->created_at?->format('d.m.Y') }}
            @if($canvas->description) &middot; {{ $canvas->description }} @endif
        </div>
    </div>

    {{-- Osterwalder Grid: 5 columns, 3 rows
         KP     | KA     | VP     | CR     | CS
         KP     | KR     | VP     | CH     | CS
         COST   | COST   | COST   | REV    | REV
    --}}

    @php
        $blocks = $canvasData['blocks'] ?? [];

        $getBlock = function($type) use ($blocks, $blockTypes) {
            $block = $blocks[$type] ?? null;
            $config = $blockTypes[$type] ?? [];
            $label = $config['label'] ?? ucfirst(str_replace('_', ' ', $type));
            $entries = $block['entries'] ?? [];
            return ['label' => $label, 'entries' => $entries];
        };

        $kp = $getBlock('key_partners');
        $ka = $getBlock('key_activities');
        $vp = $getBlock('value_propositions');
        $cr = $getBlock('customer_relationships');
        $cs = $getBlock('customer_segments');
        $kr = $getBlock('key_resources');
        $ch = $getBlock('channels');
        $cost = $getBlock('cost_structure');
        $rev = $getBlock('revenue_streams');
    @endphp

    <table class="canvas-table">
        {{-- Row 1: KP | KA | VP | CR | CS --}}
        <tr>
            {{-- Key Partners (spans 2 rows) --}}
            <td rowspan="2" style="width: 20%;">
                <div class="block-header"><h3>{{ $kp['label'] }}</h3></div>
                <div class="block-body">
                    @forelse($kp['entries'] as $entry)
                        <div class="entry">
                            @if(!empty($entry['title']))<div class="entry-title">{{ $entry['title'] }}</div>@endif
                            @if(!empty($entry['content']))<div class="entry-content">{{ $entry['content'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-hint">–</div>
                    @endforelse
                </div>
            </td>

            {{-- Key Activities --}}
            <td style="width: 20%;">
                <div class="block-header"><h3>{{ $ka['label'] }}</h3></div>
                <div class="block-body">
                    @forelse($ka['entries'] as $entry)
                        <div class="entry">
                            @if(!empty($entry['title']))<div class="entry-title">{{ $entry['title'] }}</div>@endif
                            @if(!empty($entry['content']))<div class="entry-content">{{ $entry['content'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-hint">–</div>
                    @endforelse
                </div>
            </td>

            {{-- Value Propositions (spans 2 rows) --}}
            <td rowspan="2" style="width: 20%;">
                <div class="block-header"><h3>{{ $vp['label'] }}</h3></div>
                <div class="block-body">
                    @forelse($vp['entries'] as $entry)
                        <div class="entry">
                            @if(!empty($entry['title']))<div class="entry-title">{{ $entry['title'] }}</div>@endif
                            @if(!empty($entry['content']))<div class="entry-content">{{ $entry['content'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-hint">–</div>
                    @endforelse
                </div>
            </td>

            {{-- Customer Relationships --}}
            <td style="width: 20%;">
                <div class="block-header"><h3>{{ $cr['label'] }}</h3></div>
                <div class="block-body">
                    @forelse($cr['entries'] as $entry)
                        <div class="entry">
                            @if(!empty($entry['title']))<div class="entry-title">{{ $entry['title'] }}</div>@endif
                            @if(!empty($entry['content']))<div class="entry-content">{{ $entry['content'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-hint">–</div>
                    @endforelse
                </div>
            </td>

            {{-- Customer Segments (spans 2 rows) --}}
            <td rowspan="2" style="width: 20%;">
                <div class="block-header"><h3>{{ $cs['label'] }}</h3></div>
                <div class="block-body">
                    @forelse($cs['entries'] as $entry)
                        <div class="entry">
                            @if(!empty($entry['title']))<div class="entry-title">{{ $entry['title'] }}</div>@endif
                            @if(!empty($entry['content']))<div class="entry-content">{{ $entry['content'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-hint">–</div>
                    @endforelse
                </div>
            </td>
        </tr>

        {{-- Row 2: (KP spans) | KR | (VP spans) | CH | (CS spans) --}}
        <tr>
            {{-- Key Resources --}}
            <td>
                <div class="block-header"><h3>{{ $kr['label'] }}</h3></div>
                <div class="block-body">
                    @forelse($kr['entries'] as $entry)
                        <div class="entry">
                            @if(!empty($entry['title']))<div class="entry-title">{{ $entry['title'] }}</div>@endif
                            @if(!empty($entry['content']))<div class="entry-content">{{ $entry['content'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-hint">–</div>
                    @endforelse
                </div>
            </td>

            {{-- Channels --}}
            <td>
                <div class="block-header"><h3>{{ $ch['label'] }}</h3></div>
                <div class="block-body">
                    @forelse($ch['entries'] as $entry)
                        <div class="entry">
                            @if(!empty($entry['title']))<div class="entry-title">{{ $entry['title'] }}</div>@endif
                            @if(!empty($entry['content']))<div class="entry-content">{{ $entry['content'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-hint">–</div>
                    @endforelse
                </div>
            </td>
        </tr>

        {{-- Row 3: Cost Structure (3 cols) | Revenue Streams (2 cols) --}}
        <tr>
            <td colspan="3">
                <div class="block-header"><h3>{{ $cost['label'] }}</h3></div>
                <div class="block-body">
                    @forelse($cost['entries'] as $entry)
                        <div class="entry">
                            @if(!empty($entry['title']))<div class="entry-title">{{ $entry['title'] }}</div>@endif
                            @if(!empty($entry['content']))<div class="entry-content">{{ $entry['content'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-hint">–</div>
                    @endforelse
                </div>
            </td>
            <td colspan="2">
                <div class="block-header"><h3>{{ $rev['label'] }}</h3></div>
                <div class="block-body">
                    @forelse($rev['entries'] as $entry)
                        <div class="entry">
                            @if(!empty($entry['title']))<div class="entry-title">{{ $entry['title'] }}</div>@endif
                            @if(!empty($entry['content']))<div class="entry-content">{{ $entry['content'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-hint">–</div>
                    @endforelse
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        {{ $canvas->name }} &middot; Erstellt am {{ $canvas->created_at?->format('d.m.Y H:i') }}
        @if($canvas->createdByUser) von {{ $canvas->createdByUser->name }} @endif
        &middot; Business Model Canvas
    </div>
</body>
</html>
