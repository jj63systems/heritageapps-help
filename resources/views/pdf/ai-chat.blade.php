<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>AI Assistant Conversation</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #1e293b; margin: 0; padding: 0; }
        .header { background: #4f46e5; color: white; padding: 24px 32px; }
        .header h1 { margin: 0 0 4px; font-size: 20px; }
        .header p { margin: 0; font-size: 12px; opacity: 0.8; }
        .content { padding: 24px 32px; }
        .message { margin-bottom: 16px; }
        .message-user { text-align: right; }
        .message-user .bubble { background: #4f46e5; color: white; display: inline-block; padding: 10px 14px; border-radius: 16px 16px 4px 16px; max-width: 80%; text-align: left; }
        .message-assistant .bubble { background: #f1f5f9; color: #1e293b; display: inline-block; padding: 10px 14px; border-radius: 16px 16px 16px 4px; max-width: 80%; }
        .role { font-size: 11px; color: #94a3b8; margin-bottom: 4px; }
        .message-user .role { text-align: right; }
        .timestamp { font-size: 11px; color: #94a3b8; margin-top: 4px; }
        .message-user .timestamp { text-align: right; }
        table { border-collapse: collapse; width: 100%; margin: 8px 0; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 10px; text-align: left; font-size: 12px; }
        th { background: #f8fafc; font-weight: 600; }
        code { background: #f1f5f9; padding: 2px 5px; border-radius: 3px; font-size: 11px; }
        pre { background: #1e293b; color: #f1f5f9; padding: 12px; border-radius: 6px; font-size: 11px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AI Assistant Conversation</h1>
        <p>{{ $userName }} &middot; {{ $date }}</p>
    </div>

    <div class="content">
        @foreach ($messages as $message)
            <div class="message message-{{ $message['role'] }}">
                <div class="role">{{ $message['role'] === 'user' ? 'You' : 'AI Assistant' }}</div>
                <div class="bubble">
                    @if ($message['role'] === 'assistant')
                        {!! \Illuminate\Support\Str::markdown($message['content']) !!}
                    @else
                        {{ $message['content'] }}
                    @endif
                </div>
                @if (!empty($message['timestamp']))
                    <div class="timestamp">{{ $message['timestamp'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
</body>
</html>
