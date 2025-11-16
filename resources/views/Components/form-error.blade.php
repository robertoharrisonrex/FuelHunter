

@props(['name'])

    @error($name)
    <p class="text-sm text-red-800"> {{$message}} </p>
    @enderror
