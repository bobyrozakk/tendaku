@props(['documents'])

<template x-teleport="body">
    <div class="footer-dialog"
         x-show="doc !== null"
         x-effect="document.body.classList.toggle('overflow-y-hidden', doc !== null); doc !== null && $nextTick(() => $refs['panel-' + doc] && $refs['panel-' + doc].focus())"
         x-on:keydown.escape.window="doc !== null && close()"
         x-transition.opacity.duration.200ms
         style="display: none;">
        <div class="footer-dialog__overlay" x-on:click="close()"></div>

        @foreach ($documents as $key => $doc)
            <section class="footer-dialog__panel"
                     x-ref="panel-{{ $key }}"
                     x-show="doc === '{{ $key }}'"
                     x-transition:enter="footer-dialog__enter"
                     x-transition:enter-start="footer-dialog__enter-start"
                     x-transition:enter-end="footer-dialog__enter-end"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="footer-dialog-{{ $key }}"
                     tabindex="-1">
                <header class="footer-dialog__header">
                    <h2 id="footer-dialog-{{ $key }}" class="footer-dialog__title">{{ $doc['title'] }}</h2>
                    <button type="button" class="footer-dialog__close" aria-label="Tutup" x-on:click="close()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </header>

                <div class="footer-dialog__body">
                    @isset($doc['intro'])
                        <div class="footer-dialog__callout">
                            <p class="footer-dialog__callout-text">{{ $doc['intro'] }}</p>
                        </div>
                    @endisset

                    <div class="footer-dialog__sections">
                        @foreach ($doc['sections'] as $index => $section)
                            @php
                                $num = sprintf('%02d', $index + 1);
                                $heading = preg_replace('/^\d+\.\s*/', '', $section['heading']);
                            @endphp
                            <div class="footer-dialog__section">
                                <div class="footer-dialog__section-header">
                                    <span class="footer-dialog__badge" aria-hidden="true">{{ $num }}</span>
                                    <h3 class="footer-dialog__heading">{{ $heading }}</h3>
                                </div>
                                <div class="footer-dialog__content">
                                    @foreach ($section['body'] as $paragraph)
                                        <p>{{ $paragraph }}</p>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endforeach
    </div>
</template>
