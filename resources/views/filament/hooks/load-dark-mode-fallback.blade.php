@if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
    <script data-navigate-once>
        window.loadDarkMode = function () {
            window.theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)

            if (
                window.theme === 'dark' ||
                (window.theme === 'system' &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches)
            ) {
                document.documentElement.classList.add('dark')
            }
        }
    </script>
@endif
