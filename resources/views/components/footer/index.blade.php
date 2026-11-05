@php
    $f = config('footer');

    // "/katalog" -> url('/katalog'); "#", "tel:", "https://..." dibiarkan apa adanya.
    $href = fn (string $u) => str_starts_with($u, '/') ? url($u) : $u;
    $ext  = fn (string $u) => str_starts_with($u, 'http') ? 'target="_blank" rel="noopener noreferrer"' : '';
@endphp

<footer class="site-footer" style="--footer-photo: url('{{ asset($f['image']) }}')">
    <div class="site-footer__inner">

        <div class="site-footer__grid">

            {{-- Brand --}}
            <section class="site-footer__brand" aria-label="Tentang Tendaku">
                <div class="site-footer__head">
                    <a href="{{ url('/') }}" class="site-footer__logo" aria-label="{{ $f['brand']['name'] }}">
                        <img src="{{ asset($f['logo']) }}" alt="{{ $f['brand']['name'] }}">
                    </a>
                </div>
                <p class="site-footer__slogan">{{ $f['brand']['slogan'] }}</p>
                <p class="site-footer__desc">{{ $f['brand']['description'] }}</p>

                <ul class="site-footer__social">
                    @foreach ($f['social'] as $s)
                        <li>
                            <a href="{{ $href($s['url']) }}" aria-label="{{ $s['label'] }}" {!! $ext($s['url']) !!}>
                                <x-footer.icon :name="$s['icon']" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Kolom link --}}
            @foreach ($f['columns'] as $col)
                <nav class="site-footer__col" aria-label="{{ $col['title'] }}">
                    <div class="site-footer__head"><h4 class="site-footer__title">{{ $col['title'] }}</h4></div>
                    <ul class="site-footer__list">
                        @foreach ($col['links'] as $link)
                            <li><a href="{{ $href($link['url']) }}" {!! $ext($link['url']) !!}>{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach

            {{-- Kontak --}}
            <section class="site-footer__col site-footer__contact" aria-label="{{ $f['contact']['title'] }}">
                <div class="site-footer__head"><h4 class="site-footer__title">{{ $f['contact']['title'] }}</h4></div>
                <ul class="site-footer__list">
                    @foreach ($f['contact']['items'] as $item)
                        <li>
                            <x-footer.icon :name="$item['icon']" class="site-footer__icon" />
                            <span>
                                @isset($item['url'])
                                    <a href="{{ $item['url'] }}">{{ $item['text'] }}</a>
                                @else
                                    {{ $item['text'] }}
                                @endisset
                                @isset($item['sub'])
                                    <small>{{ $item['sub'] }}</small>
                                @endisset
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- Bar bawah --}}
        <div class="site-footer__bottom">
            <p>&copy; {{ date('Y') }} {{ $f['brand']['name'] }}. Seluruh hak cipta dilindungi.</p>
            <nav class="site-footer__legal" aria-label="Legal">
                @foreach ($f['legal'] as $l)
                    <a href="{{ $href($l['url']) }}">{{ $l['label'] }}</a>
                @endforeach
            </nav>
        </div>

    </div>
</footer>
