<div
    class="no-print fixed right-3 bottom-[max(0.75rem,env(safe-area-inset-bottom))] z-50 w-[min(22rem,calc(100vw-1.5rem))] sm:right-5"
    x-data="{
        open: false,
        sending: false,
        message: '',
        messages: [{ role: 'bot', text: @js(app(App\Services\JournalChatbot::class)->greeting()), links: [] }],
        suggestions: @js(app(App\Services\JournalChatbot::class)->suggestions()),
        endpoint: @js(route('chatbot')),
        csrf: document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '',
        async send(text = null) {
            const value = (text ?? this.message).trim();
            if (! value || this.sending) return;
            this.message = '';
            this.messages.push({ role: 'user', text: value, links: [] });
            this.sending = true;
            this.$nextTick(() => this.scroll());
            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ message: value }),
                });
                const data = await response.json();
                if (! response.ok) {
                    throw new Error(data.message || 'unavailable');
                }
                this.messages.push({ role: 'bot', text: data.reply, links: data.links || [] });
                if (Array.isArray(data.suggestions) && data.suggestions.length) {
                    this.suggestions = data.suggestions;
                }
            } catch (error) {
                this.messages.push({
                    role: 'bot',
                    text: @js(__('ui.chatbot_error')),
                    links: [],
                });
            } finally {
                this.sending = false;
                this.$nextTick(() => {
                    this.scroll();
                    window.renderIcons?.();
                });
            }
        },
        scroll() {
            this.$refs.thread?.scrollTo({ top: this.$refs.thread.scrollHeight, behavior: 'smooth' });
        },
    }"
>
    <div
        x-show="open"
        x-cloak
        x-transition
        class="mb-3 flex max-h-[min(70vh,28rem)] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
    >
        <div class="flex items-center justify-between gap-3 bg-brand-950 px-4 py-3 text-white">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ __('ui.chatbot_title') }}</p>
                <p class="truncate text-xs text-slate-300">{{ __('ui.chatbot_subtitle') }}</p>
            </div>
            <button type="button" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-md hover:bg-white/10" @click="open = false" :aria-label="@js(__('ui.close'))">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>

        <div x-ref="thread" class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain px-3 py-3 text-sm">
            <template x-for="(item, index) in messages" :key="index">
                <div :class="item.role === 'user' ? 'ml-8 text-right' : 'mr-8'">
                    <p
                        class="inline-block rounded-2xl px-3 py-2 leading-6"
                        :class="item.role === 'user' ? 'bg-brand-900 text-white' : 'bg-slate-100 text-slate-800'"
                        x-text="item.text"
                    ></p>
                    <template x-if="item.links && item.links.length">
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="link in item.links" :key="link.url">
                                <a :href="link.url" class="inline-flex rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200" x-text="link.label"></a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
            <p x-show="sending" class="text-xs text-slate-500">{{ __('ui.chatbot_thinking') }}</p>
        </div>

        <div class="border-t border-slate-100 px-3 py-2">
            <div class="mb-2 flex flex-wrap gap-1.5">
                <template x-for="suggestion in suggestions" :key="suggestion">
                    <button type="button" class="rounded-full border border-slate-200 px-2.5 py-1 text-left text-[11px] font-medium text-slate-600 hover:bg-slate-50" @click="send(suggestion)" x-text="suggestion"></button>
                </template>
            </div>
            <form class="flex items-end gap-2" @submit.prevent="send()">
                <label class="sr-only" for="journal-chatbot-input">{{ __('ui.chatbot_placeholder') }}</label>
                <textarea
                    id="journal-chatbot-input"
                    rows="1"
                    maxlength="500"
                    x-model="message"
                    :disabled="sending"
                    class="min-h-11 w-full resize-none rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900"
                    placeholder="{{ __('ui.chatbot_placeholder') }}"
                ></textarea>
                <button type="submit" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl bg-brand-900 text-white hover:bg-brand-800 disabled:opacity-60" :disabled="sending" :aria-label="@js(__('ui.chatbot_send'))">
                    <x-icon name="send" class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>

    <button
        type="button"
        class="ml-auto flex min-h-12 items-center gap-2 rounded-full bg-brand-900 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-brand-800"
        @click="open = !open; $nextTick(() => { if (open) { scroll(); window.renderIcons?.(); } })"
        :aria-expanded="open.toString()"
        aria-controls="journal-chatbot-input"
    >
        <x-icon name="message-circle" class="h-5 w-5" />
        <span x-text="open ? @js(__('ui.close')) : @js(__('ui.chatbot_open'))"></span>
    </button>
</div>
