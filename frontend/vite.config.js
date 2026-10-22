import { sveltekit } from '@sveltejs/kit/vite';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [tailwindcss(), sveltekit()],
	ssr: {
		noExternal: ['@xyflow/svelte', '@xyflow/system']
	},
	server: {
		allowedHosts: ['tareas.daemon-lab.duckdns.org', 'localhost'],
		watch: {
			// En Docker sobre Windows los cambios del host no llegan al contenedor como
			// eventos, así que hay que revisar los archivos periódicamente (VITE_USE_POLLING=true en .env)
			usePolling: process.env.VITE_USE_POLLING === 'true'
		}
	}
});
