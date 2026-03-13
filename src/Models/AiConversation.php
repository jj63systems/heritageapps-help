<?php

namespace HeritageApps\Help\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversation extends Model
{
    protected $fillable = [
        'user_id',
        'messages',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \App\Models\User::class)
        );
    }

    public function getMessageCountAttribute(): int
    {
        return count($this->messages ?? []);
    }

    public function getSummaryAttribute(): string
    {
        if (empty($this->messages)) {
            return 'Empty conversation';
        }

        $firstMessage = $this->messages[0]['content'] ?? 'No content';

        return strlen($firstMessage) > 50
            ? substr($firstMessage, 0, 50) . '...'
            : $firstMessage;
    }
}
