<div style="font-family: Arial, sans-serif; font-size: 15px; line-height: 1.6; color: #141414; max-width: 560px">
    {!! nl2br(e($body)) !!}
    <p style="margin-top: 24px; color: #666; font-size: 13px">
        {{ \App\Models\Setting::get('company_name', 'Rivoxa') }} · {{ \App\Models\Setting::get('company_site') }}
    </p>
</div>
