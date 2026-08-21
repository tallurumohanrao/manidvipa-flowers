<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionPlanRequest;
use App\Models\Admin\SubscriptionPlan;
use App\Traits\RedirectTrait;
use App\Traits\StoreImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Storage;
use Symfony\Component\HttpFoundation\Response;
use View;

class SubscriptionPlanController extends Controller
{
    use StoreImageTrait, RedirectTrait;

    public function __construct(SubscriptionPlan $model)
    {
        $this->model = $model;
        $this->module = 'subscriptionplans';
        View::share('module', $this->module);
        View::share('businessTypes', $this->businessTypes());
        View::share('subscriptionTypes', $this->subscriptionTypes());
        View::share('flowerGrades', $this->flowerGrades());
        View::share('billingCycles', $this->billingCycles());
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $query = $this->model::query();

        if ($request->filled('q')) {
            $query->where(function ($subQuery) use ($request) {
                $search = $request->q;
                $subQuery->where('title', 'like', '%'.$search.'%')
                    ->orWhere('business_type', 'like', '%'.$search.'%')
                    ->orWhere('subscription_type', 'like', '%'.$search.'%')
                    ->orWhere('flower_grade', 'like', '%'.$search.'%')
                    ->orWhere('flower_examples', 'like', '%'.$search.'%')
                    ->orWhere('short_description', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        if ($request->filled('subscription_type')) {
            $query->where('subscription_type', $request->subscription_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($request->input('per_page') ?: config('PER_PAGE'))
            ->withQueryString();

        return view('admin.'.$this->module.'.index', compact('data'));
    }

    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        return view('admin.'.$this->module.'.create', ['row' => []]);
    }

    public function store(StoreSubscriptionPlanRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $formInput = $this->prepareFormInput($request);
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        $plan = $this->model::create($formInput);
        $this->clearSubscriptionCache();

        return $this->redirectAfterSave($request->FormButton, $plan->id);
    }

    public function edit(SubscriptionPlan $subscriptionplan)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        return view('admin.'.$this->module.'.edit', ['row' => $subscriptionplan]);
    }

    public function update(StoreSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionplan)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $formInput = $this->prepareFormInput($request, $subscriptionplan);
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);

        if ($subscriptionplan->update($formInput) === true) {
            $this->clearSubscriptionCache();
            return $this->redirectAfterSave($request->FormButton, $subscriptionplan->id);
        }

        return redirect()->back()->withInput()->with('error', 'Unable to update subscription plan.');
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        if ($request->ajax() && $request->isMethod('PATCH')) {
            $plan = $this->model::findOrFail($id);

            if ($plan->update(['status' => $request->status])) {
                $this->clearSubscriptionCache();
                $status = $request->status == 1 ? 'enabled' : 'disabled';
                return response()->json(['status' => 'success', 'message' => "Status $status successfully."]);
            }
        }

        return response()->json(['status' => 'error', 'message' => 'Unable to update status.'], 422);
    }

    public function destroy(SubscriptionPlan $subscriptionplan)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        if ($subscriptionplan->image) {
            Storage::delete('public/'.$this->module.'/'.$subscriptionplan->image);
        }

        if ($subscriptionplan->delete() == 1) {
            $this->clearSubscriptionCache();
            return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'An unexpected error has occurred.']);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $ids = array_filter(explode(',', $request->ids));
        $result = 0;

        foreach ($ids as $id) {
            $model = $this->model::find($id);

            if (! $model) {
                continue;
            }

            if ($model->image) {
                Storage::delete('public/'.$this->module.'/'.$model->image);
            }

            $result = $model->delete();
        }

        if ($result == 1) {
            $this->clearSubscriptionCache();
            return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'An unexpected error has occurred.']);
    }

    private function prepareFormInput(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $formInput = $request->except(['FormButton', 'old_image']);
        $baseSlug = Str::slug($request->title);
        $slug = $baseSlug;
        $counter = 1;

        while (
            $this->model::where('slug', $slug)
                ->when($plan, fn ($query) => $query->where('id', '!=', $plan->id))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $formInput['slug'] = $slug;
        $formInput['subscription_type'] = $request->subscription_type ?: 'Premium Arrangements';
        $formInput['price_suffix'] = $request->price_suffix ?: '/ month';
        $formInput['cta_label'] = $request->cta_label ?: 'Request Plan';
        $formInput['sort_order'] = (int) ($request->sort_order ?: 0);
        $formInput['is_featured'] = (int) $request->is_featured;
        $formInput['status'] = (int) $request->status;

        return $formInput;
    }

    private function clearSubscriptionCache(): void
    {
        Cache::forget('api_subscription_plans');
        Cache::forget('api_subscription_plans_featured');
    }

    private function businessTypes(): array
    {
        return [
            'Corporate Offices' => 'Corporate Offices',
            'Hospitals' => 'Hospitals',
            'Hotels' => 'Hotels',
            'Temples' => 'Temples',
            'Business Bulk' => 'Business Bulk',
            'Events' => 'Events',
            'Home / Apartments' => 'Home / Apartments',
            'Custom' => 'Custom',
        ];
    }

    private function billingCycles(): array
    {
        return [
            'Daily' => 'Daily',
            'Weekly' => 'Weekly',
            'Monthly' => 'Monthly',
            'Quarterly' => 'Quarterly',
            'Custom' => 'Custom',
        ];
    }

    private function subscriptionTypes(): array
    {
        return [
            'Premium Arrangements' => 'Premium Arrangements',
            'Loose Flowers' => 'Loose Flowers',
            'Custom / Hybrid' => 'Custom / Hybrid',
        ];
    }

    private function flowerGrades(): array
    {
        return [
            'Standard Fresh' => 'Standard Fresh',
            'Premium' => 'Premium',
            'Standard / Premium' => 'Standard / Premium',
            'Premium / Imported Mix' => 'Premium / Imported Mix',
            'Imported / Exotic' => 'Imported / Exotic',
            'Fresh Daily' => 'Fresh Daily',
            'As per requirement' => 'As per requirement',
        ];
    }
}
