( function ( wp ) {
    const { createElement, render } = wp.element;
    const { useEffect, useState } = wp.element;
    const { apiFetch } = wp.apiFetch;

    const DashboardApp = () => {
        const [settings, setSettings] = useState(null);
        const [error, setError] = useState('');

        useEffect(() => {
            apiFetch({ path: '/avaei/v1/settings' })
                .then((response) => {
                    if (response.success) {
                        setSettings(response.data);
                    }
                })
                .catch(() => setError('Unable to load settings.'));
        }, []);

        if (error) {
            return createElement('div', { className: 'avaei-card' }, error);
        }

        return createElement(
            'div',
            null,
            createElement('div', { className: 'avaei-card' },
                createElement('h2', null, 'AspirantVeda Status'),
                settings ? createElement('p', null, `AI provider: ${settings.provider}`) : createElement('p', null, 'Loading...')
            )
        );
    };

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('avaei-dashboard-root');
        if (root) {
            render(createElement(DashboardApp), root);
        }
    });
} )( window.wp );
