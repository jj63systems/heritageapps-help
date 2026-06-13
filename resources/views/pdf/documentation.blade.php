<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #1e293b; margin: 0; padding: 0; }
        .header { background: #4f46e5; color: white; padding: 24px 32px; }
        .header h1 { margin: 0 0 4px; font-size: 20px; font-weight: 700; }
        .header p { margin: 0; font-size: 11px; opacity: 0.75; }
        .content { padding: 28px 32px; }

        h2 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 24px 0 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; }
        h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin: 18px 0 6px; }
        h4 { font-size: 13px; font-weight: 700; color: #475569; margin: 14px 0 4px; }
        p { margin: 0 0 10px; line-height: 1.6; }
        ul, ol { margin: 0 0 10px 0; padding-left: 20px; }
        li { margin-bottom: 4px; line-height: 1.5; }

        table { border-collapse: collapse; width: 100%; margin: 12px 0; font-size: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 7px 10px; text-align: left; }
        th { background: #f8fafc; font-weight: 600; color: #374151; }
        tr:nth-child(even) td { background: #f8fafc; }

        code { background: #f1f5f9; padding: 2px 5px; border-radius: 3px; font-size: 11px; font-family: monospace; }
        pre { background: #1e293b; color: #f1f5f9; padding: 12px 16px; border-radius: 6px; font-size: 11px; white-space: pre-wrap; margin: 0 0 12px; }
        pre code { background: none; padding: 0; color: inherit; }

        blockquote { border-left: 3px solid #6366f1; margin: 12px 0; padding: 8px 16px; background: #f5f3ff; color: #4338ca; }

        hr { border: none; border-top: 1px solid #e2e8f0; margin: 20px 0; }

        img { max-width: 100%; height: auto; }

        .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; }

        a { color: #4f46e5; text-decoration: none; }

        strong { font-weight: 700; }
        em { font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>CollectorCloud Help &middot; {{ now()->format('d F Y') }}</p>
    </div>

    <div class="content">
        {!! $htmlContent !!}

        <div class="footer">
            Exported from CollectorCloud Help on {{ now()->format('d/m/Y \a\t H:i') }}
        </div>
    </div>
</body>
</html>
