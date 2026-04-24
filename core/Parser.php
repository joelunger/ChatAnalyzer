<?php

class ChatParser
{
    private $stats = [
        'total' => 0,
        'users' => [],
        'timeline' => [],
        'hours' => [],
        'weekdays' => [],
        'messages' => [], // Added for visualization
        'words' => 0,
        'letters' => 0,     // New
        'media' => 0,       // New
        'links' => 0,       // New
        'deleted' => 0,     // New
        'avg_length' => 0,
        'active_days' => 0,
        'top_emojis' => [],
        'top_words' => [],
        'optimal_time' => null, // Added
        'top_optimal_times' => [], // Added
        'total_users_count' => 0, // Added
        'most_active_date' => ['date' => null, 'count' => 0], // Added to prevent warning
        'first_date' => null,
        'last_date' => null
    ];

    private $tempEmojis = [];
    private $tempWords = [];
    private $hourlyPresence = []; // [weekday][hour][date] = [sender1 => true, ...]
    private $userLastSeen = []; // [sender] = timestamp
    private $currentMessage = null;
    private $lastMessageTimestamp = null; // Added to prevent notice

    private $stopWords = [
        'die',
        'und',
        'der',
        'zu',
        'in',
        'das',
        'ist',
        'dass',
        'nicht',
        'start',
        'mit',
        'video',
        'bild',
        'omitted',
        'audio',
        'sticker',
        'gif',
        'entfernt',
        'verpasst',
        'anruf',
        'ich',
        'du',
        'die',
        'und',
        'der',
        'zu',
        'in',
        'das',
        'ist',
        'dass',
        'nicht',
        'start',
        'mit',
        'video',
        'bild',
        'omitted',
        'audio',
        'sticker',
        'gif',
        'entfernt',
        'verpasst',
        'anruf',
        'ich',
        'du',
        'er',
        'sie',
        'es',
        'wir',
        'ihr',
        'sie',
        'mir',
        'dir',
        'mich',
        'dich',
        'war',
        'hat',
        'für',
        'auf',
        'eine',
        'ein',
        'von',
        'im',
        'den',
        'dem',
        'aber',
        'auch',
        'als',
        'um',
        'noch',
        'so',
        'wie',
        'hab',
        'ja',
        'nein',
        'mal',
        'schon',
        'wenn',
        'dann',
        'was',
        'da',
        'wo',
        'hast',
        'bin',
        'bist',
        'sind',
        'man',
        'aus',
        'oder',
        'nur',
        'kann',
        'einer',
        'einem',
        'einen',
        'eines',
        'einer',
        'dieser',
        'diese',
        'dieses',
        'diesen',
        'diesem',
        'solche',
        'solcher',
        'solches',
        'meine',
        'mein',
        'meiner',
        'meinem',
        'meinen',
        'deine',
        'dein',
        'deiner',
        'deinem',
        'deinen',
        'ihre',
        'ihr',
        'ihrer',
        'ihrem',
        'ihren',
        'seine',
        'sein',
        'seiner',
        'seinem',
        'seinen',
        'unser',
        'unsere',
        'unserer',
        'unserem',
        'unseren',
        'euer',
        'eure',
        'eurer',
        'eurem',
        'euren',
        'alle',
        'alles',
        'allen',
        'allem',
        'jeder',
        'jedes',
        'jedem',
        'jeden',
        'viele',
        'viel',
        'beide',
        'zwei',
        'drei',
        'vier',
        'fünf',
        'sechs',
        'sieben',
        'acht',
        'neun',
        'zehn',
        'elf',
        'zwölf',
        'zwanzig',
        'dreißig',
        'hundert',
        'tausend',
        'einfach',
        'immer',
        'ganz',
        'etwas',
        'nichts'
    ];

    public function parse($filePath)
    {
        $handle = fopen($filePath, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $this->analyzeLine($line);
            }
            if ($this->currentMessage) {
                $this->processCurrentMessage();
            }
            fclose($handle);
        }

        $this->finalizeStats();
        return $this->stats;
    }

    private function analyzeLine($line)
    {
        $line = trim($line);
        if (empty($line))
            return;

        $isNewMessage = false;
        $date = null;
        $time = null;
        $sender = null;
        $message = null;

        // Try Android (Relaxed Regex: \d{1,2} for flexible digits)
        // Matches: 27.1.20, 9:30 - User: Message OR 27.01.2020, 15:30 ...
        // Try Android
        if (preg_match('/^(\d{1,2}\.\d{1,2}\.\d{2,4}), (\d{1,2}:\d{2})\s?-\s?(.*?):\s?(.*)$/u', $line, $matches)) {
            $isNewMessage = true;
            $date = $matches[1];
            $time = $matches[2];
            $sender = $matches[3];
            $message = $matches[4];
        }
        // Try iOS
        elseif (preg_match('/^\[(\d{1,2}\.\d{1,2}\.\d{2,4}),\s(\d{1,2}:\d{2}:\d{2})\]\s(.*?):\s(.*)$/u', $line, $matches)) {
            $isNewMessage = true;
            $date = $matches[1];
            $time = $matches[2];
            $sender = $matches[3];
            $message = $matches[4];
        }
        // WhatsApp Web / Admin messages / System messages
        elseif (preg_match('/^(\d{1,2}\.\d{1,2}\.\d{2,4}), (\d{1,2}:\d{2}) - (.*)$/u', $line, $matches)) {
            $isNewMessage = true;
            $date = $matches[1];
            $time = $matches[2];
            $sender = null; // System message
            $message = $matches[3];
        }

        if ($isNewMessage) {
            if ($this->currentMessage) {
                $this->processCurrentMessage();
            }

            if ($sender) {
                $this->currentMessage = [
                    'date' => $date,
                    'time' => $time,
                    'sender' => $sender,
                    'message' => $message
                ];
            } else {
                $this->currentMessage = null;
            }
        } else {
            if ($this->currentMessage) {
                $this->currentMessage['message'] .= "\n" . $line;
            }
        }
    }

    private function processCurrentMessage()
    {
        if (!$this->currentMessage)
            return;

        $data = $this->currentMessage;
        $this->processMessage($data['date'], $data['time'], $data['sender'], $data['message']);
        $this->currentMessage = null;
    }

    private function processMessage($dateStr, $timeStr, $sender, $message)
    {
        // Normalize Date
        $dt = DateTime::createFromFormat('d.m.y H:i', "$dateStr $timeStr");
        if (!$dt)
            $dt = DateTime::createFromFormat('d.m.Y H:i', "$dateStr $timeStr");
        if (!$dt)
            $dt = DateTime::createFromFormat('d.m.y H:i:s', "$dateStr $timeStr");
        if (!$dt)
            $dt = DateTime::createFromFormat('d.m.Y H:i:s', "$dateStr $timeStr");

        if (!$dt)
            return;

        $timestamp = $dt->getTimestamp();
        $isoDate = $dt->format('Y-m-d');
        $hour = $dt->format('H');
        $weekday = $dt->format('w');

        // Increment stats
        $this->stats['total']++;

        if (!isset($this->stats['users'][$sender]))
            $this->stats['users'][$sender] = 0;
        $this->stats['users'][$sender]++;

        if (!isset($this->stats['timeline'][$isoDate]))
            $this->stats['timeline'][$isoDate] = 0;
        $this->stats['timeline'][$isoDate]++;

        if (!isset($this->stats['hours'][$hour]))
            $this->stats['hours'][$hour] = 0;
        $this->stats['hours'][$hour]++;

        if (!isset($this->stats['weekdays'][$weekday]))
            $this->stats['weekdays'][$weekday] = 0;
        $this->stats['weekdays'][$weekday]++;

        // Add message to history
        $this->stats['messages'][] = [
            'date' => $dateStr,
            'time' => $timeStr,
            'sender' => $sender,
            'message' => $message,
            'timestamp' => $timestamp
        ];

        // Track hourly presence for optimal time calculation (Weekday + Hour)
        if (!isset($this->hourlyPresence[$weekday])) $this->hourlyPresence[$weekday] = [];
        if (!isset($this->hourlyPresence[$weekday][$hour])) $this->hourlyPresence[$weekday][$hour] = [];
        if (!isset($this->hourlyPresence[$weekday][$hour][$isoDate])) $this->hourlyPresence[$weekday][$hour][$isoDate] = [];
        $this->hourlyPresence[$weekday][$hour][$isoDate][$sender] = true;

        // Conversation Starters logic (> 4 hours silence)
        if ($this->lastMessageTimestamp !== null) {
            $diff = $timestamp - $this->lastMessageTimestamp;
            if ($diff > 14400) { // 4 hours in seconds
                if (!isset($this->stats['conversation_starters'][$sender]))
                    $this->stats['conversation_starters'][$sender] = 0;
                $this->stats['conversation_starters'][$sender]++;
            }
        }
        $this->lastMessageTimestamp = $timestamp;

        // Content Analysis
        $this->stats['letters'] += mb_strlen($message);

        // Media
        if (
            strpos($message, '<Media omitted>') !== false ||
            strpos($message, '<Medien ausgeschlossen>') !== false ||
            preg_match('/(Bild|Video|Audio|Sticker) (omitted|weggelassen|entfernt)/i', $message)
        ) {
            $this->stats['media']++;
            return;
        }

        // Links
        if (preg_match_all('/https?:\/\/[^\s]+/', $message, $urlMatches)) {
            $this->stats['links'] += count($urlMatches[0]);
        }

        // Deleted
        if (strpos($message, 'deleted') !== false || strpos($message, 'gelöscht') !== false) { // Simplified check
            if (mb_strlen($message) < 50) { // Heuristic to avoid false positives in long texts
                $this->stats['deleted']++;
                return;
            }
        }

        // User Traits
        // Questions
        if (strpos($message, '?') !== false) {
            if (!isset($this->stats['questions'][$sender]))
                $this->stats['questions'][$sender] = 0;
            $this->stats['questions'][$sender]++;
        }

        // Laughs (simple regex)
        if (preg_match('/(haha|yihaa|lol|rofl|xd|😂|🤣)/i', $message)) {
            if (!isset($this->stats['laughs'][$sender]))
                $this->stats['laughs'][$sender] = 0;
            $this->stats['laughs'][$sender]++;
        }


        // Word Count
        $cleanMessage = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $message));
        $words = explode(' ', $cleanMessage);

        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) < 3)
                continue;
            if (in_array($word, $this->stopWords))
                continue;
            if ($word == 'media' || $word == 'omitted')
                continue;

            if (!isset($this->tempWords[$word]))
                $this->tempWords[$word] = 0;
            $this->tempWords[$word]++;
            $this->stats['words']++;
        }

        // Emojis
        preg_match_all('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}]/u', $message, $emojis);
        if (!empty($emojis[0])) {
            foreach ($emojis[0] as $emoji) {
                if (!isset($this->tempEmojis[$emoji]))
                    $this->tempEmojis[$emoji] = 0;
                $this->tempEmojis[$emoji]++;
            }
        }

        // Dates
        if ($this->stats['first_date'] === null)
            $this->stats['first_date'] = $timestamp;
        $this->stats['last_date'] = $timestamp;
        $this->userLastSeen[$sender] = $timestamp;
    }

    private function finalizeStats()
    {
        // Avg Length
        if ($this->stats['total'] > 0) {
            $this->stats['avg_length'] = round($this->stats['words'] / $this->stats['total'], 1);
        }

        // Active Days & Streak
        ksort($this->stats['timeline']);
        $this->stats['active_days'] = count($this->stats['timeline']);

        // Streak Logic
        $dates = array_keys($this->stats['timeline']);
        $maxStreak = 0;
        $currentStreak = 0;
        $prevDate = null;

        foreach ($dates as $dateStr) {
            $dt = new DateTime($dateStr);
            if ($prevDate === null) {
                $currentStreak = 1;
            } else {
                $diff = $prevDate->diff($dt);
                if ($diff->days == 1) {
                    $currentStreak++;
                } else {
                    $currentStreak = 1; // Reset
                }
            }
            if ($currentStreak > $maxStreak)
                $maxStreak = $currentStreak;
            $prevDate = $dt;

            // Track Max Date
            if ($this->stats['timeline'][$dateStr] > $this->stats['most_active_date']['count']) {
                $this->stats['most_active_date'] = [
                    'date' => $dateStr,
                    'count' => $this->stats['timeline'][$dateStr]
                ];
            }
        }
        $this->stats['longest_streak'] = $maxStreak;

        // Top Lists
        arsort($this->tempEmojis);
        $this->stats['top_emojis'] = array_slice($this->tempEmojis, 0, 5);

        arsort($this->tempWords);
        $this->stats['top_words'] = array_slice($this->tempWords, 0, 30);

        // Optimal Time Calculation (Day + Hour)
        $timeScores = [];
        $days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
        
        // Define "Active Group Size" - Users seen in the last 90 days of the chat
        $lastDate = $this->stats['last_date'];
        $activeUsers90d = 0;
        foreach ($this->userLastSeen as $user => $timestamp) {
            if (($lastDate - $timestamp) < (90 * 86400)) {
                $activeUsers90d++;
            }
        }
        // Fallback to total if group is very small or chat is short
        $activeGroupSize = max($activeUsers90d, 1);
        $totalUsers = count($this->stats['users']);

        foreach ($this->hourlyPresence as $wday => $hours) {
            foreach ($hours as $hour => $dates) {
                $totalParticipants = 0;
                foreach ($dates as $date => $participants) {
                    $totalParticipants += count($participants);
                }
                
                // Average participants per hour on this specific weekday
                $avgParticipants = $totalParticipants / count($dates);
                
                // Reach % logic - now based on active group size for realistic prognosis
                $reachPct = ($activeGroupSize > 0) ? ($avgParticipants / $activeGroupSize) * 100 : 0;
                
                // Interaction prognosis: More precise.
                // We assume interactions scale with unique participants but saturate at some point.
                // Factor in the message density if available (messages per user in that hour)
                $interactionPct = $reachPct * 0.75 + (min(25, $avgParticipants * 2)); 

                $timeScores[] = [
                    'day_name' => $days[$wday],
                    'day_index' => $wday,
                    'hour' => $hour,
                    'score' => round($avgParticipants, 1),
                    'reach_pct' => round(min(100, $reachPct), 1),
                    'interaction_pct' => round(min(100, $interactionPct), 1),
                    'raw_total_users' => $totalUsers,
                    'active_group_size' => $activeGroupSize
                ];
            }
        }

        if (!empty($timeScores)) {
            // Sort by score descending
            usort($timeScores, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            $this->stats['optimal_time'] = $timeScores[0];
            $this->stats['top_optimal_times'] = array_slice($timeScores, 0, 5);
        }
        $this->stats['total_users_count'] = $totalUsers;

        arsort($this->stats['users']);
    }
}
