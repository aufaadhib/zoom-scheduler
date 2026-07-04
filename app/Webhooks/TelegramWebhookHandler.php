<?php

namespace App\Webhooks;

use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Stringable;
use App\Models\User;
use App\Models\Meeting;
use App\Services\ZoomOAuthService;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use Illuminate\Support\Facades\Log;

class TelegramWebhookHandler extends WebhookHandler
{
    /**
     * Handle the /start command (with optional deep link parameter)
     */
    public function start(?string $parameter = null): void
    {
        if ($parameter) {
            $this->link($parameter);
            return;
        }

        $user = User::find($this->chat->user_id);

        $message = implode("\n", [
            "<b>Selamat datang di Bot RSUD Blambangan!</b>",
            "",
            "Bot ini akan membantu Anda mengelola dan menerima notifikasi jadwal <b>Zoom Meeting</b> secara otomatis langsung di Telegram.",
            "",
            $user ? "Halo <b>{$user->name}</b>! Apa yang ingin Anda lakukan?" : "Silakan hubungkan akun Anda terlebih dahulu melalui Web Dashboard."
        ]);

        $request = $this->chat->html($message);
        
        if ($user) {
            $request = $request->replyKeyboard(ReplyKeyboard::make()->buttons([
                ReplyButton::make('Buat Rapat Instan'),
                ReplyButton::make('Jadwal Baru'),
                ReplyButton::make('Jadwal Hari Ini'),
                ReplyButton::make('Kelola Rapat'),
                ReplyButton::make('Buka Dashboard Web')->webApp(config('app.url')),
            ])->resize());
        } else {
            $request = $request->replyKeyboard(ReplyKeyboard::make()->buttons([
                ReplyButton::make('Buka Dashboard')->webApp(config('app.url'))
            ])->resize());
        }

        $request->send();
    }

    /**
     * Tampilkan menu interaktif bot
     */
    public function menu(): void
    {
        $this->start(); // Reuse the same combined message
    }

    /**
     * Handle the /link <code> command
     */
    public function link(string $code): void
    {
        $user = User::where('telegram_link_code', strtoupper(trim($code)))->first();

        if (!$user) {
            $this->chat->html(
                "<b>Kode tidak valid atau sudah tidak berlaku.</b>\n\n" .
                "Pastikan kode yang Anda masukkan benar. Anda bisa membuat kode baru melalui menu <b>Pengaturan → Integrasi</b> di aplikasi."
            )->send();
            return;
        }

        // Tautkan chat id ke user
        $this->chat->user_id = $user->id;
        $this->chat->save();
        
        $user->telegram_link_code = null;
        $user->save();

        $message = implode("\n", [
            "<b>Akun Berhasil Ditautkan!</b>",
            "",
            "Halo, <b>{$user->name}</b>! ",
            "Akun Zoom Scheduler Anda telah berhasil terhubung ke Telegram ini.",
        ]);

        $this->chat->html($message)->send();
        $this->menu();
    }

    public function createInstantMeeting(): void
    {
        $user = User::find($this->chat->user_id);
        if (!$user) {
            $this->reply("Akun Anda belum terhubung.");
            return;
        }

        $accounts = $user->zoomAccounts()->whereNotNull('access_token')->get();
        if ($accounts->isEmpty()) {
            $this->chat->html("<b>Tidak ada Akun Zoom!</b>\nAnda belum menghubungkan akun Zoom di Dashboard.")->send();
            return;
        }

        $keyboard = Keyboard::make()->row([
            Button::make('Otomatis (Pilih yang kosong)')->action('processInstantMeeting')->param('account_id', 'auto')
        ]);
        
        foreach ($accounts as $account) {
            $keyboard = $keyboard->row([
                Button::make($account->account_name)->action('processInstantMeeting')->param('account_id', $account->id)
            ]);
        }

        $this->chat->html("Pilih Akun Zoom yang ingin digunakan untuk <b>Rapat Instan</b>:")->keyboard($keyboard)->send();
    }

    public function processInstantMeeting(): void
    {
        $accountId = $this->data->get('account_id');
        $user = User::find($this->chat->user_id);
        if (!$user) return;

        $zoomAccount = null;
        if ($accountId === 'auto') {
            // Find non-overlapping account (simple check for active meetings)
            // For instant meetings, we just pick the first available one that doesn't have an active meeting right now
            $busyAccountIds = Meeting::where('start_time', '<=', now()->addMinutes(60))
                ->where('start_time', '>=', now()->subHours(2))
                ->pluck('zoom_account_id')->toArray();
                
            $zoomAccount = $user->zoomAccounts()->whereNotNull('access_token')->whereNotIn('id', $busyAccountIds)->first();
            if (!$zoomAccount) {
                // If all busy, just pick the first one as fallback
                $zoomAccount = $user->zoomAccounts()->whereNotNull('access_token')->first();
            }
        } else {
            $zoomAccount = $user->zoomAccounts()->find($accountId);
        }

        if (!$zoomAccount) {
            $this->chat->html("Akun tidak ditemukan.")->send();
            return;
        }

        $loading = $this->chat->html("<i>Sedang menyiapkan Rapat Instan...</i>")->send();
        $loadingMessageId = $loading->telegraphMessageId();

        try {
            $zoomService = app(ZoomOAuthService::class);

            $response = $zoomService->createMeeting($zoomAccount, [
                'topic' => 'Rapat RSUD Blambangan',
                'type' => 1, // Instant
            ]);

            // Save to DB
            Meeting::create([
                'user_id' => $user->id,
                'zoom_account_id' => $zoomAccount->id,
                'zoom_meeting_id' => (string)$response['id'],
                'topic' => $response['topic'],
                'type' => 1,
                'start_time' => now(),
                'duration' => 60,
                'timezone' => $response['timezone'] ?? 'Asia/Jakarta',
                'join_url' => $response['join_url'],
                'start_url' => $response['start_url'],
                'password' => $response['password'] ?? null,
            ]);

            $inviteText = "Agenda Zoom: {$response['topic']}\n\nGabung Zoom Meeting:\n{$response['join_url']}\n\nMeeting ID: {$response['id']}\nPasscode: " . ($response['password'] ?? 'Tidak ada');

            $message = implode("\n", [
                "<blockquote><b>Rapat Instan Berhasil Dibuat!</b>",
                "Akun: {$zoomAccount->account_name} ({$zoomAccount->email})</blockquote>",
                "",
                "Salin undangan di bawah ini:",
                "<pre><code class=\"language-copy\">{$inviteText}</code></pre>",
                "",
                "<a href='{$response['start_url']}'><b>Klik di sini untuk Memulai (Host) Rapat</b></a>"
            ]);

            $this->chat->html($message)->send();
            if ($loadingMessageId) {
                $this->chat->deleteMessage($loadingMessageId)->send();
            }

        } catch (\Exception $e) {
            if (isset($loadingMessageId)) {
                $this->chat->deleteMessage($loadingMessageId)->send();
            }
            Log::error('Gagal membuat instant meeting via Telegram: ' . $e->getMessage());
            $this->chat->html("<b>Gagal membuat rapat!</b>\nSilakan coba lagi atau cek koneksi Zoom Anda di Dashboard.")->send();
        }
    }

    /**
     * Aksi mengecek jadwal hari ini
     */
    public function checkTodayMeetings(): void
    {
        $user = User::find($this->chat->user_id);
        if (!$user) {
            $this->reply("Akun Anda belum terhubung.");
            return;
        }

        $meetings = Meeting::where('user_id', $user->id)
            ->whereDate('start_time', today())
            ->orderBy('start_time', 'asc')
            ->get();

        if ($meetings->isEmpty()) {
            $this->chat->html("<blockquote><b>Daftar Meeting Hari Ini</b>\nTotal Rapat: 0\n\nTidak ada jadwal meeting untuk hari ini. Waktunya bersantai! </blockquote>")->send();
            return;
        }

        $totalMeetings = $meetings->count();
        $instantCount = $meetings->where('type', 1)->count();
        $scheduledCount = $meetings->where('type', 2)->count();

        $header = implode("\n", [
            "<blockquote><b>Daftar Meeting Hari Ini</b>",
            "Total Rapat: {$totalMeetings}",
            "Rapat Instan: {$instantCount}",
            "Rapat Terjadwal: {$scheduledCount}</blockquote>\n"
        ]);

        // Batas karakter Telegram 4096, kita pecah menjadi maksimal 5 meeting per pesan
        $chunks = $meetings->chunk(5);
        $chunkIndex = 0;

        foreach ($chunks as $chunk) {
            $lines = [];
            if ($chunkIndex === 0) {
                $lines[] = $header;
            }

            $loopIndex = 0;
            foreach ($chunk as $m) {
                $time = $m->type == 1 ? 'Instan' : \Carbon\Carbon::parse($m->start_time)->setTimezone('Asia/Jakarta')->format('H:i') . ' WIB';
                $no = ($chunkIndex * 5) + $loopIndex + 1;
                
                $lines[] = "<b>{$no}. {$m->topic}</b>";
                $lines[] = "Waktu: {$time}";
                $lines[] = "Akun: {$m->zoomAccount->account_name} ({$m->zoomAccount->email})";
                $lines[] = "Link Join:\n<pre><code class=\"language-copy\">{$m->join_url}</code></pre>";
                $lines[] = ""; // Spasi antar meeting
                $loopIndex++;
            }

            $this->chat->html(implode("\n", $lines))->send();
            $chunkIndex++;
        }
    }

    public function startScheduling(): void
    {
        $user = User::find($this->chat->user_id);
        if (!$user) return;

        $accounts = $user->zoomAccounts()->whereNotNull('access_token')->get();
        if ($accounts->isEmpty()) {
            $this->chat->html("<b>Tidak ada Akun Zoom!</b>\nAnda belum menghubungkan akun Zoom di Dashboard.")->send();
            return;
        }

        $keyboard = Keyboard::make()->row([
            Button::make('Otomatis (Cari yang kosong)')->action('askTopic')->param('account_id', 'auto')
        ]);
        
        foreach ($accounts as $account) {
            $keyboard = $keyboard->row([
                Button::make($account->account_name)->action('askTopic')->param('account_id', $account->id)
            ]);
        }

        $this->chat->html("Pilih Akun Zoom yang ingin dijadwalkan:")->keyboard($keyboard)->send();
    }

    public function askTopic(): void
    {
        $accountId = $this->data->get('account_id');
        
        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'WAITING_TOPIC',
            'data' => [
                'account_id' => $accountId,
            ]
        ], now()->addMinutes(15));

        $this->chat->html("Tulis <b>Topik Rapat</b> yang akan dijadwalkan:\n\n<i>Ketik langsung di kolom chat (contoh: Rapat Mingguan)</i>")->send();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $textStr = trim((string) $text);
        
        // Handle ReplyKeyboard Buttons
        if ($textStr === 'Buat Rapat Instan') {
            $this->createInstantMeeting();
            return;
        }
        if ($textStr === 'Jadwal Baru') {
            $this->startScheduling();
            return;
        }
        if ($textStr === 'Jadwal Hari Ini') {
            $this->checkTodayMeetings();
            return;
        }
        if ($textStr === 'Kelola Rapat') {
            $this->manageMeetings();
            return;
        }

        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");

        if (!$stateData) {
            $this->handleUnknownCommand($text);
            return;
        }

        $state = $stateData['state'];
        $data = $stateData['data'];

        if ($state === 'WAITING_TOPIC') {
            $data['topic'] = $textStr;
            $this->askScheduleYear($data);
            return;
        }

        if ($state === 'EDIT_TOPIC') {
            $data['topic'] = (string) $text;
            $this->finalizeEdit($data);
            return;
        }

        if ($state === 'EDIT_TIME') {
            try {
                $parsedTime = \Carbon\Carbon::createFromFormat('d-m-Y H:i', trim((string) $text), 'Asia/Jakarta')->setTimezone('UTC');
                $data['start_time'] = $parsedTime->toIso8601String();
            } catch (\Exception $e) {
                $this->chat->html("Format waktu salah. Silakan ketik ulang dengan format <code>DD-MM-YYYY HH:MM</code> atau gunakan tombol.")->send();
                return;
            }
            $this->askEditDuration($data);
            return;
        }

        if ($state === 'EDIT_DURATION') {
            $val = (int) trim((string)$text);
            if ($val <= 0) {
                $this->chat->html("Masukkan angka durasi yang valid dalam satuan menit.")->send();
                return;
            }
            $data['duration'] = $val;
            $this->finalizeEdit($data);
            return;
        }

        $this->handleUnknownCommand($text);
    }

    public function askScheduleYear(array $data): void
    {
        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'WIZARD_YEAR',
            'data' => $data
        ], now()->addMinutes(15));

        $currentYear = (int) now('Asia/Jakarta')->format('Y');
        
        $keyboard = Keyboard::make()->row([
            Button::make((string) $currentYear)->action('setScheduleYear')->param('year', $currentYear),
            Button::make((string) ($currentYear + 1))->action('setScheduleYear')->param('year', $currentYear + 1),
            Button::make((string) ($currentYear + 2))->action('setScheduleYear')->param('year', $currentYear + 2),
        ])->row([
            Button::make('Batal')->action('cancelAction')
        ]);

        $this->chat->html("Pilih <b>Tahun</b> untuk jadwal rapat:")->keyboard($keyboard)->send();
    }

    public function setScheduleYear(): void
    {
        $year = $this->data->get('year');
        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");
        if (!$stateData || $stateData['state'] !== 'WIZARD_YEAR') return;

        $data = $stateData['data'];
        $data['year'] = $year;

        $this->chat->deleteMessage($this->messageId)->send();

        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'WIZARD_MONTH',
            'data' => $data
        ], now()->addMinutes(15));

        $keyboard = Keyboard::make()
            ->row([
                Button::make('Jan (1)')->action('setScheduleMonth')->param('month', 1),
                Button::make('Feb (2)')->action('setScheduleMonth')->param('month', 2),
                Button::make('Mar (3)')->action('setScheduleMonth')->param('month', 3),
            ])
            ->row([
                Button::make('Apr (4)')->action('setScheduleMonth')->param('month', 4),
                Button::make('Mei (5)')->action('setScheduleMonth')->param('month', 5),
                Button::make('Jun (6)')->action('setScheduleMonth')->param('month', 6),
            ])
            ->row([
                Button::make('Jul (7)')->action('setScheduleMonth')->param('month', 7),
                Button::make('Ags (8)')->action('setScheduleMonth')->param('month', 8),
                Button::make('Sep (9)')->action('setScheduleMonth')->param('month', 9),
            ])
            ->row([
                Button::make('Okt (10)')->action('setScheduleMonth')->param('month', 10),
                Button::make('Nov (11)')->action('setScheduleMonth')->param('month', 11),
                Button::make('Des (12)')->action('setScheduleMonth')->param('month', 12),
            ])
            ->row([
                Button::make('Batal')->action('cancelAction')
            ]);

        $this->chat->html("Tahun: <b>{$year}</b>

Pilih <b>Bulan</b>:")->keyboard($keyboard)->send();
    }

    public function setScheduleMonth(): void
    {
        $month = (int) $this->data->get('month');
        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");
        if (!$stateData || $stateData['state'] !== 'WIZARD_MONTH') return;

        $data = $stateData['data'];
        $data['month'] = $month;
        
        $this->chat->deleteMessage($this->messageId)->send();

        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'WIZARD_DATE',
            'data' => $data
        ], now()->addMinutes(15));

        $daysInMonth = \Carbon\Carbon::create($data['year'], $month, 1)->daysInMonth;
        
        $keyboard = Keyboard::make();
        $currentRow = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $currentRow[] = Button::make((string) $i)->action('setScheduleDate')->param('date', $i);
            if (count($currentRow) == 6 || $i == $daysInMonth) {
                $keyboard = $keyboard->row($currentRow);
                $currentRow = [];
            }
        }
        $keyboard = $keyboard->row([Button::make('Batal')->action('cancelAction')]);

        $monthName = \Carbon\Carbon::create(null, $month, 1)->translatedFormat('F');
        $this->chat->html("Bulan: <b>{$monthName} {$data['year']}</b>

Pilih <b>Tanggal</b>:")->keyboard($keyboard)->send();
    }

    public function setScheduleDate(): void
    {
        $date = (int) $this->data->get('date');
        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");
        if (!$stateData || $stateData['state'] !== 'WIZARD_DATE') return;

        $data = $stateData['data'];
        $data['date'] = $date;
        
        $this->chat->deleteMessage($this->messageId)->send();

        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'WIZARD_HOUR',
            'data' => $data
        ], now()->addMinutes(15));

        $keyboard = Keyboard::make();
        $currentRow = [];
        for ($h = 0; $h <= 23; $h++) {
            $hourStr = str_pad((string)$h, 2, '0', STR_PAD_LEFT);
            $currentRow[] = Button::make($hourStr)->action('setScheduleHour')->param('hour', $hourStr);
            if (count($currentRow) == 4 || $h == 23) {
                $keyboard = $keyboard->row($currentRow);
                $currentRow = [];
            }
        }
        $keyboard = $keyboard->row([Button::make('Batal')->action('cancelAction')]);

        $formattedDate = sprintf("%02d/%02d/%04d", $date, $data['month'], $data['year']);
        $this->chat->html("Tanggal: <b>{$formattedDate}</b>

Pilih <b>Jam</b>:")->keyboard($keyboard)->send();
    }

    public function setScheduleHour(): void
    {
        $hour = $this->data->get('hour');
        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");
        if (!$stateData || $stateData['state'] !== 'WIZARD_HOUR') return;

        $data = $stateData['data'];
        $data['hour'] = $hour;

        $this->chat->deleteMessage($this->messageId)->send();

        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'WIZARD_MINUTE',
            'data' => $data
        ], now()->addMinutes(15));

        $keyboard = Keyboard::make()->row([
            Button::make('00')->action('setScheduleMinute')->param('minute', '00'),
            Button::make('15')->action('setScheduleMinute')->param('minute', '15'),
            Button::make('30')->action('setScheduleMinute')->param('minute', '30'),
            Button::make('45')->action('setScheduleMinute')->param('minute', '45'),
        ])->row([
            Button::make('Batal')->action('cancelAction')
        ]);

        $this->chat->html("Jam: <b>{$hour}:..</b>

Pilih <b>Menit</b>:")->keyboard($keyboard)->send();
    }

    public function setScheduleMinute(): void
    {
        $minute = $this->data->get('minute');
        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");
        if (!$stateData || $stateData['state'] !== 'WIZARD_MINUTE') return;

        $data = $stateData['data'];
        $data['minute'] = $minute;

        $this->chat->deleteMessage($this->messageId)->send();

        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'WIZARD_DURATION',
            'data' => $data
        ], now()->addMinutes(15));

        $keyboard = Keyboard::make()->row([
            Button::make('30 Menit')->action('setScheduleDuration')->param('duration', 30),
            Button::make('1 Jam')->action('setScheduleDuration')->param('duration', 60),
            Button::make('2 Jam')->action('setScheduleDuration')->param('duration', 120),
        ])->row([
            Button::make('1.5 Jam')->action('setScheduleDuration')->param('duration', 90),
            Button::make('2.5 Jam')->action('setScheduleDuration')->param('duration', 150),
            Button::make('3 Jam')->action('setScheduleDuration')->param('duration', 180),
        ])->row([
            Button::make('Batal')->action('cancelAction')
        ]);

        $this->chat->html("Waktu Rapat: <b>{$data['hour']}:{$minute} WIB</b>

Pilih <b>Durasi Rapat</b>:")->keyboard($keyboard)->send();
    }

    public function setScheduleDuration(): void
    {
        $duration = (int) $this->data->get('duration');
        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");
        if (!$stateData || $stateData['state'] !== 'WIZARD_DURATION') return;

        $data = $stateData['data'];
        $data['duration'] = $duration;

        $this->chat->deleteMessage($this->messageId)->send();

        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'WIZARD_CONFIRM',
            'data' => $data
        ], now()->addMinutes(15));

        $formattedDate = sprintf("%02d/%02d/%04d", $data['date'], $data['month'], $data['year']);
        
        $keyboard = Keyboard::make()->row([
            Button::make('Ya, Buat Rapat')->action('confirmSchedule'),
            Button::make('Batal')->action('cancelAction')
        ]);

        $msg = implode("
", [
            "<b>Konfirmasi Jadwal Rapat</b>",
            "",
            "Topik: <b>{$data['topic']}</b>",
            "Tanggal: <b>{$formattedDate}</b>",
            "Jam: <b>{$data['hour']}:{$data['minute']} WIB</b>",
            "Durasi: <b>{$duration} Menit</b>",
            "",
            "Apakah data di atas sudah benar?"
        ]);

        $this->chat->html($msg)->keyboard($keyboard)->send();
    }

    public function confirmSchedule(): void
    {
        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");
        if (!$stateData || $stateData['state'] !== 'WIZARD_CONFIRM') {
            $this->chat->html("Sesi sudah habis, silakan mulai ulang.")->send();
            return;
        }

        $data = $stateData['data'];
        $this->chat->deleteMessage($this->messageId)->send();
        
        $dateTimeStr = sprintf("%04d-%02d-%02d %s:%s", $data['year'], $data['month'], $data['date'], $data['hour'], $data['minute']);
        try {
            $parsedTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $dateTimeStr, 'Asia/Jakarta')->setTimezone('UTC');
            $data['start_time'] = $parsedTime->toIso8601String();
        } catch (\Exception $e) {
            $this->chat->html("Waktu tidak valid.")->send();
            return;
        }

        $this->finalizeSchedule($data, $data['duration']);
    }
    private function finalizeSchedule(array $data, int $duration): void
    {
        \Illuminate\Support\Facades\Cache::forget("telegram_state_{$this->chat->id}");
        
        $user = User::find($this->chat->user_id);
        if (!$user) return;

        $accountId = $data['account_id'];
        $reqStart = \Carbon\Carbon::parse($data['start_time'])->setTimezone('UTC');
        $reqEnd = $reqStart->copy()->addMinutes($duration);

        $isOverlapping = function ($accId) use ($reqStart, $reqEnd) {
            $meetings = Meeting::where('zoom_account_id', $accId)
                ->where('created_at', '>=', now()->subDays(1))
                ->get();
            foreach ($meetings as $m) {
                if ($m->isPast()) continue;
                $mStart = $m->start_time ? \Carbon\Carbon::parse($m->start_time) : $m->created_at;
                $mEnd = $mStart->copy()->addMinutes($m->duration);
                if ($mStart < $reqEnd && $mEnd > $reqStart) {
                    return true;
                }
            }
            return false;
        };

        $zoomAccount = null;
        if ($accountId === 'auto') {
            $accounts = $user->zoomAccounts()->whereNotNull('access_token')->get();
            foreach ($accounts as $account) {
                if (!$isOverlapping($account->id)) {
                    $zoomAccount = $account;
                    break;
                }
            }
        } else {
            $zoomAccount = $user->zoomAccounts()->find($accountId);
            if ($zoomAccount && $isOverlapping($zoomAccount->id)) {
                $this->chat->html("<b>Gagal:</b> Akun Zoom ini sudah ada jadwal rapat yang bentrok pada waktu tersebut.")->send();
                return;
            }
        }

        if (!$zoomAccount) {
            $this->chat->html("<b>Gagal:</b> Tidak ada akun Zoom yang tersedia di jadwal tersebut (semua bentrok).")->send();
            return;
        }

        $loading = $this->chat->html("<i>Menjadwalkan rapat...</i>")->send();
        $loadingMessageId = $loading->telegraphMessageId();

        try {
            $zoomService = app(ZoomOAuthService::class);
            $response = $zoomService->createMeeting($zoomAccount, [
                'topic' => $data['topic'],
                'type' => 2,
                'start_time' => $reqStart->toIso8601String(),
                'duration' => $duration
            ]);

            Meeting::create([
                'user_id' => $user->id,
                'zoom_account_id' => $zoomAccount->id,
                'zoom_meeting_id' => (string)$response['id'],
                'topic' => $response['topic'],
                'type' => 2,
                'start_time' => $reqStart,
                'duration' => $duration,
                'timezone' => $response['timezone'] ?? 'Asia/Jakarta',
                'join_url' => $response['join_url'],
                'start_url' => $response['start_url'],
                'password' => $response['password'] ?? null,
            ]);

            $timeStr = $reqStart->setTimezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB';
            
            $inviteText = "Agenda Zoom: {$response['topic']}\nWaktu: {$timeStr}\n\nGabung Zoom Meeting:\n{$response['join_url']}\n\nMeeting ID: {$response['id']}\nPasscode: " . ($response['password'] ?? 'Tidak ada');

            $message = implode("\n", [
                "<blockquote><b>Rapat Terjadwal Berhasil Dibuat!</b>",
                "Akun: {$zoomAccount->account_name} ({$zoomAccount->email})</blockquote>",
                "",
                "Salin undangan di bawah ini:",
                "<pre><code class=\"language-copy\">{$inviteText}</code></pre>"
            ]);

            $this->chat->html($message)->send();
            if ($loadingMessageId) {
                $this->chat->deleteMessage($loadingMessageId)->send();
            }
        } catch (\Exception $e) {
            if (isset($loadingMessageId)) {
                $this->chat->deleteMessage($loadingMessageId)->send();
            }
            Log::error('Gagal membuat scheduled meeting via Telegram: ' . $e->getMessage());
            $this->chat->html("<b>Terjadi Kesalahan!</b>\nGagal membuat jadwal rapat di Zoom.")->send();
        }
    }

    /**
     * Manage Meetings (Kelola Rapat)
     */
    public function manageMeetings(): void
    {
        $user = User::find($this->chat->user_id);
        if (!$user) return;

        $meetings = Meeting::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(1))
            ->orderBy('start_time', 'asc')
            ->get()
            ->filter(fn($m) => !$m->isPast())
            ->values();

        if ($meetings->isEmpty()) {
            $this->chat->html("<blockquote><b>Kelola Rapat</b>\n\nTidak ada rapat aktif yang bisa dikelola saat ini.</blockquote>")->send();
            return;
        }

        $chunks = $meetings->chunk(5);
        $globalIndex = 1;

        foreach ($chunks as $chunk) {
            $lines = [];
            $keyboard = Keyboard::make();

            foreach ($chunk as $m) {
                $time = $m->type == 1 ? 'Instan' : \Carbon\Carbon::parse($m->start_time)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB';
                $lines[] = "<b>{$globalIndex}. {$m->topic}</b>";
                $lines[] = "Waktu: {$time}";
                $lines[] = "";

                $keyboard = $keyboard->row([
                    Button::make("Edit $globalIndex")->action('askEditOption')->param('id', $m->id),
                    Button::make("Hapus $globalIndex")->action('deleteMeetingPrompt')->param('id', $m->id),
                ]);

                $globalIndex++;
            }

            $this->chat->html(implode("\n", $lines))->keyboard($keyboard)->send();
        }
    }

    public function deleteMeetingPrompt(): void
    {
        $meetingId = $this->data->get('id');
        $meeting = Meeting::find($meetingId);
        if (!$meeting) {
            $this->chat->html("Rapat tidak ditemukan atau sudah dihapus.")->send();
            return;
        }

        $keyboard = Keyboard::make()->row([
            Button::make('Ya, Hapus')->action('confirmDeleteMeeting')->param('id', $meeting->id),
            Button::make('Batal')->action('cancelAction')
        ]);

        $this->chat->html("Apakah Anda yakin ingin menghapus rapat <b>{$meeting->topic}</b>?")->keyboard($keyboard)->send();
    }

    public function cancelAction(): void
    {
        $this->chat->deleteMessage($this->messageId)->send();
        $this->menu();
    }

    public function confirmDeleteMeeting(): void
    {
        $meetingId = $this->data->get('id');
        $meeting = Meeting::find($meetingId);
        if (!$meeting) return;
        
        try {
            $zoomAccount = $meeting->zoomAccount;
            app(ZoomOAuthService::class)->deleteMeeting($zoomAccount, $meeting->zoom_meeting_id);
            $meeting->delete();
            $this->chat->deleteMessage($this->messageId)->send();
            $this->chat->html("Rapat <b>{$meeting->topic}</b> berhasil dibatalkan dan dihapus.")->send();
        } catch (\Exception $e) {
            $this->chat->deleteMessage($this->messageId)->send();
            $this->chat->html("Gagal menghapus rapat di Zoom: " . $e->getMessage())->send();
        }
    }

    public function askEditOption(): void
    {
        $meetingId = $this->data->get('id');
        $meeting = Meeting::find($meetingId);
        if (!$meeting) return;

        $keyboard = Keyboard::make()->row([
            Button::make('Ubah Topik')->action('prepareEditTopic')->param('id', $meeting->id),
            Button::make('Ubah Waktu')->action('prepareEditTime')->param('id', $meeting->id)
        ])->row([
            Button::make('Batal')->action('cancelAction')
        ]);

        $this->chat->html("Apa yang ingin Anda ubah dari rapat <b>{$meeting->topic}</b>?")->keyboard($keyboard)->send();
    }

    public function prepareEditTopic(): void
    {
        $meetingId = $this->data->get('id');
        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'EDIT_TOPIC',
            'data' => ['id' => $meetingId]
        ], now()->addMinutes(15));

        $this->chat->html("Ketik <b>Topik Baru</b> untuk rapat ini di kolom chat:")->send();
    }

    public function prepareEditTime(): void
    {
        $meetingId = $this->data->get('id');
        $meeting = Meeting::find($meetingId);
        if (!$meeting) return;

        // Instant meeting cannot change time easily to scheduled without duration changes, but let's assume they can
        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'EDIT_TIME',
            'data' => ['id' => $meetingId]
        ], now()->addMinutes(15));

        $keyboard = Keyboard::make()->row([
            Button::make('1 Jam Lagi')->action('processEditTimeBtn')->param('time', '1_hour_later'),
            Button::make('Besok Pagi')->action('processEditTimeBtn')->param('time', 'tomorrow_morning')
        ]);

        $this->chat->html("Kapan waktu baru untuk rapat <b>{$meeting->topic}</b>?\n\nKetik format manual: <code>DD-MM-YYYY HH:MM</code> atau pilih:")->keyboard($keyboard)->send();
    }

    public function processEditTimeBtn(): void
    {
        $timeChoice = $this->data->get('time');
        
        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");
        if (!$stateData || $stateData['state'] !== 'EDIT_TIME') {
            $this->chat->html("Sesi sudah habis, silakan mulai ulang.")->send();
            return;
        }
        $data = $stateData['data'];

        $startTime = now('Asia/Jakarta');
        if ($timeChoice === '1_hour_later') {
            $startTime = $startTime->addHour()->startOfMinute();
        } elseif ($timeChoice === 'tomorrow_morning') {
            $startTime = $startTime->addDay()->setTime(9, 0, 0);
        }

        $data['start_time'] = $startTime->setTimezone('UTC')->toIso8601String();
        
        $this->askEditDuration($data);
    }

    private function askEditDuration(array $data): void
    {
        \Illuminate\Support\Facades\Cache::put("telegram_state_{$this->chat->id}", [
            'state' => 'EDIT_DURATION',
            'data' => $data
        ], now()->addMinutes(15));

        $keyboard = Keyboard::make()->row([
            Button::make('30 Menit')->action('processEditDurationBtn')->param('duration', 30),
            Button::make('1 Jam')->action('processEditDurationBtn')->param('duration', 60),
        ])->row([
            Button::make('2 Jam')->action('processEditDurationBtn')->param('duration', 120),
            Button::make('3 Jam')->action('processEditDurationBtn')->param('duration', 180),
        ]);

        $this->chat->html("Berapa lama durasi rapat yang baru?\n\nPilih atau ketik angkanya (contoh: <code>45</code>):")
            ->keyboard($keyboard)->send();
    }

    public function processEditDurationBtn(): void
    {
        $duration = (int) $this->data->get('duration');
        $stateData = \Illuminate\Support\Facades\Cache::get("telegram_state_{$this->chat->id}");
        if (!$stateData || $stateData['state'] !== 'EDIT_DURATION') return;
        
        $data = $stateData['data'];
        $data['duration'] = $duration;
        $this->finalizeEdit($data);
    }

    private function finalizeEdit(array $data): void
    {
        \Illuminate\Support\Facades\Cache::forget("telegram_state_{$this->chat->id}");
        
        $meeting = Meeting::find($data['id']);
        if (!$meeting) return;

        $updateData = [];
        $zoomData = [];

        if (isset($data['topic'])) {
            $updateData['topic'] = $data['topic'];
            $zoomData['topic'] = $data['topic'];
        }

        if (isset($data['start_time'])) {
            $reqStart = \Carbon\Carbon::parse($data['start_time']);
            $reqDuration = $data['duration'] ?? 60;

            // Check overlap
            $zoomAccount = $meeting->zoomAccount;
            $reqEnd = $reqStart->copy()->addMinutes($reqDuration);
            $overlaps = Meeting::where('zoom_account_id', $zoomAccount->id)
                ->where('id', '!=', $meeting->id)
                ->where('created_at', '>=', now()->subDays(1))
                ->get()
                ->filter(function($m) use ($reqStart, $reqEnd) {
                    if ($m->isPast()) return false;
                    $mStart = $m->start_time ? \Carbon\Carbon::parse($m->start_time) : $m->created_at;
                    $mEnd = $mStart->copy()->addMinutes($m->duration);
                    return ($mStart < $reqEnd && $mEnd > $reqStart);
                });

            if ($overlaps->isNotEmpty()) {
                $this->chat->html("<b>Gagal:</b> Waktu rapat bentrok dengan jadwal lain di akun Zoom ini.")->send();
                return;
            }

            $updateData['start_time'] = $reqStart;
            $updateData['duration'] = $reqDuration;
            $updateData['type'] = 2; // Scheduled
            
            $zoomData['start_time'] = $reqStart->copy()->setTimezone($meeting->timezone)->format('Y-m-d\TH:i:s');
            $zoomData['duration'] = $reqDuration;
            $zoomData['type'] = 2;
        }

        if (empty($updateData)) return;

        $loading = $this->chat->html("<i>Menyimpan perubahan...</i>")->send();
        $loadingId = $loading->telegraphMessageId();

        try {
            app(ZoomOAuthService::class)->updateMeeting($meeting->zoomAccount, $meeting->zoom_meeting_id, $zoomData);
            $meeting->update($updateData);

            if ($loadingId) $this->chat->deleteMessage($loadingId)->send();
            
            $timeStr = $meeting->type == 1 ? 'Instan' : \Carbon\Carbon::parse($meeting->start_time)->setTimezone($meeting->timezone)->format('d M Y, H:i') . ' WIB';
            $msg = implode("\n", [
                "<blockquote><b>Perubahan Berhasil Disimpan!</b></blockquote>",
                "Agenda Zoom: <b>{$meeting->topic}</b>",
                "Waktu: {$timeStr}",
                "Link: {$meeting->join_url}"
            ]);
            $this->chat->html($msg)->send();

        } catch (\Exception $e) {
            if ($loadingId) $this->chat->deleteMessage($loadingId)->send();
            $this->chat->html("Gagal menyimpan perubahan: " . $e->getMessage())->send();
        }
    }

    /**
     * Handle unknown commands / plain text messages
     */
    protected function handleUnknownCommand(Stringable $text): void
    {
        $this->chat->message(
            "Perintah tidak dikenali.\n\n" .
            "Ketik /menu untuk melihat fitur interaktif bot."
        )->send();
    }
}
