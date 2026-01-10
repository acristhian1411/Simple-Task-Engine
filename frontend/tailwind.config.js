/** @type {import('tailwindcss').Config} */
export default {
	content: ['./src/**/*.{html,js,svelte}'],
	darkMode: 'class',
	theme: {
		extend: {
			colors: {
				background: {
					DEFAULT: '#707e8dff',
					primary: '#7f9ec6ff',
					dark: '#0f172a'
				},
				primary: {
					DEFAULT: '#137fec',
					dark: '#0f65bd'
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
