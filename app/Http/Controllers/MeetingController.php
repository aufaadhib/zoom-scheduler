<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\ZoomAccount;
use App\Services\ZoomOAuthService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function __construct(
        protected ZoomOAuthService $zoomService
    ) {}

    /**
     * Display a listing of the meetings.
     */
    public function index(Request $request): View
    {
        $allMeetings = $request->user()->meetings()
            ->with('zoomAccount')
            ->orderBy('start_time', 'asc') // Order active upcoming meetings chronologically
            ->orderBy('created_at', 'desc')
            ->get();

        // Active meetings: Instant meetings, and Scheduled meetings that haven't ended yet
        $activeMeetings = $allMeetings->filter(fn($m) => !$m->isPast());
        
        // Past meetings: Scheduled meetings that have ended
        // Sorted with the most recently ended first
        $pastMeetings = $allMeetings->filter(fn($m) => $m->isPast())
            ->sortByDesc('start_time');

        return view('meetings.index', compact('activeMeetings', 'pastMeetings'));
    }

    /**
     * Show the form for creating a new meeting.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $zoomAccounts = $request->user()->zoomAccounts()
            ->whereNotNull('access_token')
            ->orderBy('account_name', 'asc')
            ->get();

        if ($zoomAccounts->isEmpty()) {
            return redirect()->route('zoom.create')
                ->with('error', 'Anda harus menghubungkan minimal satu akun Zoom terlebih dahulu sebelum bisa membuat meeting.');
        }

        return view('meetings.create', compact('zoomAccounts'));
    }

    /**
     * Store a newly created meeting in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'zoom_account_id' => ['required', 'string'],
            'topic' => ['required', 'string', 'max:255'],
            'agenda' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:1,2'],
            'start_date' => ['required_if:type,2', 'nullable', 'date', 'after_or_equal:today'],
            'start_time' => ['required_if:type,2', 'nullable', 'string'],
            'duration' => ['required_if:type,2', 'nullable', 'integer', 'min:1', 'max:1440'],
            'timezone' => ['required_if:type,2', 'nullable', 'string'],
            'password' => ['nullable', 'string', 'max:10'],
        ], [
            'zoom_account_id.required' => 'Pilih akun Zoom terlebih dahulu.',
            'topic.required' => 'Topik meeting wajib diisi.',
            'start_date.required_if' => 'Tanggal meeting wajib diisi untuk meeting terjadwal.',
            'start_time.required_if' => 'Jam meeting wajib diisi untuk meeting terjadwal.',
            'duration.required_if' => 'Durasi meeting wajib diisi untuk meeting terjadwal.',
            'timezone.required_if' => 'Timezone wajib diisi untuk meeting terjadwal.',
        ]);

        $reqType = (int) $validated['type'];
        $reqDuration = $reqType === 2 ? (int) $validated['duration'] : 60;
        
        $reqStart = null;
        if ($reqType === 2) {
            $dateTimeString = $validated['start_date'] . ' ' . $validated['start_time'];
            $reqStart = Carbon::createFromFormat('Y-m-d H:i', $dateTimeString, $validated['timezone'])->utc();
        } else {
            $reqStart = now()->utc();
        }
        $reqEnd = $reqStart->copy()->addMinutes($reqDuration);

        $isOverlapping = function ($accountId) use ($reqStart, $reqEnd) {
            $meetings = Meeting::where('zoom_account_id', $accountId)
                ->where('created_at', '>=', now()->subDays(1)) // Optimization for instant meetings
                ->get();

            foreach ($meetings as $m) {
                // If it's a scheduled meeting that has passed, or instant meeting that ended
                if ($m->isPast()) continue;

                $mStart = $m->start_time ? Carbon::parse($m->start_time) : $m->created_at;
                $mEnd = $mStart->copy()->addMinutes($m->duration);

                if ($mStart < $reqEnd && $mEnd > $reqStart) {
                    return true;
                }
            }
            return false;
        };

        $zoomAccount = null;

        if ($validated['zoom_account_id'] === 'auto') {
            $accounts = $request->user()->zoomAccounts()->whereNotNull('access_token')->get();
            foreach ($accounts as $account) {
                if (!$isOverlapping($account->id)) {
                    $zoomAccount = $account;
                    break;
                }
            }
            if (!$zoomAccount) {
                return redirect()->back()->withInput()->with('error', 'Semua akun Zoom sedang terpakai (bentrok) pada jadwal tersebut. Silakan pilih waktu lain.');
            }
        } else {
            $zoomAccount = $request->user()->zoomAccounts()->findOrFail($validated['zoom_account_id']);
            if ($isOverlapping($zoomAccount->id)) {
                return redirect()->back()->withInput()->with('error', 'Akun Zoom ini sudah memiliki jadwal rapat yang bentrok pada waktu tersebut. Silakan pilih akun lain atau pilih Otomatis.');
            }
        }

        try {
            $zoomData = [
                'topic' => $validated['topic'],
                'type' => $reqType,
                'agenda' => $validated['agenda'],
                'password' => $validated['password'],
            ];

            $startTimeFormatted = null;

            if ($reqType === 2) {
                $zoomData['start_time'] = $reqStart->copy()->tz($validated['timezone'])->format('Y-m-d\TH:i:s');
                $zoomData['duration'] = $reqDuration;
                $zoomData['timezone'] = $validated['timezone'];
                $startTimeFormatted = $reqStart;

            } else {
                // Instant meetings
                $zoomData['duration'] = 60; // default duration display
                $zoomData['timezone'] = 'Asia/Jakarta';
            }

            // Create on Zoom
            $zoomMeetingResponse = $this->zoomService->createMeeting($zoomAccount, $zoomData);

            // Save locally
            $meeting = $request->user()->meetings()->create([
                'zoom_account_id' => $zoomAccount->id,
                'zoom_meeting_id' => (string) $zoomMeetingResponse['id'],
                'topic' => $zoomMeetingResponse['topic'],
                'agenda' => $zoomMeetingResponse['agenda'] ?? null,
                'type' => $zoomMeetingResponse['type'],
                'start_time' => $startTimeFormatted,
                'duration' => $zoomMeetingResponse['duration'] ?? 60,
                'timezone' => $zoomMeetingResponse['timezone'] ?? 'Asia/Jakarta',
                'join_url' => $zoomMeetingResponse['join_url'],
                'start_url' => $zoomMeetingResponse['start_url'],
                'password' => $zoomMeetingResponse['password'] ?? null,
            ]);

            return redirect()->route('meetings.show', $meeting)
                ->with('success', 'Meeting Zoom berhasil dibuat!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat meeting di Zoom: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified meeting.
     */
    public function show(Request $request, Meeting $meeting): View
    {
        // Ensure meeting belongs to the user
        if ($meeting->user_id !== $request->user()->id) {
            abort(403);
        }

        $meeting->load('zoomAccount');

        return view('meetings.show', compact('meeting'));
    }

    /**
     * Show the form for editing the specified meeting.
     */
    public function edit(Request $request, Meeting $meeting): View
    {
        // Ensure meeting belongs to the user
        if ($meeting->user_id !== $request->user()->id) {
            abort(403);
        }

        // Only scheduled meetings can be meaningfully edited (time-wise)
        // But let's allow editing topic/agenda for both
        
        $zoomAccounts = $request->user()->zoomAccounts()
            ->whereNotNull('access_token')
            ->orderBy('account_name', 'asc')
            ->get();

        return view('meetings.edit', compact('meeting', 'zoomAccounts'));
    }

    /**
     * Update the specified meeting in storage.
     */
    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        if ($meeting->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'zoom_account_id' => ['required', 'string'],
            'topic' => ['required', 'string', 'max:255'],
            'agenda' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:1,2'],
            'start_date' => ['required_if:type,2', 'nullable', 'date'],
            'start_time' => ['required_if:type,2', 'nullable', 'string'],
            'duration' => ['required_if:type,2', 'nullable', 'integer', 'min:1', 'max:1440'],
            'timezone' => ['required_if:type,2', 'nullable', 'string'],
            'password' => ['nullable', 'string', 'max:10'],
        ], [
            'zoom_account_id.required' => 'Pilih akun Zoom terlebih dahulu.',
            'topic.required' => 'Topik meeting wajib diisi.',
            'start_date.required_if' => 'Tanggal meeting wajib diisi untuk meeting terjadwal.',
            'start_time.required_if' => 'Jam meeting wajib diisi untuk meeting terjadwal.',
            'duration.required_if' => 'Durasi meeting wajib diisi untuk meeting terjadwal.',
            'timezone.required_if' => 'Timezone wajib diisi untuk meeting terjadwal.',
        ]);

        $reqType = (int) $validated['type'];
        $reqDuration = $reqType === 2 ? (int) $validated['duration'] : 60;
        
        $reqStart = null;
        if ($reqType === 2) {
            $dateTimeString = $validated['start_date'] . ' ' . $validated['start_time'];
            $reqStart = Carbon::createFromFormat('Y-m-d H:i', $dateTimeString, $validated['timezone'])->utc();
        } else {
            $reqStart = now()->utc();
        }
        $reqEnd = $reqStart->copy()->addMinutes($reqDuration);

        $isOverlapping = function ($accountId) use ($reqStart, $reqEnd, $meeting) {
            $meetings = Meeting::where('zoom_account_id', $accountId)
                ->where('id', '!=', $meeting->id) // Exclude current meeting
                ->where('created_at', '>=', now()->subDays(1))
                ->get();

            foreach ($meetings as $m) {
                if ($m->isPast()) continue;

                $mStart = $m->start_time ? Carbon::parse($m->start_time) : $m->created_at;
                $mEnd = $mStart->copy()->addMinutes($m->duration);

                if ($mStart < $reqEnd && $mEnd > $reqStart) {
                    return true;
                }
            }
            return false;
        };

        $zoomAccount = null;

        if ($validated['zoom_account_id'] === 'auto') {
            $accounts = $request->user()->zoomAccounts()->whereNotNull('access_token')->get();
            foreach ($accounts as $account) {
                if (!$isOverlapping($account->id)) {
                    $zoomAccount = $account;
                    break;
                }
            }
            if (!$zoomAccount) {
                return redirect()->back()->withInput()->with('error', 'Semua akun Zoom sedang terpakai (bentrok) pada jadwal tersebut. Silakan pilih waktu lain.');
            }
        } else {
            $zoomAccount = $request->user()->zoomAccounts()->findOrFail($validated['zoom_account_id']);
            if ($isOverlapping($zoomAccount->id)) {
                return redirect()->back()->withInput()->with('error', 'Akun Zoom ini sudah memiliki jadwal rapat yang bentrok pada waktu tersebut. Silakan pilih akun lain atau pilih Otomatis.');
            }
        }

        try {
            $zoomData = [
                'topic' => $validated['topic'],
                'type' => $reqType,
                'agenda' => $validated['agenda'],
                'password' => $validated['password'] ?? '',
            ];

            $startTimeFormatted = null;

            if ($reqType === 2) {
                $zoomData['start_time'] = $reqStart->copy()->tz($validated['timezone'])->format('Y-m-d\TH:i:s');
                $zoomData['duration'] = $reqDuration;
                $zoomData['timezone'] = $validated['timezone'];
                $startTimeFormatted = $reqStart;
            } else {
                $zoomData['duration'] = 60;
                $zoomData['timezone'] = 'Asia/Jakarta';
            }

            // Update on Zoom
            // If the account changed, we theoretically should delete from old and create on new.
            // But Zoom API update is only on the original account.
            // To simplify, if account changed, we delete old and create new.
            if ($meeting->zoom_account_id !== $zoomAccount->id) {
                // Delete old
                try {
                    $oldAccount = $request->user()->zoomAccounts()->find($meeting->zoom_account_id);
                    if ($oldAccount) {
                        $this->zoomService->deleteMeeting($oldAccount, $meeting->zoom_meeting_id);
                    }
                } catch (\Exception $e) {
                    // Ignore deletion errors on old account if it was already deleted
                }
                
                // Create new
                $zoomMeetingResponse = $this->zoomService->createMeeting($zoomAccount, $zoomData);
                
                $meeting->update([
                    'zoom_account_id' => $zoomAccount->id,
                    'zoom_meeting_id' => (string) $zoomMeetingResponse['id'],
                    'topic' => $zoomMeetingResponse['topic'],
                    'agenda' => $zoomMeetingResponse['agenda'] ?? null,
                    'type' => $zoomMeetingResponse['type'],
                    'start_time' => $startTimeFormatted,
                    'duration' => $zoomMeetingResponse['duration'] ?? 60,
                    'timezone' => $zoomMeetingResponse['timezone'] ?? 'Asia/Jakarta',
                    'join_url' => $zoomMeetingResponse['join_url'],
                    'start_url' => $zoomMeetingResponse['start_url'],
                    'password' => $zoomMeetingResponse['password'] ?? null,
                ]);

            } else {
                // Same account, just update
                $this->zoomService->updateMeeting($zoomAccount, $meeting->zoom_meeting_id, $zoomData);
                
                $meeting->update([
                    'topic' => $validated['topic'],
                    'agenda' => $validated['agenda'] ?? null,
                    'type' => $reqType,
                    'start_time' => $startTimeFormatted,
                    'duration' => $reqType === 2 ? $reqDuration : 60,
                    'timezone' => $reqType === 2 ? $validated['timezone'] : 'Asia/Jakarta',
                    'password' => $validated['password'] ?? null,
                ]);
            }

            return redirect()->route('meetings.show', $meeting)
                ->with('success', 'Meeting Zoom berhasil diperbarui!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui meeting di Zoom: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified meeting from storage and Zoom.
     */
    public function destroy(Request $request, Meeting $meeting): RedirectResponse
    {
        // Ensure meeting belongs to the user
        if ($meeting->user_id !== $request->user()->id) {
            abort(403);
        }

        $meeting->load('zoomAccount');

        try {
            // Delete from Zoom API
            $this->zoomService->deleteMeeting($meeting->zoomAccount, $meeting->zoom_meeting_id);

            // Delete locally
            $topic = $meeting->topic;
            $meeting->delete();

            return redirect()->route('meetings.index')
                ->with('success', 'Meeting "' . $topic . '" berhasil dihapus dari aplikasi dan dibatalkan di Zoom.');

        } catch (\Exception $e) {
            return redirect()->route('meetings.index')
                ->with('error', 'Gagal membatalkan meeting di Zoom: ' . $e->getMessage());
        }
    }
}
