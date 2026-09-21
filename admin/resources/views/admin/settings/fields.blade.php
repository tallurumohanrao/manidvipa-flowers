@php
    $canEditSettings = $canEditSettings ?? \Illuminate\Support\Facades\Gate::allows($module.'_edit');
    $settingPlaceholders = [
        'GOOGLE_ANALYTICS_ID' => 'Example: G-XXXXXXXXXX',
        'GOOGLE_SEARCH_CONSOLE_VERIFICATION' => 'Paste only the verification token, or paste the full google-site-verification meta tag',
        'GOOGLE_TAG_MANAGER_ID' => 'Example: GTM-XXXXXXX',
        'META_PIXEL_ID' => 'Example: 123456789012345',
    ];
    $settingHelpText = [
        'GOOGLE_ANALYTICS_ID' => 'Direct GA4 fallback. Enter only the Measurement ID. This direct loader is disabled when a Google Tag Manager ID is present.',
        'GOOGLE_SEARCH_CONSOLE_VERIFICATION' => 'Use the content value from Google Search Console HTML tag verification.',
        'GOOGLE_TAG_MANAGER_ID' => 'Recommended tracking method. Configure GA4 and Meta Pixel inside GTM. Use All Pages for the initial load and the custom event virtual_page_view for client-side page changes. Direct GA4 and Meta loaders are disabled while GTM is active.',
        'META_PIXEL_ID' => 'Direct Meta Pixel fallback. It loads only when Google Tag Manager is blank. When using GTM, add the Meta Pixel tag inside your GTM container instead.',
        'PRODUCT_PRICE_VISIBILITY_DEFAULT' => 'Default for products using Use global default. Enquiry Only and Coming Soon disable ordering.',
        'SUBSCRIPTION_PRICE_VISIBILITY_DEFAULT' => 'Recommended during launch: Enquiry Only. Admin prices stay saved but are not sent publicly.',
        'PRICE_ENQUIRY_LABEL' => 'Text displayed where a hidden price would normally appear.',
        'PRICE_ENQUIRY_BUTTON_LABEL' => 'Button text used for products whose public price is hidden.',
        'PRICE_COMING_SOON_LABEL' => 'Text displayed for products or plans marked Coming Soon.',
    ];
    $settingSelectOptions = [
        'PRODUCT_PRICE_VISIBILITY_DEFAULT' => \App\Support\PriceVisibility::modes(false),
        'SUBSCRIPTION_PRICE_VISIBILITY_DEFAULT' => array_intersect_key(
            \App\Support\PriceVisibility::subscriptionModes(),
            array_flip(['show_everywhere', 'enquiry_only', 'coming_soon'])
        ),
    ];
@endphp

@foreach(['Site','Pricing','Contact','Social Media','Developer'] as $v)
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
                                    {{ html()->textarea($fieldName)->value($setting->value)->class('form-control')->attributes(['rows'=>4, 'placeholder'=>$placeholder]) }}
                                @else
                                    <div class="form-control-plaintext">{{ $setting->value ?: 'N/A' }}</div>
                                @endif
                            @elseif($setting->input == 'select')
                                @if($canEditSettings)
                                    {!! html()->select($fieldName, $settingSelectOptions[$setting->key] ?? [])->value($setting->value)->class('form-control') !!}
                                @else
                                    <div class="form-control-plaintext">{{ ($settingSelectOptions[$setting->key] ?? [])[$setting->value] ?? $setting->value ?: 'N/A' }}</div>
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
