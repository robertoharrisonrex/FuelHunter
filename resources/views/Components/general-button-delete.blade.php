@props(['newClass' => 'inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold
            text-red-600 bg-red-50 hover:bg-red-100 border border-red-200
            focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1
            active:scale-95 transition-all duration-150'])


<button {{$attributes->merge(['class' => $newClass])}} >{{$slot}}</button>
