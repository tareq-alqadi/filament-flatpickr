import { defineConfig } from 'vitest/config';

export default defineConfig({
  resolve: {
    // The ESM build, as esbuild bundles it: one copy of flatpickr, whose locale patterns the field relies on.
    alias: [{ find: /^flatpickr$/, replacement: 'flatpickr/dist/esm/index.js' }],
  },
  test: {
    environment: 'happy-dom',
    include: ['tests/js/**/*.test.js'],
  },
});
