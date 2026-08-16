<div style="background: #e3e5ef; padding: 40px 0;">
<div style="margin: 0 auto;background: #ffffff;box-shadow: 0 0 30px rgba(41, 53, 118, 0.15);font-family: 'Inter', sans-serif;font-size: 12px;font-weight: 400;padding:20px 30px 30px 30px;border-radius: 10px;">
<div style="margin: 10px 0 20px 0;"><a href="{{ config('APP_URL') }}" style="border: none; outline: none; text-decoration: none;" target="_blank"><img src="{{ config('app.frontend_url') }}/storage/website/{{ config('SITE_LOGO') }}" style="width:160px; border: none; outline: none; " /></a></div>
<div style="border: solid 1px #ededed;padding: 20px;border-radius: 10px;">
<p style="font-size: 16px; font-weight: bold; margin: 0 0 5px 0;">Dear {{ $user->name }},</p>
<p style="margin: 0px;">To get back into your account, you&#39;ll need to create a new password.</p>
<p style="height: 10px; margin: 0px; padding:0px;">&nbsp;</p>
<p>It&#39;s easy:</p>
<p style="margin:0px; padding:0px;">1. Click the link below to open a secure browser window.</p>
<p style="margin:0px; padding:0px;">2. Enter your new password and you&#39;re done.</p>
<p style="margin: 15px 0;"><a href="{{ $link }}">{{ $link }}</a></p>
<p style="margin:0px; padding:0px;">This password reset link will expire in <strong>60 minutes.</strong></p>
<p style="height: 10px; margin: 0px; padding:0px;">&nbsp;</p>
<p style="margin:0px; padding:0px;">If you are still struggling to access your account please contact us.</p>
<p style="margin:0px; padding:0px;"><a href="mailto:{{ config('SITE_EMAIL') }}" target="_blank">{{ config('SITE_EMAIL') }}</a>&nbsp;or<br />call us on : {{ config('SITE_PHONE') }}.</p>
<p style="height: 10px; margin: 0px; padding:0px;">&nbsp;</p>
<p style="margin:0px; padding:0px;">Regards,</p>
<p style="margin:0px; padding:0px;">{{ config('SITE_NAME') }}</p>
</div>
</div>
</div>