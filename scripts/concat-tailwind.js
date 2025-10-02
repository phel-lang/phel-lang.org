const fs = require('fs');
const path = require('path');

const projectRoot = __dirname + '/..';
const outFile = path.join(projectRoot, 'css', 'tailwind.entry.css');

// Define concatenation order (top → bottom)
const parts = [
  'css/theme.css',
  'css/base.css',
  'css/components/footer.css',
  'css/components/header.css',
  'css/components/navigation.css',
  'css/components/layout.css',
  'css/components/search.css',
  'css/components/documentation.css',
  'css/components/blog.css',
  'css/components/dark-mode.css',
  'css/components/performance.css',
];

// Check if we're in watch mode
const isWatchMode = process.argv.includes('--watch');
const logPrefix = isWatchMode ? '[concat-tailwind-watch]' : '[concat-tailwind]';

function concatenateFiles() {
  let output = '';
  for (const rel of parts) {
    const abs = path.join(projectRoot, rel);
    if (!fs.existsSync(abs)) {
      console.error(`${logPrefix} Missing file: ${rel}`);
      process.exit(1);
    }
    output += `/* === ${rel} === */\n` + fs.readFileSync(abs, 'utf8') + '\n\n';
  }

  fs.writeFileSync(outFile, output, 'utf8');
  
  if (isWatchMode) {
    console.log(`${logPrefix} Wrote ${outFile} at ${new Date().toLocaleTimeString()}`);
  } else {
    console.log(`${logPrefix} Wrote ${outFile}`);
  }
}

concatenateFiles();

if (isWatchMode) {
  console.log(`${logPrefix} Watching for changes...`);
  
  const watchedFiles = parts.map(rel => path.join(projectRoot, rel));
  
  watchedFiles.forEach(filePath => {
    fs.watchFile(filePath, { interval: 100 }, (curr, prev) => {
      if (curr.mtime !== prev.mtime) {
        console.log(`${logPrefix} File changed: ${path.relative(projectRoot, filePath)}`);
        concatenateFiles();
      }
    });
  });

  // Handle cleanup on exit
  process.on('SIGINT', () => {
    console.log(`\n${logPrefix} Stopping file watcher...`);
    watchedFiles.forEach(filePath => {
      fs.unwatchFile(filePath);
    });
    process.exit(0);
  });
}
