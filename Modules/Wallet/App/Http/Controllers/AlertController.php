<?php

namespace Modules\Wallet\App\Http\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Jobs\SendReminderNotification;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::guard('app_users')->user()->id;
        $alerts=  Alert::where('user_id',$userId)->get();
        return Common::apiResponse(true, 'Reminder created successfully',  $alerts, null,  $alerts, true);
    }
    public function store(Request $request)
    {
        $request->validate([
            'text' => 'nullable|string',
            'date' => 'required|date',
            // 'time' => 'required|date_format:H:i',
            // 'sub_services_id' => 'required|exists:sub_services,id',
        ]);
        $user = Auth::guard('app_users')->user();
        $reminder = Alert::create([
            'text' => $request->text,
            'date' => $request->date,
            'time' => $request->time,
            'user_id' => $user->id,
            'sub_services_id' => $request->sub_services_id,
        ]);

        // جدولة التذكير بعد إنشائه
        // $this->scheduleReminderNotification($reminder);
        return Common::apiResponse(true, 'Reminder created successfully',  $reminder, null, null, true);

    }

    // private function scheduleReminderNotification($reminder)
    // {
    //     $reminderDateTime = $reminder->date . ' ' . $reminder->time;

    //     // Schedule the job to be executed at the reminder time
    //     SendReminderNotification::dispatch($reminder)->delay(now()->parse($reminderDateTime));
    // }
 
}
