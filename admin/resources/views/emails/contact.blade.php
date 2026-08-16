<table bgcolor='#eaeff4' cellspacing='30' cellpadding='10'>
	<tbody>
		<tr>
			<td>
				<a href="{{ route('home') }}">
                <img src="{{ asset('storage/website/'.config('SITE_LOGO')) }}" title="{{ config('SITE_NAME') }}" alt="{{ config('SITE_NAME') }}">
                </a>
			</td>
		</tr>
		<tr>
			<td style="background-color:#ffffff;padding:30px 50px;">
				<h1 style="font-family:Times New Roman;font-size:30px;color:#15303e;"> Your request is received.</h1></br><p style="font-size:14px;font-weight:normal;color:#15303e;">We will aim to reply within 24 hours.</p>
				</br></br>
				<p style="font-size:14px; font-weight:normal;color:#15303e;">If your enquiry is urgent please call us on {{ config("SITE_PHONE") }}</p>
			</td>
		</tr>
		<tr>
			<td>
				<p style="font-size:14px;color:#15303e;">Kind Regards,</p><p style="font-size:14px;color:#15303e;">{{ config("SITE_NAME") }}</p>
			</td>
		</tr>
    </tbody>
</table>
