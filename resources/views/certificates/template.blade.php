<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
        }
        .certificate-container {
            width: 100%;
            height: 100%;
            position: relative;
            background-image: url('{{ $backgroundUrl }}');
            background-size: cover;
            background-position: center;
        }
        .placeholder {
            position: absolute;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="certificate-container">
        @foreach($placeholders as $key => $position)
            @if($key === 'qr_code')
                <img 
                    src="{{ $data['qr_code_url'] }}" 
                    class="placeholder"
                    style="left: {{ $position['x'] }}px; top: {{ $position['y'] }}px; width: {{ $position['width'] }}px; height: {{ $position['height'] }}px;"
                />
            @else
                <div 
                    class="placeholder text-{{ $position['align'] ?? 'center' }}"
                    style="left: {{ $position['x'] }}px; top: {{ $position['y'] }}px; font-size: {{ $position['font_size'] ?? 14 }}px; font-family: '{{ $position['font'] ?? 'DejaVu Sans' }}';"
                >
                    {{ $data[$key] ?? '' }}
                </div>
            @endif
        @endforeach
    </div>
</body>
</html>
