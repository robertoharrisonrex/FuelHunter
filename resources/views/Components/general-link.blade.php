@props(['newClass' => 'text-indigo-600 hover:text-indigo-800 font-medium text-sm
            transition-colors duration-150'])


<a {{$attributes->merge(['class' => $newClass])}} >{{$slot}}</a>
