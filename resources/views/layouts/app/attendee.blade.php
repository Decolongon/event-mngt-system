<!DOCTYPE html>
<html lang="en">
<head>
   @include('partials.head')
</head>
<body>
    {{ $slot }}
     @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
</body>
</html>