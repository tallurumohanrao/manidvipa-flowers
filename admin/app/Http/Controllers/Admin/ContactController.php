<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Contact;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Gate,View;

class ContactController extends Controller
{
    public function __construct(Contact $model)
    {
        $this->model = $model;
        $this->module = 'contacts';
        View::share ( 'module', $this->module );
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $email = $request->email ?? '';
        $mobile = $request->mobile ?? '';
        $query = $this->model::query();
        if ($request->filled('email')) {
            $query->where('email',$email);
        }
        if ($request->filled('mobile')) {
            $query->where('mobile',$mobile);
        }
        $query->orderByDesc('id');
        $perPage = $request->input('per_page') ?: config('PER_PAGE');
        $data = $query->paginate($perPage)->withQueryString();
        return view('admin.contacts.index', ['data' => $data]);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $model = $this->model::find($id);
            $result = $model->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
}
