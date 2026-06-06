@livewireScripts

<script data-navigate-once>
    (function () {
        function ensureLivewireStarted() {
            if (! window.Livewire || window.Livewire.initialRenderIsFinished) {
                return
            }

            window.Livewire.start()
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', ensureLivewireStarted)
        } else {
            ensureLivewireStarted()
        }
    })()
</script>
