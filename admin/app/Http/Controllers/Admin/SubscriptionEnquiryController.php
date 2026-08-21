<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SubscriptionEnquiry;
use App\Traits\RedirectTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use View;

class SubscriptionEnquiryController extends Controller
{
    use RedirectTrait;

    public function __construct(SubscriptionEnquiry $model)
    {
        $this->model = $model;
        $this->module = 'subscriptionenquiries';
        View::share('module', $this->module);
        View::share('enquiryStatuses', $this->enquiryStatuses());
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $query = $this->model::with('plan');

        if ($request->filled('q')) {
            $query->where(function ($subQuery) use ($request) {
                $search = $request->q;
                $subQuery->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('organization_name', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query
            ->orderByDesc('id')
            ->paginate($request->input('per_page') ?: config('PER_PAGE'))
            ->withQueryString();

        return view('admin.'.$this->module.'.index', compact('data'));
    }

    public function show(SubscriptionEnquiry $subscriptionenquiry)
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        return view('admin.'.$this->module.'.show', ['row' => $subscriptionenquiry->load('plan')]);
    }

    public function edit(SubscriptionEnquiry $subscriptionenquiry)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        return view('admin.'.$this->module.'.edit', ['row' => $subscriptionenquiry->load('plan')]);
    }

    public function update(Request $request, SubscriptionEnquiry $subscriptionenquiry)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $request->validate([
            'status' => 'required|string|max:40',
            'admin_notes' => 'nullable|string',
        ]);

        $subscriptionenquiry->update($request->only(['status', 'admin_notes']));

        return redirect()
            ->route('admin.'.$this->module.'.show', $subscriptionenquiry)
            ->with('success', 'Enquiry updated successfully.');
    }

    public function destroy(SubscriptionEnquiry $subscriptionenquiry)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        if ($subscriptionenquiry->delete() == 1) {
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

            $result = $model->delete();
        }

        if ($result == 1) {
            return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'An unexpected error has occurred.']);
    }

    private function enquiryStatuses(): array
    {
        return [
            'New' => 'New',
            'Contacted' => 'Contacted',
            'Quote Sent' => 'Quote Sent',
            'Converted' => 'Converted',
            'Closed' => 'Closed',
        ];
    }
}
