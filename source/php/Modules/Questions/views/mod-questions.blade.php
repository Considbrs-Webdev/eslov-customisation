@if (!$rekaiEnabled)
    @if (!empty($showEditorNotice))
        <p class="eslov-rekai-questions__notice">{{ $notice }}</p>
    @endif
@elseif (!empty($editorPreview))
    <div class="eslov-rekai-questions">
        @if (empty($hideTitle) && !empty($postTitle))
            @typography([
                'element' => 'h4',
                'variant' => 'h2',
                'classList' => ['module-title']
            ])
                {{ $postTitle }}
            @endtypography
        @endif
        <p class="eslov-rekai-questions__notice">{{ $editorHint }}</p>
    </div>
@else
    <div
        id="{{ $mountId }}"
        class="eslov-rekai-questions"
        data-read-more="{{ $readMoreLabel }}"
    >
        <div class="eslov-rekai-questions__body" hidden>
            @if (empty($hideTitle) && !empty($postTitle))
                @typography([
                    'element' => 'h4',
                    'variant' => 'h2',
                    'classList' => ['module-title']
                ])
                    {{ $postTitle }}
                @endtypography
            @endif

            @accordion([
                'divider' => true,
                'attributeList' => [
                    'data-rekai-questions-list' => '',
                ],
            ])
            @endaccordion
        </div>

        <template id="{{ $mountId }}-template">
            @accordion__item([
                'heading' => 'QUESTION_HEADING',
            ])
                <p class="eslov-rekai-questions__answer">ANSWER_BODY</p>
                <p class="eslov-rekai-questions__more" hidden>
                    <a class="eslov-rekai-questions__link" href=""></a>
                </p>
            @endaccordion__item
        </template>

        <script>
            (function () {
                var mountId = {!! wp_json_encode($mountId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
                var params = {!! wp_json_encode($predictParams, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
                var root = document.getElementById(mountId);

                if (!root) {
                    return;
                }

                var template = document.getElementById(mountId + '-template');
                var list = root.querySelector('[data-rekai-questions-list]');
                var body = root.querySelector('.eslov-rekai-questions__body');
                var readMoreLabel = root.getAttribute('data-read-more') || '';

                function removeRoot() {
                    root.remove();
                }

                function isOtherPage(url) {
                    if (typeof url !== 'string' || !/^https?:\/\//i.test(url)) {
                        return false;
                    }

                    try {
                        var parsed = new URL(url);
                        var current = window.location.pathname.replace(/\/+$/, '') || '/';
                        var next = parsed.pathname.replace(/\/+$/, '') || '/';

                        return next !== current;
                    } catch (error) {
                        return false;
                    }
                }

                function render(data) {
                    var predictions = data && Array.isArray(data.predictions) ? data.predictions : [];
                    var added = 0;

                    if (!template || !list || !body) {
                        removeRoot();
                        return;
                    }

                    predictions.forEach(function (item) {
                        if (!item || typeof item.question !== 'string' || item.question.trim() === '') {
                            return;
                        }

                        var fragment = template.content.cloneNode(true);
                        var heading = fragment.querySelector('[role="heading"]');
                        var answer = fragment.querySelector('.eslov-rekai-questions__answer');
                        var more = fragment.querySelector('.eslov-rekai-questions__more');
                        var link = fragment.querySelector('.eslov-rekai-questions__link');

                        if (heading) {
                            heading.textContent = item.question;
                        }

                        if (answer) {
                            answer.textContent = typeof item.answer === 'string' ? item.answer : '';
                        }

                        if (more && link && isOtherPage(item.url)) {
                            link.textContent = readMoreLabel;
                            link.href = item.url;
                            more.hidden = false;
                        }

                        list.appendChild(fragment);
                        added += 1;
                    });

                    if (!added) {
                        removeRoot();
                        return;
                    }

                    body.hidden = false;
                }

                function start() {
                    if (!window.__rekai || typeof window.__rekai.predict !== 'function') {
                        removeRoot();
                        return;
                    }

                    try {
                        window.__rekai.predict({ params: params }, function (data) {
                            try {
                                render(data);
                            } catch (error) {
                                removeRoot();
                            }
                        });
                    } catch (error) {
                        removeRoot();
                    }
                }

                function boot() {
                    if (window.__rekai) {
                        start();
                        return;
                    }

                    window.addEventListener('rekai.load', start, { once: true });

                    var script = document.querySelector('script[src*="static.rekai.se"]');

                    if (!script) {
                        removeRoot();
                        return;
                    }

                    script.addEventListener('error', removeRoot, { once: true });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', boot, { once: true });
                } else {
                    boot();
                }
            })();
        </script>
    </div>
@endif
