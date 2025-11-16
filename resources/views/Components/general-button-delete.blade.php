@props(['newClass' => 'relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium
            text-black bg-white hover:bg-red-50 hover:border-red-500 border border-gray-300 rounded leading-5
            hover:text-red-500 focus:z-10 focus:outline-none focus:ring ring-gray-300
            focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150'])


<button {{$attributes->merge(['class' => $newClass])}} >{{$slot}}</button>
