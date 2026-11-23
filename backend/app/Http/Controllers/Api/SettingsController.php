<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show()
    {
        return ['settings' => Setting::map(), 'partners' => Partner::orderBy('id')->get()];
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:80', 'company_site' => 'nullable|string|max:120', 'company_email' => 'nullable|email',
            'company_phone' => 'nullable|string|max:40', 'invoice_prefix' => 'required|string|alpha_num|max:8',
            'country_code' => 'required|digits_between:1,4', 'renewal_alert_days' => 'required|integer|min:1|max:365',
            'auto_email_recurring' => 'boolean',
        ]);
        $data['auto_email_recurring'] = $request->boolean('auto_email_recurring') ? '1' : '0';
        Setting::put($data);

        return $this->show();
    }
}
