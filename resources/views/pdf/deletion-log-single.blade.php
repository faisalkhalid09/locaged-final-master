<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ui_t('pdf.deletion_log.title') }} - {{ $doc?->title ?? ui_t('pdf.deletion_log.document') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #dc3545;
        }
        .header h1 {
            color: #dc3545;
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        .header .subtitle {
            color: #666;
            font-size: 12px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .info-table th,
        .info-table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
        }
        .info-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
            width: 20%;
        }
        .info-table td {
            background-color: #fff;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-danger {
            background-color: #f8d7da;
            color: #842029;
        }
        .badge-warning {
            background-color: #fff3cd;
            color: #664d03;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ ui_t('pdf.deletion_log.heading') }}</h1>
        <div class="subtitle">{{ ui_t('pdf.deletion_log.generated_on') }} {{ now()->format('Y-m-d H:i:s') }}</div>
    </div>

    <table class="info-table">
        <tr>
            <th>{{ ui_t('pdf.deletion_log.document_title') }}</th>
            <td>{{ $doc?->title ?? ui_t('pdf.deletion_log.document_deleted') }}</td>
        </tr>
        <tr>
            <th>{{ ui_t('pdf.deletion_log.document_id') }}</th>
            <td>{{ $log->document_id }}</td>
        </tr>
        <tr>
            <th>{{ ui_t('pdf.deletion_log.creation_date') }}</th>
            <td>{{ $doc?->created_at?->format('Y-m-d') ?? '—' }}</td>
        </tr>
        <tr>
            <th>{{ ui_t('pdf.deletion_log.expiration_date') }}</th>
            <td>
                @if($doc?->expire_at)
                    <span class="badge badge-warning">{{ $doc->expire_at->format('Y-m-d') }}</span>
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ ui_t('pdf.deletion_log.deleted_at') }}</th>
            <td>
                <span class="badge badge-danger">{{ $log->occurred_at?->format('Y-m-d H:i:s') ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <th>{{ ui_t('pdf.deletion_log.deleted_by') }}</th>
            <td>{{ $log->user?->full_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>{{ ui_t('pdf.deletion_log.structure') }}</th>
            <td>{{ $structure }}</td>
        </tr>
    </table>

    <div class="footer">
        LocaGed Document Management System
    </div>
</body>
</html>
