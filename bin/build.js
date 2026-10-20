import * as esbuild from 'esbuild'
import fs from 'node:fs'
import path from 'node:path'
import { pathToFileURL } from 'node:url'

const isDev = process.argv.includes('--dev')
const dist = './resources/dist'

async function compile(options) {
    const context = await esbuild.context(options)

    if (isDev) {
        await context.watch()
    } else {
        await context.rebuild()
        await context.dispose()
    }
}

const log = {
    name: 'log',
    setup(build) {
        build.onStart(() => {
            console.log(`Build started at ${new Date().toLocaleTimeString()}: ${build.initialOptions.outfile}`)
        })

        build.onEnd((result) => {
            const status = result.errors.length ? 'failed' : 'finished'

            console.log(`Build ${status} at ${new Date().toLocaleTimeString()}: ${build.initialOptions.outfile}`, ...result.errors)
        })
    },
}

/**
 * Each flatpickr locale as JSON, which the server sends with the field so only its language is loaded. JSON can't hold
 * the function that gives a day's suffix ("1st", "1er"), so the suffixes of days 1 to 31 are worked out here.
 */
async function buildLocales() {
    const source = './node_modules/flatpickr/dist/esm/l10n'
    const target = `${dist}/locales`

    fs.rmSync(target, { recursive: true, force: true })
    fs.mkdirSync(target, { recursive: true })

    for (const file of fs.readdirSync(source)) {
        if (!file.endsWith('.js') || ['index.js', 'default.js'].includes(file)) {
            continue
        }

        const module = await import(pathToFileURL(path.resolve(source, file)).href)
        const [locale] = Object.entries(module).filter(([name]) => name !== 'default').map(([, value]) => value)
        const { ordinal, ...json } = locale

        if (typeof ordinal === 'function') {
            json.ordinals = Array.from({ length: 31 }, (_, index) => ordinal(index + 1))
        }

        const key = file.replace(/\.js$/, '').toLowerCase().replace(/-/g, '_')

        fs.writeFileSync(`${target}/${key}.json`, `${JSON.stringify(json)}\n`)
    }
}

const options = {
    define: {
        'process.env.NODE_ENV': isDev ? `'development'` : `'production'`,
    },
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    sourcemap: isDev ? 'inline' : false,
    sourcesContent: isDev,
    treeShaking: true,
    target: ['es2020'],
    minify: !isDev,
    plugins: [log],
}

await buildLocales()

await Promise.all([
    compile({
        ...options,
        entryPoints: ['./resources/js/index.js'],
        outfile: `${dist}/filament-flatpickr.js`,
    }),
    // Flatpickr's stylesheet, the month picker's, the Filament theme and the field, in one file.
    compile({
        ...options,
        entryPoints: ['./resources/css/index.css'],
        outfile: `${dist}/filament-flatpickr.css`,
    }),
])
