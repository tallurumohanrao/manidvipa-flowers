<table class="table table-bordered  table-hover tablegrid">
<thead>
    <tr role="row">
        <th>#</th>
        <th>Name</th>
        <th>Email</th>
        <th>Comment</th>
        <th>Publish</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>
</thead>

<tbody>
@foreach($row->comments as $comment)
    <tr id="row-{{ $comment->id }}">
        <td>{{ $loop->iteration }}</td>
        <td>{{$comment->name}}</td>
        <td>{{$comment->email}}</td>
        <td>{!! $comment->message !!}</td>
        <td>
            <label class="switch">
            {{ html()->checkbox('status', $comment->is_visable, null)->class('status')->id('status_'.$comment->id)->attributes(['data-id'=>$comment->id,'data-url'=>route('admin.posts.comments.update.status',['id'=>$comment->id])]) }}
            <span class="slider round"></span>
            </label>
        </td>
        <td>{{$comment->created_at}}</td>
        <td>
            <div class="btn-group">
                <a href="javascript:;" class="delete btn btn-danger" data-id="{{ $comment->id }}" data-url="{{ route('admin.posts.comments.destroy',$comment) }}"><i class="fas fa-trash"></i></a>
            </div>
        </td>
    </tr>
@endforeach
</tbody>
</table>
