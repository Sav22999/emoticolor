import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const packageJsonPath = path.join(__dirname, 'package.json')
const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'))

const isDev = process.argv.includes('--dev')

// Estraiamo la versione base e l'eventuale build number
// Formato atteso: X.Y.Z o X.Y.Z#build
const versionMatch = packageJson.version.match(/^(\d+\.\d+\.\d+)(#(\d+))?$/)

if (!versionMatch) {
  console.error(`Versione non valida nel package.json: ${packageJson.version}`)
  process.exit(1)
}

let versionBase = versionMatch[1]
let buildNum = parseInt(versionMatch[3] || '0')

if (isDev) {
  // In dev: incrementa solo il build number, lascia invariata la versione base (X.Y.Z)
  buildNum++
  packageJson.version = `${versionBase}#${buildNum}`
} else {
  // In build: incrementa la Z della versione base e rimuovi il build number
  let parts = versionBase.split('.')
  parts[2] = parseInt(parts[2]) + 1
  packageJson.version = parts.join('.')
}

fs.writeFileSync(packageJsonPath, JSON.stringify(packageJson, null, 2) + '\n')
console.log(`Version updated to: ${packageJson.version} (${isDev ? 'dev mode' : 'build mode'})`)
