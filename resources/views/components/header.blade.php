@props(['src', 'title' => 'Fuel Hunter' ])


<head>
    <title>$title</title>
    @vite('resources/css/app.css')
    {{$slot}}
</head>
