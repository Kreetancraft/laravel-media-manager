{{-- Stands in for the HOST application's layout. This package ships none by
     design, so the suite has to play the host. Test scaffolding, never published. --}}
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>{{ $title ?? 'Test' }}</title>@fluxAppearance</head>
<body>{{ $slot }}@fluxScripts</body>
</html>
