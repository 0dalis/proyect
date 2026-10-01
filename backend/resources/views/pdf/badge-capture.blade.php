<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; background: #ffffff; }
        .face { page-break-after: always; }
        .face.last { page-break-after: auto; }
        .face img { display: block; width: {{ $width }}pt; height: {{ $height }}pt; }
    </style>
</head>
<body>
    <div class="face"><img src="{{ $front }}" alt="Frente"></div>
    <div class="face last"><img src="{{ $back }}" alt="Reverso"></div>
</body>
</html>
