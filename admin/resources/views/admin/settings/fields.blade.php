@php
    $canEditSettings = $canEditSettings ?? \Illuminate\Support\Facades\Gate::allows($module.'_edit');
    $settingPlaceholders = [
        'GOOGLE_ANALYTICS_ID' => 'Example: G-XXXXXXXXXX',
        'GOOGLE_SEARCH_CONSOLE_VERIFICATION' => 'Paste only the verification token, or paste the full google-site-verification meta tag',
    ];
    $settingHelpText = [
        'GOOGLE_ANALYTICS_ID' => 'Use the GA4 Measurement ID from Google Analytics. Do not paste the full analytics script here.',
        'GOOGLE_SEARCH_CONSOLE_VERIFICATION' => 'Use the content value from Google Search Console HTML tag verification.',
    ];
@endphp

@foreach(['Site','Contact','Social Media','Developer'] as $v)
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 pb-3">
        <div class="form-boarder">
            <div class="form-row">
                <h3 class="text-uppercase w-100">{{ $v }}</h3>
                @foreach($settings as $setting)
                    @if($setting->type == $v)
                        @php
                            $fieldName = 'Site['.$setting->key.']';
                            $placeholder = $settingPlaceholders[$setting->key] ?? '';
                            $helpText = $settingHelpText[$setting->key] ?? '';
                        @endphp
                        <div class="form-group col-md-4 col-lg-4 col-sm-12 col-xs-12">
                            <label class="w-100 control-label" for="{{ $setting->key }}">{{ $setting->label }}</label>

                            @if($setting->input == 'text')
                                @if($canEditSettings)
                                    {{ html()->text($fieldName)->value($setting->value)->class('form-control')->placeholder($placeholder) }}
                                @else
                                    <div class="form-control-plaintext">{{ $setting->value ?: 'N/A' }}</div>
                                @endif
                            @elseif($setting->input == 'textarea')
                                @if($canEditSettings)
                                    {{ html()->textarea($fieldName)->value($setting->value)->class('form-control')->attributes(['rows'=>2]) }}
                                @else
                                    <div class="form-control-plaintext">{{ $setting->value ?: 'N/A' }}</div>
                                @endif
                            @elseif($setting->input == 'file')
                                @if($canEditSettings)
                                    {!! html()->file('Files['.$setting->key.']') !!}
                                    <input type="hidden" name="Files[old_{{ $setting->key }}]" value="{{ $setting->value }}">
                                @endif
                                @if($setting->value)
                                    <div class="image float-right">
                                        {{ html()->img(asset('storage/website/'.$setting->value), $setting->value)->attributes(['title' => $setting->value, 'class' => 'w-50']) }}
                                    </div>
                                @elseif(!$canEditSettings)
                                    <div class="form-control-plaintext">N/A</div>
                                @endif
                            @endif

                            @if($helpText)
                                <small class="form-text text-muted">{{ $helpText }}</small>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
@endforeach
