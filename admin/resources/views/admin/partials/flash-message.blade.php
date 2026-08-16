@if ($errors->any())
    Message.add('{!! collect($errors->all())->implode('</br>') !!}', {type: 'error',sticky: true});
@endif

@if ($message = Session::get('success'))
Message.add('{{ $message }}', {type: 'success'});
@endif

@if ($message = Session::get('error'))
Message.add('<strong>Error:</strong> {{ $message }}',{type:'error'});
@endif

@if ($message = Session::get('warning'))
Message.add('<strong>Warning:</strong> {{ $message }}',{type:'warning'});
@endif

@if ($message = Session::get('info'))
Message.add('<strong>Info:</strong> {{ $message }}',{type:'info'});
@endif
