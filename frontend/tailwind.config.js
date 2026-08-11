/** @type {import('tailwindcss').Config} */
export default {
	content: ['./src/**/*.{html,js,svelte}'],
	darkMode: 'class',
	theme: {
		extend: {
			colors: {
				background: {
					light: '#f8fafc',
					DEFAULT: '#0f172a',
					dark: '#0f172a'
				},
				surface: {
					light: '#ffffff',
					dark: '#111827',
					border: '#e2e8f0'
				},
				border: {
					light: '#e2e8f0',
					dark: '#334155'
				},
				text: {
					main: {
						light: '#0f172a',
						dark: '#e2e8f0'
					},
					sec: {
						light: '#475569',
						dark: '#94a3b8'
					}
				},
				primary: {
					DEFAULT: '#137fec',
					dark: '#0f65bd',
					light: '#dbeafe'
				}
			},
			fontFamily: {
				display: ['Inter', 'sans-serif']
			},
			boxShadow: {
				primary: '0 10px 25px -5px rgba(99,102,241,0.15)',
			}
		}
	},
	plugins: []
};
