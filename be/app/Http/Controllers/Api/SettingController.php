<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * Lay tat ca cai dat
     */
    public function index()
    {
        $settings = [
            'site_name' => config('app.name', 'DTU LingoAI'),
            'logo_url' => config('app.logo_url', ''),
            'site_description' => config('app.description', ''),
            'contact_email' => config('app.contact_email', ''),
            'contact_phone' => config('app.contact_phone', ''),
            'address' => config('app.address', ''),
            // Email
            'smtp_host' => config('mail.mailers.smtp.host', ''),
            'smtp_port' => config('mail.mailers.smtp.port', ''),
            'smtp_username' => config('mail.mailers.smtp.username', ''),
            'smtp_password' => config('mail.mailers.smtp.password', ''),
            'enable_ssl' => config('mail.mailers.smtp.encryption') === 'tls',
            // Payment
            'enable_vnpay' => config('services.vnpay.enabled', false),
            'vnpay_merchant_id' => config('services.vnpay.merchant_id', ''),
            'vnpay_secret_key' => config('services.vnpay.secret_key', ''),
            'enable_momo' => config('services.momo.enabled', false),
            'momo_partner_code' => config('services.momo.partner_code', ''),
            'momo_secret_key' => config('services.momo.secret_key', ''),
        ];

        return response()->json($settings);
    }

    /**
     * Luu cai dat (chi admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Chung
            'site_name' => ['sometimes', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'site_description' => ['nullable', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            // Email
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'string', 'max:10'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'enable_ssl' => ['boolean'],
            // Payment
            'enable_vnpay' => ['boolean'],
            'vnpay_merchant_id' => ['nullable', 'string', 'max:255'],
            'vnpay_secret_key' => ['nullable', 'string', 'max:255'],
            'enable_momo' => ['boolean'],
            'momo_partner_code' => ['nullable', 'string', 'max:255'],
            'momo_secret_key' => ['nullable', 'string', 'max:255'],
        ]);

        // Luu vao cache / database
        foreach ($validated as $key => $value) {
            Cache::put("settings.{$key}", $value);
        }

        return response()->json([
            'message' => 'Luu cai dat thanh cong',
            'settings' => $validated
        ]);
    }

    /**
     * Lay mot cai dat cu the
     */
    public function show($key)
    {
        $value = Cache::get("settings.{$key}");
        return response()->json([$key => $value]);
    }

    /**
     * Cap nhat mot cai dat cu the
     */
    public function update(Request $request, $key)
    {
        $validated = $request->validate([
            'value' => ['required'],
        ]);

        Cache::put("settings.{$key}", $validated['value']);

        return response()->json([
            'message' => 'Cap nhat thanh cong',
            'key' => $key,
            'value' => $validated['value']
        ]);
    }
}
