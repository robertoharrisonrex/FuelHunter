@props(['newClass' => 'inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold
            text-white bg-indigo-600 hover:bg-indigo-500 border border-transparent
            focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1
            active:scale-95 transition-all duration-150'])


<button {{$attributes->merge(['class' => $newClass])}} >{{$slot}}</button>
