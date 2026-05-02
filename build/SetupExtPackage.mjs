// @package     SimplySmart-IT Dev-Utils
// @link        https://simplysmart-it.de
// @copyright   (C) 2024 - 2025, SimplySmart-IT - Martina Scholz <https://simplysmart-it.de>. All rights reserved.

import fs from "fs";
import { exec, execSync } from "child_process";

// DO NOT DELETE THIS FILE
// This file is used by build system to build a clean extension install package.

const __dirname = import.meta.dirname;

function prepareExtension(ext, sourceObj, config) {
	fs.mkdirSync(__dirname + `/tmp/${ext.name}`, { recursive: true });

	if (fs.existsSync(__dirname + `/tmp/${ext.name}`)) {
		// Prepare the extensions
        if (Array.isArray(config.dev.extension) && config.dev.extension.length > 0) {
		    fs.cpSync(__dirname + `/../${ext.name}`, __dirname + `/tmp/${ext.name}`, { recursive: true });
        } else {
            fs.cpSync(__dirname + `/../${config.dev.extension}`, __dirname + `/tmp/${ext.name}`, { recursive: true });
            if (config.dev.media && fs.existsSync(__dirname + `/../${config.dev.media}`)) {
                fs.cpSync(__dirname + `/../${config.dev.media}`, __dirname + `/tmp/${ext.name}/media`, { recursive: true });
            }
            if (fs.existsSync(__dirname + "/../README.md")) {
                fs.copyFileSync(__dirname + "/../README.md", __dirname + `/tmp/${ext.name}/README.md`);
            }
        }
		sourceObj.name = ext.name;
		sourceObj.version = ext.version;
		fs.writeFileSync(__dirname + `/tmp/${ext.name}/package.json`, Buffer.from(JSON.stringify(sourceObj, null, 2), "utf-8") );
		fs.copyFileSync(__dirname + "/.npmignore", __dirname + `/tmp/${ext.name}/.npmignore`);
		if (fs.existsSync(__dirname + `/tmp/${ext.name}/media/scss`)) {
            execSync(`sass --no-source-map --style=expanded --update ${__dirname}/tmp/${ext.name}/media/scss:${__dirname}/tmp/${ext.name}/media/css`, { stdio: "inherit" })
            // Prefixer
            execSync(`postcss --use autoprefixer -b 'defaults' --no-map --replace ${__dirname}/tmp/${ext.name}/media/css/*.css`, { stdio: "inherit" })
			// Minify
            execSync(`cleancss --output ${__dirname}/tmp/${ext.name}/media/css/ --batch --batch-suffix \".min\" \"${__dirname}/tmp/${ext.name}/media/css/*.css\"`, { stdio: "inherit" });
            // Banner
            execSync(`esbuild "${__dirname}/tmp/${ext.name}/media/css/**/*.css" --allow-overwrite --outdir="${__dirname}/tmp/${ext.name}/media/css" --banner:css=\"/**\n * @package   ${ext.name}  v${ext.version}\n * @copyright   ${config.build.bannercopyright}\n */\n\"`, { stdio: "inherit" });
		}
        if (fs.existsSync(__dirname + `/tmp/${ext.name}/media/js`)) {
            // Banner
			execSync(`esbuild "${__dirname}/tmp/${ext.name}/media/js/**/*.js" --allow-overwrite --outdir="${__dirname}/tmp/${ext.name}/media/js" --banner:js=\"// @package     ${ext.name}  v${ext.version}\n// @copyright   ${config.build.bannercopyright}\n\n\"`, { stdio: "inherit" });
            // Build esm
            if (fs.existsSync(__dirname + `/tmp/${ext.name}/media/js/ems`)) {
                execSync(`esbuild ${__dirname}/tmp/${ext.name}/media/js/ems/**/*.js --bundle --sourcemap --allow-overwrite --outdir=${__dirname}/tmp/${ext.name}/media/js --log-level=info --color=true`, { stdio: "inherit" });
            }
            // Minify
			execSync(`esbuild "${__dirname}/tmp/${ext.name}/media/js/**/*.js" --minify --entry-names=[name].min --allow-overwrite --outdir=${__dirname}/tmp/${ext.name}/media/js --analyze --color=true`, { stdio: "inherit" });


		}
        if (fs.existsSync(__dirname + `/tmp/${ext.name}/media`)) {
            // Compress
            execSync(`gzipper compress ${__dirname}/tmp/${ext.name}/media --gzip --verbose --remove-larger --include js,css`, { stdio: "inherit" })
        }
		if (fs.existsSync(__dirname + `/tmp/${ext.name}/admin/script.php`)) {
			fs.renameSync(__dirname + `/tmp/${ext.name}/admin/script.php`, __dirname + `/tmp/${ext.name}/script.php`);
		}
		if (fs.existsSync(__dirname + `/tmp/${ext.name}/admin/${ext.name.replace(/^com_/g, '')}.xml`)) {
			fs.renameSync(__dirname + `/tmp/${ext.name}/admin/${ext.name.replace(/^com_/g, '')}.xml`, __dirname + `/tmp/${ext.name}/${ext.name.replace(/^com_/g, '')}.xml`);
		}
		if (fs.existsSync(__dirname + "/../LICENSE.md")) {
			fs.copyFileSync(__dirname + "/../LICENSE.md", __dirname + `/tmp/${ext.name}/LICENSE.txt`);
		}

		// Build the extensions only here if we are in a monorepo with multiple extensions
        if (config.dev.extension && Array.isArray(config.dev.extension) && config.dev.extension.length > 0) {
            execSync('npm pack', { cwd: `${__dirname}/tmp/${ext.name}`, stdio: 'inherit' });
            fs.renameSync(`${__dirname}/tmp/${ext.name}/${ext.name}-${ext.version}.zip`, `${__dirname}/tmp/${ext.name}.zip`, { recursive: true });
            fs.rmSync(__dirname + `/tmp/${ext.name}`, { recursive: true, force: true });
        };
	}
}

function main() {
    const source = fs.readFileSync(__dirname + "/../package.json").toString('utf-8');
    const sourceObj = JSON.parse(source);
    sourceObj.scripts = {};
    sourceObj.scripts = {
        "postpack": "tarball=$(npm list --depth 0 | sed 's/@/-/g; s/ .*/.tgz/g; 1q;'); tar -tf $tarball | sed 's/^package\\///' | zip -@r ${npm_package_name}-${npm_package_version}.zip -x package.json; rm $tarball"
    };
    sourceObj.devDependencies = {};
    const config = sourceObj.config;
    sourceObj.config = {};
    sourceObj.overrides = {};

    // Remove tmp folder if exists
    if (fs.existsSync(__dirname + "/tmp")) {
        fs.rmSync(__dirname + "/tmp", { recursive: true, force: true });
    }

    // ensure tmp folder exists
    fs.mkdirSync(__dirname + "/tmp", { recursive: true });

    fs.writeFileSync(__dirname + "/tmp/package.json", Buffer.from(JSON.stringify(sourceObj, null, 2), "utf-8") );

    // Build all extensions and the package in a monorepo
    if (config.dev.extension && Array.isArray(config.dev.extension) && config.dev.extension.length > 0) {
        config.dev.extension.forEach((ext) => {
            prepareExtension(ext, sourceObj, config);
        });

        // Prepare the package
        if (fs.existsSync(__dirname + `/../package`)) {
            fs.cpSync(__dirname + `/../package`, __dirname + `/tmp`, { recursive: true });

            fs.copyFileSync(__dirname + "/.npmignore", __dirname + "/tmp/.npmignore");

            if (fs.existsSync(__dirname + "/../LICENSE.md")) {
                fs.copyFileSync(__dirname + "/../LICENSE.md", __dirname + "/tmp/LICENSE.txt");
            }

            if (fs.existsSync(__dirname + "/../README.md")) {
                fs.copyFileSync(__dirname + "/../README.md", __dirname + "/tmp/README.md");
            }

            // Build the package
            execSync('npm pack', { cwd: `${__dirname}/tmp`, stdio: 'inherit' });
            // @todo test
            fs.renameSync(`${__dirname}/tmp/${sourceObj.name}-${sourceObj.version}.zip`, `${__dirname}/${sourceObj.name}-${sourceObj.version}.zip`, { recursive: true });
        };


    } else {
        // Prepare the single extension
        let ext = {};
        ext.name = sourceObj.name;
        ext.version = sourceObj.version;
        console.log(ext);
        prepareExtension(ext, sourceObj, config);
        // Build the package
        execSync('npm pack', { cwd: `${__dirname}/tmp/${ext.name}`, stdio: 'inherit' });
        fs.renameSync(`${__dirname}/tmp/${ext.name}/${ext.name}-${ext.version}.zip`, `${__dirname}/${ext.name}-${ext.version}.zip`, { recursive: true });
        fs.rmSync(__dirname + `/tmp/${ext.name}`, { recursive: true, force: true });
    }
}

main();
