<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

class AiChatDirectSlotPool
{
    public function acquire(): ?Lock
    {
        $slotCount = max(0, min(20, (int) config('ai.chatbot.direct_slots', 4)));
        $lockSeconds = max(5, (int) config('ai.chatbot.direct_timeout', 12) + 5);

        for ($slot = 1; $slot <= $slotCount; $slot++) {
            $lock = Cache::lock("ai_chat_direct_slot:{$slot}", $lockSeconds);

            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }
}
