import { cpSync, existsSync, mkdirSync, readFileSync, readdirSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { basename, join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';

const PLUGIN_SLUG = 's2j-media-library-date-corrector';

const repoRoot = resolve(process.cwd());
const pkgJsonPath = join(repoRoot, 'package.json');
const pkg = JSON.parse(readFileSync(pkgJsonPath, 'utf8'));
const version = (process.env.DIST_VERSION || pkg.version || '0.0.0').toString();

const distDir = join(repoRoot, 'dist');
if (!existsSync(distDir)) {
	throw new Error('dist/ not found. Run `npm run dist:build` first.');
}

const outDir = join(repoRoot, 'release');
const stageRoot = join(repoRoot, '.dist-tmp');
const stagePluginDir = join(stageRoot, PLUGIN_SLUG);
const zipPath = join(outDir, `${PLUGIN_SLUG}-${version}.zip`);

const ensureEmptyDir = (dir) => {
	rmSync(dir, { recursive: true, force: true });
	mkdirSync(dir, { recursive: true });
};

const copyTree = (src, dest) => {
	cpSync(src, dest, { recursive: true });
};

const removeJunkFilesRecursively = (dir) => {
	if (!existsSync(dir)) {
		return;
	}
	for (const entry of readdirSync(dir, { withFileTypes: true })) {
		const full = join(dir, entry.name);
		if (entry.isDirectory()) {
			removeJunkFilesRecursively(full);
			continue;
		}
		if (entry.name === '.DS_Store') {
			rmSync(full, { force: true });
		}
	}
};

const writeBuildMeta = () => {
	const meta = {
		plugin: PLUGIN_SLUG,
		version,
		builtAt: new Date().toISOString(),
		node: process.version,
	};
	writeFileSync(join(stagePluginDir, 'release-meta.json'), JSON.stringify(meta, null, 2) + '\n', 'utf8');
};

const mustExist = (relPath) => {
	const abs = join(repoRoot, relPath);
	if (!existsSync(abs)) {
		throw new Error(`Missing required file: ${relPath}`);
	}
	return abs;
};

const resolveReadmePath = () => {
	for (const name of ['readme.txt', 'README.txt']) {
		const abs = join(repoRoot, name);
		if (existsSync(abs)) {
			return abs;
		}
	}
	throw new Error('Missing required file: readme.txt');
};

ensureEmptyDir(outDir);
ensureEmptyDir(stagePluginDir);

copyTree(mustExist('includes'), join(stagePluginDir, 'includes'));
copyTree(mustExist('dist'), join(stagePluginDir, 'dist'));

cpSync(mustExist(`${PLUGIN_SLUG}.php`), join(stagePluginDir, `${PLUGIN_SLUG}.php`));
cpSync(mustExist('uninstall.php'), join(stagePluginDir, 'uninstall.php'));
cpSync(resolveReadmePath(), join(stagePluginDir, 'readme.txt'));
cpSync(mustExist('LICENSE'), join(stagePluginDir, 'LICENSE'));

const languagesDir = join(repoRoot, 'languages');
if (existsSync(languagesDir)) {
	copyTree(languagesDir, join(stagePluginDir, 'languages'));
}

removeJunkFilesRecursively(stagePluginDir);
writeBuildMeta();

rmSync(zipPath, { force: true });

const zip = spawnSync('zip', ['-r', zipPath, basename(stagePluginDir)], {
	cwd: stageRoot,
	stdio: 'inherit',
});

if (zip.status !== 0) {
	throw new Error('zip command failed. Ensure `zip` is available in your environment.');
}

rmSync(stageRoot, { recursive: true, force: true });

const zipStat = statSync(zipPath);
process.stdout.write(`Created: ${zipPath} (${zipStat.size} bytes)\n`);
